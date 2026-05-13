<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ShopperCollection;
use ReflectionClass;
use WP_UnitTestCase;

/**
 * Tests for the ShopperCollection block type.
 */
class ShopperCollectionTests extends WP_UnitTestCase {

	/**
	 * System under test.
	 *
	 * Constructed via reflection so `AbstractBlock::__construct` doesn't run
	 * `parent::initialize()` and re-register the block (the test bootstrap
	 * has already registered it). The filter callbacks under test only read
	 * `$this->namespace` and `$this->block_name`, both class defaults.
	 *
	 * @var ShopperCollection
	 */
	private ShopperCollection $sut;

	/**
	 * Instantiate the block without invoking its constructor.
	 */
	public function setUp(): void {
		parent::setUp();

		$reflection = new ReflectionClass( ShopperCollection::class );
		$this->sut  = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * @return array<string, array{string, string, bool, bool}>
	 */
	public function provider_register_hooked_block(): array {
		$cart_only         = '<!-- wp:woocommerce/cart /-->';
		$cart_with_shopper = '<!-- wp:woocommerce/cart /--><!-- wp:woocommerce/shopper-collection {"listName":"saved-for-later"} /-->';

		return array(
			// label                                => array( cart_page_content, anchor, context_is_cart_page, expected_hooked ).
			'hooked after cart on cart page'        => array( $cart_only, 'woocommerce/cart', true, true ),
			'not hooked after non-cart anchor'      => array( $cart_only, 'core/paragraph', true, false ),
			'not hooked when context is other page' => array( $cart_only, 'woocommerce/cart', false, false ),
			'not hooked when already present'       => array( $cart_with_shopper, 'woocommerce/cart', true, false ),
		);
	}

	/**
	 * `register_hooked_block` only adds the block when the anchor is `woocommerce/cart`,
	 * the context is the cart page, and the cart page doesn't already contain the block.
	 *
	 * @dataProvider provider_register_hooked_block
	 *
	 * @param string $cart_page_content    Initial content of the cart page.
	 * @param string $anchor               Anchor block name passed to the filter.
	 * @param bool   $context_is_cart_page Whether the filter context is the cart page or some other page.
	 * @param bool   $expected_hooked      Whether the block should end up in the hooked list.
	 */
	public function test_register_hooked_block( string $cart_page_content, string $anchor, bool $context_is_cart_page, bool $expected_hooked ): void {
		$cart_page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $cart_page_content,
			)
		);
		update_option( 'woocommerce_cart_page_id', $cart_page_id );

		$context_id = $context_is_cart_page
			? $cart_page_id
			: self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);

		$hooked = $this->sut->register_hooked_block( array(), 'after', $anchor, get_post( $context_id ) );

		if ( $expected_hooked ) {
			$this->assertContains( 'woocommerce/shopper-collection', $hooked );
		} else {
			$this->assertNotContains( 'woocommerce/shopper-collection', $hooked );
		}
	}

	/**
	 * When the cart page option is unset, `wc_get_page_id()` returns -1 — the filter
	 * must treat that as "no cart page" rather than letting it match a real post ID.
	 */
	public function test_register_hooked_block_skips_when_cart_page_unset(): void {
		delete_option( 'woocommerce_cart_page_id' );

		$context_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:woocommerce/cart /-->',
			)
		);

		$hooked = $this->sut->register_hooked_block( array(), 'after', 'woocommerce/cart', get_post( $context_id ) );

		$this->assertNotContains( 'woocommerce/shopper-collection', $hooked );
	}

	/**
	 * The auto-injected block has its `listName` attribute set to `saved-for-later`.
	 */
	public function test_hooked_block_attributes_set_list_name(): void {
		$parsed_hooked_block = array(
			'blockName' => 'woocommerce/shopper-collection',
			'attrs'     => array(),
		);
		$parsed_anchor_block = array( 'blockName' => 'woocommerce/cart' );

		$result = $this->sut->set_hooked_block_attributes(
			$parsed_hooked_block,
			'woocommerce/shopper-collection',
			'after',
			$parsed_anchor_block
		);

		$this->assertSame( 'saved-for-later', $result['attrs']['listName'] );
	}

	/**
	 * For a new shopper landing on the page with nothing saved, SSR must:
	 *   - emit the empty-state `<li>` already `hidden`, so the message
	 *     never flashes between paint and iAPI hydration, and
	 *   - seed the wrapper's iAPI context with `hasShownItems: false` and
	 *     the matching `data-wp-watch` callback, so the JS-side
	 *     `state.isEmpty` getter has the inputs it needs to keep the
	 *     message hidden until the shopper has actually saved an item.
	 *
	 * Driven through reflection on `render()` as a logged-out user,
	 * since `prefetch_list_items` short-circuits to `[]` in that case —
	 * no Store API call, no feature-flag wiring, no user fixture needed.
	 * Sets `WP_Block_Supports::$block_to_render` up front so
	 * `get_block_wrapper_attributes()` (which reads it for layout/style
	 * supports) has the context it expects when called outside the
	 * usual block-render pipeline.
	 */
	public function test_render_seeds_hidden_empty_state_for_new_shopper(): void {
		wp_set_current_user( 0 );

		$attributes = array( 'listName' => 'saved-for-later' );

		$previous_block_to_render            = \WP_Block_Supports::$block_to_render;
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/shopper-collection',
			'attrs'     => $attributes,
		);

		try {
			$reflection = new ReflectionClass( ShopperCollection::class );
			$method     = $reflection->getMethod( 'render' );
			$method->setAccessible( true );

			$markup = (string) $method->invoke( $this->sut, $attributes, '', null );
		} finally {
			\WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}

		// The empty-state `<li>` is always rendered, always initially hidden.
		$this->assertMatchesRegularExpression(
			'/<li[^>]*class="wc-block-shopper-collection__empty"[^>]*\bhidden\b/',
			$markup,
			'Empty-state <li> must be initially hidden so the message does not flash before iAPI hydration.'
		);

		// The wrapper's `data-wp-context` JSON is HTML-escaped into an
		// attribute, so the embedded quotes appear as `&quot;` in the
		// rendered markup.
		$this->assertStringContainsString(
			'&quot;hasShownItems&quot;:false',
			$markup,
			'Wrapper context must seed hasShownItems=false for an empty list so the empty message stays hidden until the shopper actually saves an item.'
		);

		$this->assertStringContainsString(
			'data-wp-watch="callbacks.trackShownItems"',
			$markup,
			'Wrapper must wire the trackShownItems watcher so hasShownItems can flip to true the first time items appear in-session.'
		);
	}
}

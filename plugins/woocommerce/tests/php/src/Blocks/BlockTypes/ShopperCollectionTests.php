<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ShopperCollection;
use ReflectionClass;
use ReflectionMethod;
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
	 * The auto-injected block ships with a seeded `core/heading` inner block so
	 * fresh cart pages render the heading on the frontend out of the box. The
	 * matching `null` push onto `innerContent` is what makes `WP_Block::render()`
	 * walk into the heading when building `$content`.
	 */
	public function test_hooked_block_attributes_seed_heading_inner_block(): void {
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

		$this->assertArrayHasKey( 'innerBlocks', $result );
		$this->assertCount( 1, $result['innerBlocks'] );

		$heading = $result['innerBlocks'][0];
		$this->assertSame( 'core/heading', $heading['blockName'] );
		$this->assertSame( 2, $heading['attrs']['level'] );
		$this->assertArrayHasKey( 'content', $heading['attrs'] );
		// `attrs.content` is the raw translated string (no `esc_html`) —
		// JSON encoding handles escaping at serialization time. Asserts
		// the en_US source string the test bootstrap runs under.
		$this->assertSame( 'Saved for later', $heading['attrs']['content'] );
		$this->assertStringContainsString( '<h2 class="wp-block-heading">', $heading['innerHTML'] );
		$this->assertSame( array( $heading['innerHTML'] ), $heading['innerContent'] );

		$this->assertArrayHasKey( 'innerContent', $result );
		$this->assertContains( null, $result['innerContent'] );
	}

	/**
	 * If something else has already populated `innerBlocks` (e.g. another hook
	 * running first, or a saved customisation), the seeding logic must not
	 * clobber it.
	 */
	public function test_hooked_block_attributes_preserves_existing_inner_blocks(): void {
		$existing_heading    = array(
			'blockName'    => 'core/heading',
			'attrs'        => array(
				'level'   => 3,
				'content' => 'My custom heading',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '<h3 class="wp-block-heading">My custom heading</h3>',
			'innerContent' => array( '<h3 class="wp-block-heading">My custom heading</h3>' ),
		);
		$parsed_hooked_block = array(
			'blockName'    => 'woocommerce/shopper-collection',
			'attrs'        => array(),
			'innerBlocks'  => array( $existing_heading ),
			'innerContent' => array( null ),
		);
		$parsed_anchor_block = array( 'blockName' => 'woocommerce/cart' );

		$result = $this->sut->set_hooked_block_attributes(
			$parsed_hooked_block,
			'woocommerce/shopper-collection',
			'after',
			$parsed_anchor_block
		);

		$this->assertCount( 1, $result['innerBlocks'] );
		$this->assertSame( $existing_heading, $result['innerBlocks'][0] );
	}

	/**
	 * `render()` returns an empty string for logged-out shoppers.
	 */
	public function test_render_returns_empty_for_logged_out_user(): void {
		wp_set_current_user( 0 );

		$render = new ReflectionMethod( ShopperCollection::class, 'render' );
		$render->setAccessible( true );

		$this->assertSame( '', (string) $render->invoke( $this->sut, array( 'listName' => 'saved-for-later' ), '', null ) );
	}
}

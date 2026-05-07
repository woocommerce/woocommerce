<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ShopperCollection;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use WP_UnitTestCase;

/**
 * Tests for the ShopperCollection block type.
 */
class ShopperCollectionTests extends WP_UnitTestCase {

	/**
	 * System under test. Constructing it registers the block-hook filters via `initialize()`.
	 *
	 * @var ShopperCollection
	 */
	private ShopperCollection $sut;

	/**
	 * Instantiate the block.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ShopperCollection(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Remove the registered filters so other tests aren't affected.
	 */
	public function tearDown(): void {
		remove_filter( 'hooked_block_types', array( $this->sut, 'register_hooked_block' ), 9 );
		remove_filter( 'hooked_block_woocommerce/shopper-collection', array( $this->sut, 'set_hooked_block_attributes' ), 10 );

		parent::tearDown();
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
			: self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked = apply_filters( 'hooked_block_types', array(), 'after', $anchor, get_post( $context_id ) );

		if ( $expected_hooked ) {
			$this->assertContains( 'woocommerce/shopper-collection', $hooked );
		} else {
			$this->assertNotContains( 'woocommerce/shopper-collection', $hooked );
		}
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

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$result = apply_filters(
			'hooked_block_woocommerce/shopper-collection',
			$parsed_hooked_block,
			'woocommerce/shopper-collection',
			'after',
			$parsed_anchor_block
		);

		$this->assertSame( 'saved-for-later', $result['attrs']['listName'] );
	}
}

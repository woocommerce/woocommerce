<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain;

use WC_Unit_Test_Case;
use WP_Block_Type_Registry;

/**
 * Tests for the on-demand WooCommerce block-type registration wired up by Bootstrap.
 *
 * Product and variation descriptions are run through do_blocks on the woocommerce_short_description filter in
 * contexts where eager block registration is skipped (the products REST endpoints, the Store API schemas, the
 * variation AJAX endpoint and product webhooks). Bootstrap registers block types on demand there so those blocks
 * do not render empty.
 */
class BootstrapTest extends WC_Unit_Test_Case {

	/**
	 * A foundational WooCommerce block that register_blocks() always registers (it is not gated behind a theme
	 * or feature flag), so it is a stable subject for the on-demand registration assertions.
	 *
	 * @var string
	 */
	private const SAMPLE_BLOCK = 'woocommerce/product-price';

	/**
	 * Snapshot of the WooCommerce block types registered before each test, restored afterwards so the shared
	 * global registry is not left in a modified state for other tests.
	 *
	 * @var array<string, \WP_Block_Type>
	 */
	private array $registered_woo_blocks = array();

	/**
	 * Snapshot and unregister the WooCommerce blocks so each test starts from a state that mirrors a fresh
	 * request whose eager block registration was skipped.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $registry->get_all_registered() as $name => $block_type ) {
			if ( 0 === strpos( $name, 'woocommerce/' ) ) {
				$this->registered_woo_blocks[ $name ] = $block_type;
				$registry->unregister( $name );
			}
		}
	}

	/**
	 * Restore the exact set of WooCommerce blocks that was registered before the test ran.
	 */
	public function tearDown(): void {
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( array_keys( $registry->get_all_registered() ) as $name ) {
			if ( 0 === strpos( (string) $name, 'woocommerce/' ) ) {
				$registry->unregister( $name );
			}
		}
		foreach ( $this->registered_woo_blocks as $block_type ) {
			$registry->register( $block_type );
		}
		$this->registered_woo_blocks = array();

		parent::tearDown();
	}

	/**
	 * @testdox Bootstrap hooks on-demand block-type registration to woocommerce_short_description at priority 8.
	 */
	public function test_short_description_filter_is_hooked_at_priority_8(): void {
		global $wp_filter;

		$this->assertArrayHasKey( 'woocommerce_short_description', $wp_filter, 'The filter should have registered callbacks.' );

		$method_names = array();
		foreach ( $wp_filter['woocommerce_short_description']->callbacks[8] ?? array() as $callback ) {
			if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ?? null ) ) {
				$method_names[] = $callback['function'][1];
			}
		}

		$this->assertContains(
			'maybe_register_blocks_from_content',
			$method_names,
			'Bootstrap should hook on-demand block registration at priority 8, before do_blocks at priority 9.'
		);
	}

	/**
	 * @testdox Filtering a description that contains a WooCommerce block registers block types that were missing.
	 */
	public function test_short_description_filter_registers_missing_block_types(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertNotEmpty( $this->registered_woo_blocks, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'WooCommerce blocks should start unregistered for this test.' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing core filter to exercise its callbacks, not declaring a new hook.
		apply_filters( 'woocommerce_short_description', 'Intro <!-- wp:woocommerce/product-price /--> outro' );

		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Block types should be registered on demand when a description containing a block is rendered.'
		);
	}

	/**
	 * @testdox Filtering a description without WooCommerce block markup does not register block types.
	 */
	public function test_short_description_filter_skips_registration_for_plain_content(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertNotEmpty( $this->registered_woo_blocks, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'WooCommerce blocks should start unregistered for this test.' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing core filter to exercise its callbacks, not declaring a new hook.
		apply_filters( 'woocommerce_short_description', 'Just plain text with no blocks, or only a core <!-- wp:paragraph -->.' );

		$this->assertFalse(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'A description without WooCommerce block markup should stay on the fast path and register nothing.'
		);
	}

	/**
	 * @testdox Fetching a product through the wc/v3 REST API registers block types on demand for its description.
	 */
	public function test_products_rest_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$product = new \WC_Product_Simple();
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should start unregistered for this test.' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertSame( 200, $response->get_status(), 'The products REST request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Fetching a product through the REST API should register block types on demand so description blocks render.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Fetching the cart through the Store API registers block types on demand for an item's description.
	 */
	public function test_store_api_cart_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$product = new \WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$cart_item_key = wc()->cart->add_to_cart( $product->get_id() );
		$this->assertNotEmpty( $cart_item_key, 'The product should be added to the cart so the item renders in the response.' );

		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should start unregistered for this test.' );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 200, $response->get_status(), 'The Store API cart request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Fetching the cart through the Store API should register block types on demand so description blocks render.'
		);

		wc()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * @testdox Filtering a description does not re-register block types when they are already registered.
	 */
	public function test_short_description_filter_skips_registration_when_blocks_already_registered(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		// Restore the blocks so the request looks like a normal one whose eager registration already ran.
		foreach ( $this->registered_woo_blocks as $block_type ) {
			$registry->register( $block_type );
		}
		$this->assertTrue( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should be registered before the filter runs.' );

		// Firing the filter must not call register_blocks() again — doing so would re-register already-registered
		// block types and trigger a doing_it_wrong failure, which WC_Unit_Test_Case turns into a test failure.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing core filter to exercise its callbacks, not declaring a new hook.
		apply_filters( 'woocommerce_short_description', 'Intro <!-- wp:woocommerce/product-price /--> outro' );

		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Block types should remain registered and must not be re-registered on demand.'
		);
	}
}

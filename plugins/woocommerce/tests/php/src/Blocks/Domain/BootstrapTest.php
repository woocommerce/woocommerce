<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain;

use Automattic\WooCommerce\Blocks\BlockTypesController;
use Automattic\WooCommerce\Blocks\Package;
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
	 * Snapshot of the WooCommerce block types registered before each test, restored afterwards so the shared
	 * global registry is not left in a modified state for other tests.
	 *
	 * @var array<string, \WP_Block_Type>
	 */
	private array $registered_woo_blocks = array();

	/**
	 * Unregister the WooCommerce blocks and clear the registration guard so each test starts from a state that
	 * mirrors a fresh request whose eager registration was skipped.
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

		$this->set_blocks_registered_flag( false );
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
		$this->set_blocks_registered_flag( true );
		$this->registered_woo_blocks = array();

		parent::tearDown();
	}

	/**
	 * Set the shared BlockTypesController registration guard.
	 *
	 * @param bool $registered Whether block types should be treated as already registered.
	 */
	private function set_blocks_registered_flag( bool $registered ): void {
		$controller = Package::container()->get( BlockTypesController::class );
		$property   = new \ReflectionProperty( $controller, 'blocks_registered' );
		$property->setAccessible( true );
		$property->setValue( $controller, $registered );
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

		$sample = array_key_first( $this->registered_woo_blocks );
		$this->assertNotNull( $sample, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( $sample ), 'WooCommerce blocks should start unregistered for this test.' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing core filter to exercise its callbacks, not declaring a new hook.
		apply_filters( 'woocommerce_short_description', 'Intro <!-- wp:woocommerce/product-price /--> outro' );

		foreach ( array_keys( $this->registered_woo_blocks ) as $name ) {
			$this->assertTrue(
				$registry->is_registered( $name ),
				sprintf( '%s should be registered on demand when a description containing a block is rendered.', $name )
			);
		}
	}

	/**
	 * @testdox Filtering a description without WooCommerce block markup does not register block types.
	 */
	public function test_short_description_filter_skips_registration_for_plain_content(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$sample = array_key_first( $this->registered_woo_blocks );
		$this->assertNotNull( $sample, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( $sample ), 'WooCommerce blocks should start unregistered for this test.' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing core filter to exercise its callbacks, not declaring a new hook.
		apply_filters( 'woocommerce_short_description', 'Just plain text with no blocks, or only a core <!-- wp:paragraph -->.' );

		$this->assertFalse(
			$registry->is_registered( $sample ),
			'A description without WooCommerce block markup should stay on the fast path and register nothing.'
		);
	}

	/**
	 * @testdox Fetching a product through the wc/v3 REST API registers block types on demand for its description.
	 */
	public function test_products_rest_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();
		$sample   = array_key_first( $this->registered_woo_blocks );
		$this->assertNotNull( $sample, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );

		$product = new \WC_Product_Simple();
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$this->assertFalse( $registry->is_registered( $sample ), 'Blocks should start unregistered for this test.' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertSame( 200, $response->get_status(), 'The products REST request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( $sample ),
			'Fetching a product through the REST API should register block types on demand so description blocks render.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Fetching the cart through the Store API registers block types on demand for an item's description.
	 */
	public function test_store_api_cart_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();
		$sample   = array_key_first( $this->registered_woo_blocks );
		$this->assertNotNull( $sample, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );

		$product = new \WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$cart_item_key = wc()->cart->add_to_cart( $product->get_id() );
		$this->assertNotEmpty( $cart_item_key, 'The product should be added to the cart so the item renders in the response.' );

		$this->assertFalse( $registry->is_registered( $sample ), 'Blocks should start unregistered for this test.' );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 200, $response->get_status(), 'The Store API cart request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( $sample ),
			'Fetching the cart through the Store API should register block types on demand so description blocks render.'
		);

		wc()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * @testdox Calling register_blocks() again after registration is a no-op and does not re-register block types.
	 */
	public function test_register_blocks_is_idempotent(): void {
		$registry   = WP_Block_Type_Registry::get_instance();
		$controller = Package::container()->get( BlockTypesController::class );

		$controller->register_blocks();
		$sample = array_key_first( $this->registered_woo_blocks );
		$this->assertNotNull( $sample, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertTrue( $registry->is_registered( $sample ), 'The first call should register block types.' );

		// A second call must not attempt to register already-registered block types (which would trigger a
		// doing_it_wrong failure); the guard makes it a no-op.
		$controller->register_blocks();

		$this->assertTrue(
			$registry->is_registered( $sample ),
			'Block types should remain registered after a repeat register_blocks() call.'
		);
	}
}

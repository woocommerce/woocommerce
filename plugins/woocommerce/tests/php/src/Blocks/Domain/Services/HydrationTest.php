<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the Hydration class.
 */
class HydrationTest extends \WP_UnitTestCase {
	/**
	 * Instance of Hydration for testing.
	 *
	 * @var Hydration
	 */
	private $hydration;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize Store API.
		StoreApi::container();

		$this->hydration = new Hydration( $this->createMock( AssetDataRegistry::class ) );
	}

	/**
	 * Reset the store session's notice queue after each test so notices don't leak between tests.
	 */
	public function tearDown(): void {
		if ( WC()->session ) {
			WC()->session->set( 'wc_notices', null );
		}

		parent::tearDown();
	}

	/**
	 * Test that get_rest_api_response_data handles cart endpoint and returns valid cart structure.
	 */
	public function test_get_rest_api_response_data_cart_without_params() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/cart' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertArrayHasKey( 'headers', $result );

		// Verify cart response has expected structure.
		$cart = $result['body'];
		$this->assertArrayHasKey( 'items', $cart, 'Cart should have items array' );
		$this->assertArrayHasKey( 'totals', $cart, 'Cart should have totals' );
		$this->assertArrayHasKey( 'coupons', $cart, 'Cart should have coupons array' );
		$this->assertArrayHasKey( 'shipping_rates', $cart, 'Cart should have shipping_rates' );
		$this->assertIsArray( $cart['items'] );
		$this->assertIsArray( $cart['coupons'] );
		$this->assertObjectHasProperty( 'total_items', $cart['totals'] );
		$this->assertObjectHasProperty( 'total_price', $cart['totals'] );
		$this->assertObjectHasProperty( 'currency_code', $cart['totals'] );
	}

	/**
	 * Test that get_rest_api_response_data handles invalid routes.
	 */
	public function test_get_rest_api_response_data_handles_invalid_routes() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/nonexistent' );

		$this->assertIsArray( $result );
		// Should fall back to rest_preload_api_request which may return empty or have body key.
		$this->assertTrue(
			empty( $result ) || isset( $result['body'] ),
			'Invalid route should return empty array or array with body key'
		);
	}

	/**
	 * Test that get_rest_api_response_data handles products with stock_status query parameter.
	 */
	public function test_get_rest_api_response_data_products_with_query_params() {
		$out_of_stock_product = \WC_Helper_Product::create_simple_product();
		$out_of_stock_product->set_name( 'Out of Stock Product' );
		$out_of_stock_product->set_stock_status( 'outofstock' );
		$out_of_stock_product->save();

		$in_stock_product = \WC_Helper_Product::create_simple_product();
		$in_stock_product->set_name( 'In Stock Product' );
		$in_stock_product->set_stock_status( 'instock' );
		$in_stock_product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?stock_status[]=outofstock' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );
		$this->assertNotEmpty( $result['body'], 'Should return at least one out of stock product' );

		// Verify all returned products have outofstock status.
		$found_out_of_stock = false;
		foreach ( $result['body'] as $product_data ) {
			$this->assertArrayHasKey( 'is_in_stock', $product_data );
			$this->assertFalse( $product_data['is_in_stock'], 'Returned product should be out of stock' );

			if ( $product_data['id'] === $out_of_stock_product->get_id() ) {
				$found_out_of_stock = true;
			}

			// Ensure in stock product is NOT in results.
			$this->assertNotEquals(
				$in_stock_product->get_id(),
				$product_data['id'],
				'In stock product should not appear in outofstock filter results'
			);
		}

		$this->assertTrue( $found_out_of_stock, 'Out of stock product should be in results' );

		$out_of_stock_product->delete( true );
		$in_stock_product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles multiple query parameters (parent + type).
	 */
	public function test_get_rest_api_response_data_with_multiple_query_params() {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variable_product->save();

		$result = $this->hydration->get_rest_api_response_data(
			'/wc/store/v1/products?parent[]=' . $variable_product->get_id() . '&type=variation'
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Each returned product should be a variation of the parent.
		foreach ( $result['body'] as $variation_data ) {
			$this->assertArrayHasKey( 'id', $variation_data );
			$this->assertArrayHasKey( 'parent', $variation_data );
			$this->assertEquals( $variable_product->get_id(), $variation_data['parent'] );
			$this->assertEquals( 'variation', $variation_data['type'] );
		}

		$variable_product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles product with ID in URL.
	 */
	public function test_get_rest_api_response_data_product_with_id() {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product for Hydration' );
		$product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products/' . $product->get_id() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		if ( isset( $result['body']['id'] ) ) {
			$this->assertEquals( $product->get_id(), $result['body']['id'] );
		}
		if ( isset( $result['body']['name'] ) ) {
			$this->assertEquals( 'Test Product for Hydration', $result['body']['name'] );
		}

		$product->delete( true );
	}

	/**
	 * Test that encoded query parameters are properly handled.
	 */
	public function test_get_rest_api_response_data_with_encoded_query_params() {
		$matching_product = \WC_Helper_Product::create_simple_product();
		$matching_product->set_name( 'Unique Hydration Test Product' );
		$matching_product->save();

		// Create a product that should not match in search results.
		$non_matching_product = \WC_Helper_Product::create_simple_product();
		$non_matching_product->set_name( 'Unrelated Item' );
		$non_matching_product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?search=Unique%20Hydration%20Test' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Verify we got results and the matching product is included.
		$this->assertNotEmpty( $result['body'], 'Search should return at least one product' );

		$found_matching_product = false;
		foreach ( $result['body'] as $product_data ) {
			if ( $product_data['id'] === $matching_product->get_id() ) {
				$found_matching_product = true;
				$this->assertEquals( 'Unique Hydration Test Product', $product_data['name'] );
			}
			$this->assertNotEquals( $non_matching_product->get_id(), $product_data['id'], 'Non-matching product should not appear in search results' );
		}

		$this->assertTrue( $found_matching_product, 'Matching product should be in search results' );

		$matching_product->delete( true );
		$non_matching_product->delete( true );
	}

	/**
	 * @testdox Should restore caller notices queued before hydration and discard notices added during hydration.
	 */
	public function test_get_rest_api_response_data_preserves_caller_notices_and_discards_render_notices() {
		wc_clear_notices();
		wc_add_notice( 'Pre-existing notice', 'success' );

		$notices_during_render = null;
		add_filter(
			'woocommerce_hydration_request_after_callbacks',
			function ( $response ) use ( &$notices_during_render ) {
				$notices_during_render = wc_get_notices();
				wc_add_notice( 'Notice injected during render', 'success' );
				return $response;
			}
		);

		$this->hydration->get_rest_api_response_data( '/wc/store/v1/products' );

		$this->assertEmpty( $notices_during_render, 'Notices should be cleared while hydration is running, proving the cache step snapshotted and cleared them' );
		$this->assertTrue( wc_has_notice( 'Pre-existing notice' ), 'Caller notice queued before hydration should be restored afterward' );
		$this->assertFalse( wc_has_notice( 'Notice injected during render' ), 'Notice added during hydration should be discarded, not leaked into the caller session' );
	}

	/**
	 * @testdox Should no-op without touching the session when the notice functions are unavailable.
	 */
	public function test_get_rest_api_response_data_noops_when_notice_functions_unavailable() {
		wc_clear_notices();
		wc_add_notice( 'Notice queued before hydration', 'success' );

		$hydration = $this->create_hydration_with_forced_availability( false );

		$session_notices_before = WC()->session->get( 'wc_notices' );
		$this->assertNotEmpty( $session_notices_before, 'Precondition: a notice should be queued before hydration runs' );

		$hydration->get_rest_api_response_data( '/wc/store/v1/products' );

		$this->assertEquals( $session_notices_before, WC()->session->get( 'wc_notices' ), 'Session notices should be untouched when the notice functions are unavailable' );
		$this->assertTrue( wc_has_notice( 'Notice queued before hydration' ), 'Queued notice should not be cleared when the notice functions are unavailable' );
		$this->assertNull( $this->get_cached_store_notices( $hydration ), 'No snapshot should be taken when the notice functions are unavailable' );
	}

	/**
	 * @testdox Should not wipe notices on restore when the notice functions become available only after caching skipped the snapshot.
	 */
	public function test_get_rest_api_response_data_does_not_wipe_notices_when_functions_become_available_mid_render() {
		wc_clear_notices();
		wc_add_notice( 'Notice queued before hydration', 'success' );

		$hydration = $this->create_hydration_with_forced_availability( false );

		add_filter(
			'woocommerce_hydration_request_after_callbacks',
			function ( $response ) use ( $hydration ) {
				// Simulate the notice functions becoming defined mid-render, e.g. via wc_load_cart().
				$hydration->notice_functions_available = true;
				return $response;
			}
		);

		$hydration->get_rest_api_response_data( '/wc/store/v1/products' );

		$this->assertTrue( wc_has_notice( 'Notice queued before hydration' ), 'Notices should survive when no snapshot was taken to restore from' );
		$this->assertNull( $this->get_cached_store_notices( $hydration ), 'Sentinel should remain null since no snapshot was taken this cycle' );
	}

	/**
	 * Creates a Hydration subclass whose notice-functions-availability seam is forced to a given value, so tests
	 * can deterministically simulate wc_get_notices()/wc_clear_notices()/wc_set_notices() being undefined.
	 *
	 * @param bool $available Initial value the seam should report.
	 * @return Hydration
	 */
	private function create_hydration_with_forced_availability( bool $available ) {
		return new class( $this->createMock( AssetDataRegistry::class ), $available ) extends Hydration {
			/**
			 * Whether store_notice_functions_available() should report the notice functions as available.
			 *
			 * @var bool
			 */
			public $notice_functions_available;

			/**
			 * Constructor.
			 *
			 * @param AssetDataRegistry $asset_data_registry        Instance of the asset data registry.
			 * @param bool              $notice_functions_available Initial seam value.
			 */
			public function __construct( AssetDataRegistry $asset_data_registry, bool $notice_functions_available ) {
				parent::__construct( $asset_data_registry );
				$this->notice_functions_available = $notice_functions_available;
			}

			/**
			 * Overrides the availability seam so tests can force the "functions unavailable" path deterministically.
			 *
			 * @return bool
			 */
			protected function store_notice_functions_available(): bool {
				return $this->notice_functions_available;
			}
		};
	}

	/**
	 * Reads the protected $cached_store_notices property via reflection.
	 *
	 * @param Hydration $hydration Instance to inspect.
	 * @return array|null
	 */
	private function get_cached_store_notices( Hydration $hydration ) {
		$property = new \ReflectionProperty( Hydration::class, 'cached_store_notices' );
		$property->setAccessible( true );

		return $property->getValue( $hydration );
	}
}

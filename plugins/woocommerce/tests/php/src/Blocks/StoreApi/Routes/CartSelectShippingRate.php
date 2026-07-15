<?php
/**
 * Controller Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Shipping\ShippingMethodOriginTracker;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Cart Select Shipping Rate Controller Tests.
 *
 * These tests pin the route's `package_id` normalization, which the shipping-method
 * origin guard in `CartController::select_shipping_rate()` silently depends on.
 *
 * The block checkout's pickup-options block does NOT send `package_id: 0` — it sends
 * `package_id: null`. A real batch request from the block looks like:
 *
 *     {"path":"/wc/store/v1/cart/select-shipping-rate","method":"POST",
 *      "data":{"package_id":null,"rate_id":"local_pickup:3"}}
 *
 * `CartSelectShippingRate::get_route_post_response()` normalizes that null by iterating
 * `CartController::get_shipping_packages()` and calling `select_shipping_rate()` with the
 * real integer package id. That normalization is what lets the origin guard compare the
 * incoming rate against the correct `chosen_shipping_methods` session key.
 *
 * If the normalization were ever dropped, `null` would reach `select_shipping_rate()`, PHP
 * would coerce it to `''` as an array key, and the route would read and write a phantom
 * package instead of package 0: the guard would never see the current choice, and the real
 * package's chosen rate would never be updated — re-breaking the Local Pickup unstick. Every
 * unit test of `select_shipping_rate()` passes an explicit integer, so none of them would
 * notice. These route-level tests do.
 */
class CartSelectShippingRate extends ControllerTestCase {

	/**
	 * Local pickup rate injected into the test package.
	 *
	 * @var string
	 */
	private const PICKUP_RATE_ID = 'local_pickup:1';

	/**
	 * Delivery rate injected into the test package.
	 *
	 * @var string
	 */
	private const DELIVERY_RATE_ID = 'flat_rate:1';

	/**
	 * Product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Tracker for chosen shipping method origins.
	 *
	 * @var ShippingMethodOriginTracker
	 */
	private $origin_tracker;

	/**
	 * Create immutable catalog rows shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		$products = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				),
			)
		);

		self::$product_ids = array_map( fn( $product ) => $product->get_id(), $products );
	}

	/**
	 * Delete class products through WooCommerce data stores.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( self::$product_ids );
	}

	/**
	 * Setup a shippable cart with a package offering both pickup and delivery.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->origin_tracker = wc_get_container()->get( ShippingMethodOriginTracker::class );

		// Register the rate filter and drop any cached package rates before anything can trigger a
		// shipping calculation — WC_Shipping caches calculated rates in the session per package hash,
		// so a calculation that runs before the filter is in place would be reused for the whole test.
		add_filter( 'woocommerce_package_rates', array( $this, 'inject_pickup_and_delivery_rates' ), 100 );
		wc()->session->set( 'shipping_for_package_0', null );

		// A registered flat rate in the default zone makes shipping methods "exist" for the
		// block-checkout default logic; the actual rates in the package come from the filter above.
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();
		$fixtures->shipping_add_flat_rate_instance();

		wc_empty_cart();

		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_postcode( '90210' );
		wc()->customer->set_shipping_city( 'Beverly Hills' );

		wc()->cart->add_to_cart( self::$product_ids[0], 1 );
	}

	/**
	 * Remove the rate filter and the session state the shipping calculation leaves behind.
	 */
	protected function tearDown(): void {
		remove_filter( 'woocommerce_package_rates', array( $this, 'inject_pickup_and_delivery_rates' ), 100 );

		wc()->session->set( 'chosen_shipping_methods', null );
		wc()->session->set( ShippingMethodOriginTracker::SESSION_KEY, null );
		wc()->session->set( 'previous_shipping_methods', null );
		wc()->session->set( 'shipping_method_counts', null );
		wc()->session->set( 'shipping_for_package_0', null );

		parent::tearDown();
	}

	/**
	 * Replace the calculated rates with a deterministic pickup + delivery pair.
	 *
	 * Mirrors the synthetic-rate approach used by WC_Cart_Default_Shipping_Method_Test, so the
	 * rate IDs the tests select are stable and `local_pickup` is recognised as a pickup method by
	 * LocalPickupUtils.
	 *
	 * @return \WC_Shipping_Rate[]
	 */
	public function inject_pickup_and_delivery_rates(): array {
		return array(
			self::PICKUP_RATE_ID   => new \WC_Shipping_Rate( self::PICKUP_RATE_ID, 'Local pickup', '0', array(), 'local_pickup' ),
			self::DELIVERY_RATE_ID => new \WC_Shipping_Rate( self::DELIVERY_RATE_ID, 'Flat rate', '10', array(), 'flat_rate' ),
		);
	}

	/**
	 * Leave the session in the state a block checkout page load produces once the auto-defaulter
	 * has settled on Local Pickup: the rate is chosen for package 0 and its origin is 'auto'.
	 *
	 * The priming calculation populates `previous_shipping_methods` / `shipping_method_counts` so a
	 * later `calculate_totals()` short-circuits instead of re-running the auto-defaulter, which is
	 * exactly the steady state the pickup-options block re-asserts its rate into.
	 */
	private function arrange_auto_chosen_pickup(): void {
		wc()->cart->calculate_totals();

		$chosen    = wc()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = self::PICKUP_RATE_ID;
		wc()->session->set( 'chosen_shipping_methods', $chosen );

		$this->origin_tracker->set_origin( 0, ShippingMethodOriginTracker::ORIGIN_AUTO, self::PICKUP_RATE_ID );
	}

	/**
	 * Build a select-shipping-rate request that omits `package_id` entirely, the way the block
	 * checkout's pickup-options block does.
	 *
	 * @param string $rate_id Rate to select.
	 * @return \WP_REST_Request
	 */
	private function build_request_without_package_id( string $rate_id ): \WP_REST_Request {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/select-shipping-rate' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'rate_id' => $rate_id ) );

		return $request;
	}

	/**
	 * Re-asserting the already-chosen pickup rate without a `package_id` must be a no-op for the
	 * recorded origin. That only holds if the route normalized the missing (null) `package_id` to
	 * the real package id — otherwise `select_shipping_rate()` reads and writes the coerced ''
	 * key, the differs-guard never matches, and the auto-defaulted pickup gets laundered into a
	 * customer decision on every block checkout page load.
	 *
	 * @testdox Selecting the same rate without a package_id writes to the real package and preserves the auto origin.
	 */
	public function test_select_shipping_rate_without_package_id_normalizes_to_real_package_id(): void {
		$this->arrange_auto_chosen_pickup();

		$this->assertAPIResponse( $this->build_request_without_package_id( self::PICKUP_RATE_ID ), 200 );

		$this->assertSame(
			array( 0 => 'local_pickup:1' ),
			wc()->session->get( 'chosen_shipping_methods' ),
			'A missing package_id must be normalized to the real package id, not coerced into an empty array key'
		);

		$this->assertSame(
			'auto',
			$this->origin_tracker->get_origin( 0 ),
			'Re-asserting the already-chosen rate must not flip the recorded origin to manual'
		);
	}

	/**
	 * Control for the test above: the same request shape, but selecting a different rate. It proves
	 * the no-package_id path really does reach the origin tracker, so the preserved 'auto' origin
	 * above is the differs-guard working rather than a dead code path.
	 *
	 * @testdox Selecting a different rate without a package_id records a manual origin against the real package.
	 */
	public function test_select_shipping_rate_without_package_id_records_manual_on_genuine_change(): void {
		$this->arrange_auto_chosen_pickup();

		$this->assertAPIResponse( $this->build_request_without_package_id( self::DELIVERY_RATE_ID ), 200 );

		$this->assertSame(
			array( 0 => 'flat_rate:1' ),
			wc()->session->get( 'chosen_shipping_methods' ),
			'A genuine change must land on the real package id'
		);

		$this->assertSame(
			'manual',
			$this->origin_tracker->get_origin( 0 ),
			'Selecting a different rate is a customer decision and must record a manual origin'
		);
	}
}

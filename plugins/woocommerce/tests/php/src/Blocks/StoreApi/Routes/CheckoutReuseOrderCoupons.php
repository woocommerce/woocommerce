<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as CheckoutRoute;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests for reusing a placed order from the session when the cart's coupons changed.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
 */
class CheckoutReuseOrderCoupons extends \WP_Test_REST_TestCase {
	use StoreApiRestTestCaseTrait;

	/**
	 * Product added to the cart.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Payment gateways before the test.
	 *
	 * @var array
	 */
	private $gateways_before;

	/**
	 * PayPal Standard instance before the test.
	 *
	 * @var \WC_Gateway_Paypal|null
	 */
	private $paypal_before;

	/**
	 * Set up a guest session with PayPal Standard, which leaves a placed order pending and keeps the cart.
	 */
	protected function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ), 10, 4 );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_checkout_phone_field', 'optional' );

		$this->gateways_before = WC()->payment_gateways()->payment_gateways;
		$this->paypal_before   = \WC_Gateway_Paypal::get_instance();

		update_option(
			'woocommerce_paypal_settings',
			array(
				'enabled'  => 'yes',
				'email'    => 'merchant@example.com',
				'testmode' => 'yes',
			)
		);
		$paypal = new \WC_Gateway_Paypal();
		\WC_Gateway_Paypal::set_instance( $paypal );
		WC()->payment_gateways()->payment_gateways = array( 'paypal' => $paypal );

		$this->initialize_store_api_server();
		wp_set_current_user( 0 );

		$this->product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'Reuse product',
				'regular_price' => 10,
				'virtual'       => true,
			)
		);

		wc_empty_cart();
		WC()->session->set( 'store_api_draft_order', 0 );
	}

	/**
	 * Restore the session, cart and gateways.
	 */
	protected function tearDown(): void {
		try {
			remove_filter( 'woocommerce_set_cookie_enabled', array( $this, 'filter_woocommerce_set_cookie_enabled' ) );
			WC()->cart->empty_cart();
			WC()->session->destroy_session();
			WC()->customer = null;
			WC()->initialize_cart();
			$GLOBALS['wp_rest_server'] = null;
		} finally {
			try {
				parent::tearDown();
			} finally {
				WC()->payment_gateways()->payment_gateways = $this->gateways_before;
				\WC_Gateway_Paypal::set_instance( $this->paypal_before );
			}
		}
	}

	/**
	 * Apply cookies to $_COOKIE instead of sending them.
	 *
	 * @param bool   $enabled Whether setcookie() is enabled.
	 * @param string $name    Cookie name.
	 * @param string $value   Cookie value.
	 * @param int    $expire  Cookie expiry.
	 * @return false
	 */
	public function filter_woocommerce_set_cookie_enabled( $enabled, $name, $value, $expire ) {
		if ( $expire < time() ) {
			unset( $_COOKIE[ $name ] );
		} else {
			$_COOKIE[ $name ] = $value;
		}

		return false;
	}

	/**
	 * Create a coupon.
	 *
	 * @param string $code   Coupon code.
	 * @param string $type   Discount type.
	 * @param float  $amount Amount.
	 * @return \WC_Coupon
	 */
	private function create_coupon( string $code, string $type = 'fixed_cart', float $amount = 1 ): \WC_Coupon {
		$coupon = new \WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( $type );
		$coupon->set_amount( $amount );
		$coupon->set_usage_limit( 10 );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Send a Store API request.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Route path below /wc/store/v1/.
	 * @param array  $body   Body parameters.
	 * @return \WP_REST_Response
	 */
	private function store_api_request( string $method, string $path, array $body = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( $method, '/wc/store/v1/' . $path );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Add the product and apply a coupon through the Store API.
	 *
	 * @param string $code Coupon code.
	 */
	private function fill_cart( string $code ): void {
		$add_item = $this->store_api_request(
			'POST',
			'cart/add-item',
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);
		$this->assertSame( 201, $add_item->get_status(), print_r( $add_item->get_data(), true ) );
		$this->apply_coupon( $code );
	}

	/**
	 * Apply a coupon through the Store API.
	 *
	 * @param string $code Coupon code.
	 */
	private function apply_coupon( string $code ): void {
		$response = $this->store_api_request( 'POST', 'cart/apply-coupon', array( 'code' => $code ) );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * Remove a coupon through the Store API.
	 *
	 * @param string $code Coupon code.
	 */
	private function remove_coupon( string $code ): void {
		$response = $this->store_api_request( 'POST', 'cart/remove-coupon', array( 'code' => $code ) );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );
	}

	/**
	 * Send POST /checkout through a new route instance, as a new HTTP request would.
	 *
	 * @return \WP_REST_Response
	 */
	private function checkout(): \WP_REST_Response {
		$this->register_new_checkout_route();

		$response = $this->store_api_request( 'POST', 'checkout', $this->checkout_body() );
		$this->assertSame( 200, $response->get_status(), print_r( $response->get_data(), true ) );

		return $response;
	}

	/**
	 * Register a new checkout route instance in place of the current one.
	 */
	private function register_new_checkout_route(): void {
		$schema_controller = StoreApi::container()->get( SchemaController::class );
		$route             = new CheckoutRoute( $schema_controller, $schema_controller->get( 'checkout' ) );
		register_rest_route( $route->get_namespace(), $route->get_path(), $route->get_args(), true );
	}

	/**
	 * The body the checkout block sends to POST /checkout.
	 *
	 * @return array
	 */
	private function checkout_body(): array {
		$address = array(
			'first_name' => 'Sam',
			'last_name'  => 'Shopper',
			'company'    => '',
			'address_1'  => '1 Main St',
			'address_2'  => '',
			'city'       => 'San Francisco',
			'state'      => 'CA',
			'postcode'   => '94110',
			'country'    => 'US',
			'phone'      => '',
		);

		return array(
			'billing_address'  => array_merge( $address, array( 'email' => 'shopper@example.com' ) ),
			'shipping_address' => $address,
			'payment_method'   => 'paypal',
		);
	}

	/**
	 * Read a coupon's usage count from the database.
	 *
	 * @param \WC_Coupon $coupon Coupon.
	 * @return int
	 */
	private function get_usage_count( \WC_Coupon $coupon ): int {
		wp_cache_delete( $coupon->get_id(), 'post_meta' );

		return ( new \WC_Coupon( $coupon->get_id() ) )->get_usage_count();
	}

	/**
	 * @testdox Swapping a placed order's coupon for one worth the same leaves the order and its counted coupon alone.
	 */
	public function test_coupon_swap_does_not_rewrite_placed_order(): void {
		$placed  = $this->create_coupon( 'reuse-placed' );
		$swapped = $this->create_coupon( 'reuse-swapped' );
		$this->fill_cart( 'reuse-placed' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->assertSame( 1, $this->get_usage_count( $placed ), 'The pending order counts its coupon.' );

		$this->remove_coupon( 'reuse-placed' );
		$this->apply_coupon( 'reuse-swapped' );

		$this->assertTrue( wc_get_order( $order_id )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The swap must keep the cart hash, which is the case this test covers.' );
		$this->assertSame( array( 'reuse-placed' ), wc_get_order( $order_id )->get_coupon_codes() );

		$new_order = wc_get_order( (int) $this->checkout()->get_data()['order_id'] );

		$this->assertNotSame( $order_id, $new_order->get_id(), 'Checkout should place a new order for the new coupon.' );
		$this->assertSame( array( 'reuse-swapped' ), $new_order->get_coupon_codes() );
		$this->assertSame( '9.00', $new_order->get_total() );

		wc_get_order( $order_id )->update_status( OrderStatus::CANCELLED );

		$this->assertSame( 0, $this->get_usage_count( $placed ), 'Cancelling gives back the coupon the order counted.' );
		$this->assertSame( 1, $this->get_usage_count( $swapped ), 'The new order counts the new coupon.' );
	}

	/**
	 * @testdox Removing a coupon that took nothing off leaves the placed order and its counted coupon alone.
	 */
	public function test_removing_zero_discount_coupon_does_not_rewrite_placed_order(): void {
		$free_shipping = $this->create_coupon( 'reuse-free-shipping', 'fixed_cart', 0 );
		$free_shipping->set_free_shipping( true );
		$free_shipping->save();
		$this->fill_cart( 'reuse-free-shipping' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->assertSame( 1, $this->get_usage_count( $free_shipping ), 'The pending order counts its coupon.' );

		$this->remove_coupon( 'reuse-free-shipping' );

		$this->assertTrue( wc_get_order( $order_id )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The removal must keep the cart hash, which is the case this test covers.' );
		$this->assertNotSame( $order_id, (int) $this->checkout()->get_data()['order_id'], 'Checkout should place a new order.' );
		$this->assertSame( array( 'reuse-free-shipping' ), wc_get_order( $order_id )->get_coupon_codes() );

		wc_get_order( $order_id )->update_status( OrderStatus::CANCELLED );

		$this->assertSame( 0, $this->get_usage_count( $free_shipping ) );
	}

	/**
	 * @testdox Removing a coupon coded 0 that took nothing off leaves the placed order alone.
	 */
	public function test_removing_coupon_coded_zero_does_not_rewrite_placed_order(): void {
		$zero = $this->create_coupon( '0', 'fixed_cart', 0 );
		$zero->set_free_shipping( true );
		$zero->save();
		$this->fill_cart( '0' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->remove_coupon( '0' );

		$this->assertNotSame( $order_id, (int) $this->checkout()->get_data()['order_id'], 'Checkout should place a new order.' );
		$this->assertSame( array( '0' ), wc_get_order( $order_id )->get_coupon_codes() );
	}

	/**
	 * @testdox Swapping the coupon of a fully discounted placed order leaves it alone.
	 */
	public function test_coupon_swap_does_not_rewrite_zero_total_order(): void {
		$percent = $this->create_coupon( 'reuse-full-percent', 'percent', 100 );
		$this->create_coupon( 'reuse-full-fixed', 'fixed_cart', 10 );
		$this->fill_cart( 'reuse-full-percent' );
		WC()->cart->calculate_totals();
		$order = ( new OrderController() )->create_order_from_cart();
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		WC()->session->set( 'store_api_draft_order', $order->get_id() );
		$this->assertSame( 0.0, (float) $order->get_total(), 'The coupon must take the order to a zero total, which is the case this test covers.' );
		$this->assertSame( 1, $this->get_usage_count( $percent ), 'The pending order counts its coupon.' );

		$this->remove_coupon( 'reuse-full-percent' );
		$this->apply_coupon( 'reuse-full-fixed' );

		$this->assertTrue( wc_get_order( $order->get_id() )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The swap must keep the cart hash, which is the case this test covers.' );
		$this->assertNotSame( $order->get_id(), (int) $this->checkout()->get_data()['order_id'], 'Checkout should place a new order.' );
		$this->assertSame( array( 'reuse-full-percent' ), wc_get_order( $order->get_id() )->get_coupon_codes() );
	}

	/**
	 * @testdox A failed order, which gave its coupon usage back, is still reused when the coupons change.
	 */
	public function test_failed_order_is_reused_when_coupons_change(): void {
		$placed = $this->create_coupon( 'reuse-failed-placed' );
		$this->create_coupon( 'reuse-failed-swapped' );
		$this->fill_cart( 'reuse-failed-placed' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];
		wc_get_order( $order_id )->update_status( OrderStatus::FAILED );
		$this->assertSame( 0, $this->get_usage_count( $placed ), 'Failing gives the coupon back, which the exempt branch depends on.' );

		$this->remove_coupon( 'reuse-failed-placed' );
		$this->apply_coupon( 'reuse-failed-swapped' );

		$this->assertSame( $order_id, (int) $this->checkout()->get_data()['order_id'], 'Checkout should reuse the failed order.' );
		$this->assertSame( array( 'reuse-failed-swapped' ), wc_get_order( $order_id )->get_coupon_codes(), 'The failed order should follow the cart.' );
	}

	/**
	 * @testdox A placed order still follows the cart when an extension supplies a coupon that is not stored.
	 */
	public function test_order_follows_cart_with_extension_coupon(): void {
		$this->create_coupon( 'reuse-before-extension' );
		add_filter(
			'woocommerce_get_shop_coupon_data',
			function ( $data, $code ) {
				if ( 'extension_discount' !== $code ) {
					return $data;
				}

				return array(
					'discount_type' => 'fixed_cart',
					'amount'        => 1,
				);
			},
			10,
			2
		);
		$this->fill_cart( 'reuse-before-extension' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->remove_coupon( 'reuse-before-extension' );
		$this->apply_coupon( 'extension_discount' );

		$this->assertTrue( wc_get_order( $order_id )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The swap must keep the cart hash, which is the case this test covers.' );
		$this->assertSame( $order_id, (int) $this->checkout()->get_data()['order_id'], 'Checkout should reuse the order.' );
		$this->assertSame( array( 'extension_discount' ), wc_get_order( $order_id )->get_coupon_codes(), 'The order should follow the cart, as before.' );
	}

	/**
	 * @testdox An extension coupon in the cart does not let a stored coupon the order lacks onto the placed order.
	 */
	public function test_extension_coupon_does_not_let_new_stored_coupon_on(): void {
		$this->create_coupon( 'reuse-placed-five', 'fixed_cart', 5 );
		$this->create_coupon( 'reuse-added-one', 'fixed_cart', 1 );
		add_filter(
			'woocommerce_get_shop_coupon_data',
			function ( $data, $code ) {
				if ( 'extension_four' !== $code ) {
					return $data;
				}

				return array(
					'discount_type' => 'fixed_cart',
					'amount'        => 4,
				);
			},
			10,
			2
		);
		$this->fill_cart( 'reuse-placed-five' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->remove_coupon( 'reuse-placed-five' );
		$this->apply_coupon( 'extension_four' );
		$this->apply_coupon( 'reuse-added-one' );

		$this->assertTrue( wc_get_order( $order_id )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The change must keep the cart hash, which is the case this test covers.' );
		$this->assertNotSame( $order_id, (int) $this->checkout()->get_data()['order_id'], 'Checkout should place a new order.' );
		$this->assertSame( array( 'reuse-placed-five' ), wc_get_order( $order_id )->get_coupon_codes() );
	}

	/**
	 * @testdox A coupon swap inside one batch request does not rewrite the order the batch placed.
	 */
	public function test_coupon_swap_in_one_batch_does_not_rewrite_placed_order(): void {
		$placed  = $this->create_coupon( 'reuse-batch-placed' );
		$swapped = $this->create_coupon( 'reuse-batch-swapped' );
		$this->fill_cart( 'reuse-batch-placed' );
		$this->register_new_checkout_route();

		$nonce    = array( 'Nonce' => wp_create_nonce( 'wc_store_api' ) );
		$checkout = array(
			'method'  => 'POST',
			'path'    => '/wc/store/v1/checkout',
			'body'    => $this->checkout_body(),
			'headers' => $nonce,
		);
		$batch    = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$batch->set_header( 'Nonce', $nonce['Nonce'] );
		$batch->set_body_params(
			array(
				'requests' => array(
					$checkout,
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/remove-coupon',
						'body'    => array( 'code' => 'reuse-batch-placed' ),
						'headers' => $nonce,
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/apply-coupon',
						'body'    => array( 'code' => 'reuse-batch-swapped' ),
						'headers' => $nonce,
					),
					$checkout,
				),
			)
		);

		$responses = rest_get_server()->dispatch( $batch )->get_data()['responses'];

		foreach ( $responses as $response ) {
			$this->assertSame( 200, $response['status'], print_r( $response['body'], true ) );
		}

		$order_id     = (int) $responses[0]['body']['order_id'];
		$new_order_id = (int) $responses[3]['body']['order_id'];

		$this->assertNotSame( $order_id, $new_order_id, 'The second checkout should place a new order for the new coupon.' );
		$this->assertSame( array( 'reuse-batch-placed' ), wc_get_order( $order_id )->get_coupon_codes() );

		wc_get_order( $order_id )->update_status( OrderStatus::CANCELLED );

		$this->assertSame( 0, $this->get_usage_count( $placed ), 'Cancelling gives back the coupon the order counted.' );
		$this->assertSame( 1, $this->get_usage_count( $swapped ), 'The new order counts the new coupon.' );
	}

	/**
	 * @testdox A coupon trashed after checkout does not count as an extension coupon when the order is checked.
	 */
	public function test_trashed_coupon_does_not_count_as_extension_coupon(): void {
		$dropped = $this->create_coupon( 'reuse-trashed-dropped', 'fixed_cart', 0 );
		$trashed = $this->create_coupon( 'reuse-trashed', 'fixed_cart', 0 );
		$this->fill_cart( 'reuse-trashed-dropped' );
		$this->apply_coupon( 'reuse-trashed' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->remove_coupon( 'reuse-trashed-dropped' );
		wp_trash_post( $trashed->get_id() );

		$this->assertSame( array( 'reuse-trashed' ), array_values( WC()->cart->get_applied_coupons() ), 'The trashed coupon must still be in the cart, which is the case this test covers.' );
		$this->assertTrue( wc_get_order( $order_id )->has_cart_hash( WC()->cart->get_cart_hash() ), 'The removal must keep the cart hash, which is the case this test covers.' );

		$this->register_new_checkout_route();
		$this->store_api_request( 'GET', 'checkout' );

		$this->assertSame( array( 'reuse-trashed-dropped', 'reuse-trashed' ), wc_get_order( $order_id )->get_coupon_codes() );

		wc_get_order( $order_id )->update_status( OrderStatus::CANCELLED );

		$this->assertSame( 0, $this->get_usage_count( $dropped ), 'Cancelling gives back the coupon the order counted.' );
	}

	/**
	 * @testdox A placed order is reused when the cart holds its coupons in another order and they compare equal as numbers.
	 */
	public function test_order_is_reused_when_numeric_codes_come_in_another_order(): void {
		$this->create_coupon( '10', 'fixed_cart', 0 );
		$this->create_coupon( '1e1', 'fixed_cart', 0 );
		$this->fill_cart( '10' );
		$this->apply_coupon( '1e1' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$this->remove_coupon( '10' );
		$this->apply_coupon( '10' );

		$this->assertSame( array( '1e1', '10' ), array_values( WC()->cart->get_applied_coupons() ), 'The cart must hold the codes in another order, which is the case this test covers.' );

		$retry = $this->checkout();

		$this->assertSame( $order_id, (int) $retry->get_data()['order_id'], 'The retry should reuse the placed order.' );
	}

	/**
	 * @testdox A placed order with the cart's coupons is still reused when it has no stored coupons hash.
	 */
	public function test_order_with_same_coupons_is_reused(): void {
		$this->create_coupon( 'reuse-same' );
		$this->fill_cart( 'reuse-same' );
		$order_id = (int) $this->checkout()->get_data()['order_id'];

		$order = wc_get_order( $order_id );
		$order->delete_meta_data( '_coupons_hash' );
		$order->save();

		$retry = $this->checkout();

		$this->assertSame( $order_id, (int) $retry->get_data()['order_id'], 'The retry should reuse the placed order.' );
	}
}

<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use WC_Helper_Order;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * OrderControllerTests class.
 */
class OrderControllerTests extends TestCase {
	/**
	 * The system under test.
	 *
	 * @var OrderController
	 */
	private $sut;

	/**
	 * Set up before test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new class() extends OrderController {
			/**
			 * Check all required address fields are set and return errors if not. Parent is protected.
			 *
			 * @param \WC_Order $order Order object.
			 * @param string    $address_type billing or shipping address, used in error messages.
			 * @param \WP_Error $errors Error object.
			 */
			public function validate_address_fields( \WC_Order $order, $address_type, \WP_Error $errors ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
				parent::validate_address_fields( $order, $address_type, $errors );
			}
		};
	}

	/**
	 * Tear down after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->countries->locale = null;
		$this->sut              = null;
	}

	/**
	 * test_validate_existing_order_before_payment_valid_data.
	 */
	public function test_validate_existing_order_before_payment_valid_data() {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address( $order );
		$order->save();

		$this->assertNull( $this->sut->validate_existing_order_before_payment( $order ) );
	}

	/**
	 * test_validate_selected_shipping_methods_throws
	 */
	public function test_validate_selected_shipping_methods_throws() {
		$this->expectException( RouteException::class );
		$this->sut->validate_selected_shipping_methods( true, array( false ) );
		$this->sut->validate_selected_shipping_methods( true, null );
	}

	/**
	 * test_validate_selected_shipping_methods.
	 */
	public function test_validate_selected_shipping_methods() {
		// Add a flat rate to the default zone.
		$flat_rate    = WC()->shipping()->get_shipping_methods()['flat_rate'];
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		$default_zone->add_shipping_method( $flat_rate->id );
		$default_zone->save();

		$registered_methods = \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods();
		$valid_method       = array_shift( $registered_methods );

		$this->assertNull( $this->sut->validate_selected_shipping_methods( true, array( $valid_method->id . ':' . $valid_method->instance_id ) ) );
		$this->assertNull( $this->sut->validate_selected_shipping_methods( false, array( 'free-shipping' ) ) );
	}

	/**
	 * test_validate_order_before_payment_invalid_coupon_usage_limit.
	 */
	public function test_validate_order_before_payment_invalid_coupon_usage_limit() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 409 );
		$this->expectExceptionMessage( '"limited-coupon" was removed from the cart. Usage limit for coupon &quot;limited-coupon&quot; has been reached.' );

		$order = WC_Helper_Order::create_order();

		// Create a coupon with usage limit of 1 and mark it as used.
		$coupon = CouponHelper::create_coupon(
			'limited-coupon',
			'publish',
			array( 'usage_limit_per_user' => 1 )
		);
		$coupon->increase_usage_count( $order->get_billing_email() );
		$order->apply_coupon( $coupon );
		$order->save();

		try {
			$this->sut->validate_order_before_payment( $order );
		} finally {
			$this->assertEmpty( $order->get_coupon_codes() );
		}
	}

	/**
	 * test_validate_order_before_payment_invalid_coupons.
	 */
	public function test_validate_order_before_payment_invalid_coupons() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 409 );
		$this->expectExceptionMessage( '"fake-coupon" was removed from the cart. Please enter a valid email at checkout to use coupon code &quot;fake-coupon&quot;.' );

		$order  = WC_Helper_Order::create_order();
		$coupon = CouponHelper::create_coupon( 'fake-coupon', 'publish', array( 'customer_email' => 'random-email@example.com' ) );
		$order->add_coupon( $coupon->get_code() );
		$order->save();
		$this->assertEquals( array( 'fake-coupon' ), $order->get_coupon_codes() );

		$class = new OrderController();
		try {
			$class->validate_order_before_payment( $order );
		} finally {
			$this->assertEmpty( $order->get_coupon_codes() );
		}
	}

	/**
	 * test_validate_existing_order_before_payment_invalid_coupons.
	 */
	public function test_validate_existing_order_before_payment_invalid_coupons() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 409 );
		$this->expectExceptionMessage( '"fake-coupon" was removed from the order. Please enter a valid email at checkout to use coupon code &quot;fake-coupon&quot;.' );

		$order  = WC_Helper_Order::create_order();
		$coupon = CouponHelper::create_coupon( 'fake-coupon', 'publish', array( 'customer_email' => 'random-email@example.com' ) );
		$order->add_coupon( $coupon->get_code() );
		$order->save();
		$this->assertEquals( array( 'fake-coupon' ), $order->get_coupon_codes() );

		try {
			$this->sut->validate_existing_order_before_payment( $order );
		} finally {
			$this->assertEmpty( $order->get_coupon_codes() );
		}
	}

	/**
	 * test_validate_order_before_payment_invalid_email.
	 */
	public function test_validate_order_before_payment_invalid_email() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'A valid email address is required' );

		$order = new \WC_Order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->validate_order_before_payment( $order );
	}

	/**
	 * test_validate_order_before_payment_invalid_addresses.
	 */
	public function test_validate_order_before_payment_invalid_addresses() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Sorry, we do not ship orders to the provided country (Invalid)' );

		$order = WC_Helper_Order::create_order();
		$order->set_shipping_country( 'Invalid' );
		$order->save();

		/** @var \WC_Order_Item_Product $item */
		$array = $order->get_items();
		$item  = reset( $array );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );

		WC()->cart->add_to_cart( $item->get_product()->get_id() );

		$this->sut->validate_order_before_payment( $order );
	}

	/**
	 * test_validate_existing_order_before_payment_invalid_addresses.
	 */
	public function test_validate_existing_order_before_payment_invalid_addresses() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Sorry, we do not ship orders to the provided country (Invalid)' );

		$order = WC_Helper_Order::create_order();
		$order->set_shipping_country( 'Invalid' );
		$order->save();

		// There is no need to update the cart here, we just check the order.
		$this->sut->validate_existing_order_before_payment( $order );
	}

	/**
	 * test_validate_order_before_payment_invalid_billing_country.
	 */
	public function test_validate_order_before_payment_invalid_billing_country() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Sorry, we do not allow orders from the provided country (Invalid)' );

		$order = WC_Helper_Order::create_order();
		$order->set_billing_country( 'Invalid' );
		$this->set_shipping_address( $order );
		$order->save();

		$this->sut->validate_order_before_payment( $order );
	}

	/**
	 * test_validate_order_before_payment_missing_required_billing_fields.
	 */
	public function test_validate_order_before_payment_missing_required_billing_fields() {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'There was a problem with the provided billing address: First name is required, Last name is required' );

		$order = WC_Helper_Order::create_order();
		// Clear required billing fields.
		$order->set_billing_first_name( '' );
		$order->set_billing_last_name( '' );
		$this->set_shipping_address( $order );
		$order->save();

		$this->sut->validate_order_before_payment( $order );
	}

	/**
	 * test_validate_order_before_payment_valid_coupon.
	 */
	public function test_validate_order_before_payment_valid_coupon() {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address( $order );

		// Create a coupon without restrictions.
		$coupon = CouponHelper::create_coupon( 'valid-coupon' );
		$order->apply_coupon( $coupon );
		$order->save();

		$this->sut->validate_order_before_payment( $order );
		$this->assertEquals( array( 'valid-coupon' ), $order->get_coupon_codes() );
	}

	/**
	 * test_validate_address_fields_valid_address.
	 */
	public function test_validate_address_fields_valid_address() {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address( $order );
		$order->save();

		$errors = new \WP_Error();
		$this->sut->validate_address_fields( $order, 'shipping', $errors );

		$this->assertEmpty( $errors->get_error_messages() );
	}

	/**
	 * test_validate_address_fields_invalid_address.
	 */
	public function test_validate_address_fields_invalid_address() {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address(
			$order,
			[
				'postcode' => '',
			]
		);
		$order->save();

		$errors = new \WP_Error();
		$this->sut->validate_address_fields( $order, 'shipping', $errors );
		$this->assertEquals( 'ZIP Code is required', $errors->get_error_message() );
	}
	/**
	 * test_validate_address_fields_invalid_address.
	 */
	public function test_validate_address_fields_required_hidden_fields_not_validates() {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address(
			$order,
			[
				'postcode' => '',
			]
		);
		$order->save();

		/**
		 * Hide the postcode field for US locale.
		 *
		 * @param array $locales All country locales.
		 *
		 * @return array
		 */
		$hide_postcode = function ( $locales ) {
			$locales['US']['postcode']['hidden'] = true;
			return $locales;
		};

		add_filter( 'woocommerce_get_country_locale', $hide_postcode );

		$errors = new \WP_Error();
		$this->sut->validate_address_fields( $order, 'shipping', $errors );
		$this->assertEmpty( $errors->get_error_messages() );
		remove_filter( 'woocommerce_get_country_locale', $hide_postcode );
	}

	/**
	 * Helper method to set shipping address on an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $override_data Optional data to override the default shipping address.
	 */
	private function set_shipping_address( \WC_Order $order, $override_data = [] ) {
		$order->set_shipping_country( 'US' );
		$order->set_shipping_first_name( 'John' );
		$order->set_shipping_last_name( 'Doe' );
		$order->set_shipping_address_1( '123 Test St' );
		$order->set_shipping_city( 'Test City' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '12345' );

		foreach ( $override_data as $key => $value ) {
			$order->{"set_shipping_$key"}( $value );
		}
	}
}

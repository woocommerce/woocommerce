<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use WC_Helper_Order;
use WC_Helper_Product;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;

/**
 * OrderControllerTests class.
 */
class OrderControllerTests extends \WC_Unit_Test_Case {
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

		// The fixtures in this class do not provide phone numbers, so make the
		// phone field optional as other Store API test classes do. Without this
		// the class only passes when run after a class that already did so.
		// The per-test database rollback restores the option.
		update_option( 'woocommerce_checkout_phone_field', 'optional' );

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

		$order       = WC_Helper_Order::create_order();
		$coupon      = CouponHelper::create_coupon( 'fake-coupon', 'publish', array( 'customer_email' => 'random-email@example.com' ) );
		$coupon_item = new \WC_Order_Item_Coupon();
		$coupon_item->set_code( $coupon->get_code() );
		$order->add_item( $coupon_item );
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

		$order       = WC_Helper_Order::create_order();
		$coupon      = CouponHelper::create_coupon( 'fake-coupon', 'publish', array( 'customer_email' => 'random-email@example.com' ) );
		$coupon_item = new \WC_Order_Item_Coupon();
		$coupon_item->set_code( $coupon->get_code() );
		$order->add_item( $coupon_item );
		$order->save();
		$this->assertEquals( array( 'fake-coupon' ), $order->get_coupon_codes() );

		try {
			$this->sut->validate_existing_order_before_payment( $order );
		} finally {
			$this->assertEmpty( $order->get_coupon_codes() );
		}
	}

	/**
	 * @testdox Existing-order validation keeps the coupon when the order already recorded its usage.
	 */
	public function test_validate_existing_order_before_payment_keeps_coupon_when_usage_recorded() {
		$coupon = CouponHelper::create_coupon( 'recorded-coupon', 'publish', array( 'usage_limit' => 1 ) );
		$coupon->increase_usage_count();

		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address( $order );
		$item = new \WC_Order_Item_Coupon();
		$item->set_code( $coupon->get_code() );
		$order->add_item( $item );
		$order->set_recorded_coupon_usage_counts( true );
		$order->save();

		$this->assertNull( $this->sut->validate_existing_order_before_payment( $order ) );
		$this->assertEquals( array( 'recorded-coupon' ), $order->get_coupon_codes() );
	}

	/**
	 * @testdox Stripping an exhausted coupon from a draft does not change its usage count.
	 */
	public function test_validate_existing_order_before_payment_does_not_decrement_usage_count() {
		$coupon = CouponHelper::create_coupon( 'draft-global', 'publish', array( 'usage_limit' => 1 ) );
		$coupon->increase_usage_count();
		$this->assertEquals( 1, ( new \WC_Coupon( 'draft-global' ) )->get_usage_count() );

		$order = WC_Helper_Order::create_order();
		$item  = new \WC_Order_Item_Coupon();
		$item->set_code( $coupon->get_code() );
		$order->add_item( $item );
		$order->save();

		try {
			$this->sut->validate_existing_order_before_payment( $order );
			$this->fail( 'Expected a RouteException for the exhausted coupon.' );
		} catch ( RouteException $e ) {
			$this->assertEquals( 409, $e->getCode() );
		}

		$this->assertEmpty( $order->get_coupon_codes() );
		$this->assertEquals( 1, ( new \WC_Coupon( 'draft-global' ) )->get_usage_count(), 'usage_count must not be decremented for a draft that never recorded it' );
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

		// validate_addresses() inspects the selected shipping rates from the global cart's
		// packages even for existing orders, so the cart must contain the shippable product
		// for the shipping country check to run.
		/** @var \WC_Order_Item_Product $item */
		$array = $order->get_items();
		$item  = reset( $array );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );

		WC()->cart->add_to_cart( $item->get_product()->get_id() );

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
	 * @testdox create_order_from_cart() removes its woocommerce_default_order_status filter even when the order update throws.
	 */
	public function test_create_order_from_cart_removes_default_order_status_filter_on_exception(): void {
		$hook           = 'woocommerce_default_order_status';
		$filters_before = has_filter( $hook );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		$this->assertFalse( WC()->cart->is_empty(), 'The cart must be non-empty so create_order_from_cart() reaches the filter logic instead of throwing for an empty cart.' );

		$thrower = static function () {
			throw new \RuntimeException( 'Forced failure during totals calculation.' );
		};
		add_action( 'woocommerce_before_calculate_totals', $thrower );

		$threw = false;
		try {
			$this->sut->create_order_from_cart();
		} catch ( \Throwable $e ) {
			$threw = true;
		} finally {
			remove_action( 'woocommerce_before_calculate_totals', $thrower );
		}

		$this->assertTrue( $threw, 'The injected exception should propagate out of create_order_from_cart().' );
		$this->assertSame(
			$filters_before,
			has_filter( $hook ),
			'create_order_from_cart() must remove the woocommerce_default_order_status filter even when the order update throws.'
		);
	}

	/**
	 * @testdox create_order_from_cart() leaves no woocommerce_default_order_status callbacks registered, so the filter chain does not grow across calls.
	 */
	public function test_create_order_from_cart_removes_default_order_status_filter(): void {
		$hook           = 'woocommerce_default_order_status';
		$filters_before = has_filter( $hook );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		$this->assertFalse( WC()->cart->is_empty(), 'The cart must be non-empty so create_order_from_cart() runs to completion.' );

		$this->sut->create_order_from_cart();
		$this->sut->create_order_from_cart();

		$this->assertSame(
			$filters_before,
			has_filter( $hook ),
			'create_order_from_cart() must remove its woocommerce_default_order_status filter; the chain must not grow across repeated calls.'
		);
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
		$order->set_shipping_phone( '555-32123' );

		foreach ( $override_data as $key => $value ) {
			$order->{"set_shipping_$key"}( $value );
		}
	}
}

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
	 * Shipping address fields in a country a US-only store does not ship to.
	 */
	private const GERMAN_SHIPPING_ADDRESS = array(
		'country'  => 'DE',
		'state'    => '',
		'city'     => 'Berlin',
		'postcode' => '10115',
	);

	/**
	 * The system under test.
	 *
	 * @var OrderController
	 */
	private $sut;

	/**
	 * Customer shipping address before the test, restored at teardown.
	 *
	 * @var array<string, string>
	 */
	private $previous_shipping_address = array();

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

		// The invalid-address tests need a shipping rate, and the flat rate only offers one for a
		// country the store ships to. The cart ships to WC()->customer, which outlives a test, so
		// give it a US/CA location here rather than depend on the address it arrives with.
		$this->previous_shipping_address = WC()->customer->get_shipping( 'edit' );
		WC()->customer->set_shipping_location( 'US', 'CA' );

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
	 * Put back the customer shipping location that setUp() replaced.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->previous_shipping_address as $key => $value ) {
				WC()->customer->{"set_shipping_{$key}"}( $value );
			}
		} finally {
			parent::tearDown();
		}
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
	 * The guard exists because an address the cart judges incomplete leaves no packages for
	 * validate_selected_shipping_methods() to inspect, so without it an order is placed with no
	 * shipping line. It mirrors WC_Checkout::is_shipping_withheld_for_missing_address().
	 *
	 * @testdox validate_order_before_payment() refuses an order whose shipping was withheld.
	 */
	public function test_validate_order_before_payment_refuses_withheld_shipping(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->add_flat_rate_to_default_zone();
		$this->add_product_to_cart_without_rating_shipping();

		// The message, not just the type: validate_selected_shipping_methods() throws the same
		// RouteException with the same error code, so only the wording tells the two apart.
		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'Please enter an address to continue.' );
		$this->sut->validate_order_before_payment( $this->create_order_with_full_shipping_address() );
	}

	/**
	 * The case that matters most in production: once the cart has rated an address, nothing may
	 * refuse the order, or every checkout on a store with the option enabled would fail.
	 *
	 * @testdox validate_order_before_payment() allows an order whose cart has rated the address.
	 */
	public function test_validate_order_before_payment_allows_a_rated_cart(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->add_flat_rate_to_default_zone( '25.00' );

		// A complete customer address, so the cart rates the package and picks the rate itself.
		WC()->customer->set_shipping_city( 'San Francisco' );
		WC()->customer->set_shipping_postcode( '94103' );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertTrue(
			WC()->cart->has_calculated_shipping(),
			'The cart must have rated the address, or this test proves nothing.'
		);

		$this->assertNull( $this->sut->validate_order_before_payment( $this->create_order_with_full_shipping_address() ) );
	}

	/**
	 * With the option off, shipping is never withheld for the address, so a cart with no rate is
	 * a store with no method configured, not a shopper slipping past one.
	 *
	 * @testdox validate_order_before_payment() allows an unrated cart when the option is off.
	 */
	public function test_validate_order_before_payment_allows_an_unrated_cart_when_the_option_is_off(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );

		// Deliberately no shipping method: with the option off that is the only way a cart that
		// needs shipping reaches checkout with nothing rated.
		$this->add_product_to_cart_without_rating_shipping();

		$this->assertNull( $this->sut->validate_order_before_payment( $this->create_order_with_full_shipping_address() ) );
	}

	/**
	 * @testdox validate_order_before_payment() allows a cart that does not need shipping.
	 */
	public function test_validate_order_before_payment_allows_a_cart_that_does_not_need_shipping(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->add_flat_rate_to_default_zone();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( true );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertFalse( WC()->cart->needs_shipping() );

		$this->assertNull( $this->sut->validate_order_before_payment( $this->create_order_with_full_shipping_address() ) );
	}

	/**
	 * A store that switches shipping calculation off through the filter withholds rates for its
	 * own reasons, so the order must go through, as it does on the classic checkout.
	 *
	 * @testdox validate_order_before_payment() allows an order when the filter stops calculation.
	 */
	public function test_validate_order_before_payment_allows_shipping_calculation_disabled_by_filter(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->add_flat_rate_to_default_zone();

		$filter = function () {
			return false;
		};
		add_filter( 'woocommerce_cart_ready_to_calc_shipping', $filter );

		try {
			$this->add_product_to_cart_without_rating_shipping();
			$this->assertNull( $this->sut->validate_order_before_payment( $this->create_order_with_full_shipping_address() ) );
		} finally {
			remove_filter( 'woocommerce_cart_ready_to_calc_shipping', $filter );
		}
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

		$this->sut->validate_existing_order_before_payment( $order );
	}

	/**
	 * @testdox Existing-order validation decides local pickup from the order's shipping lines, not from the cart.
	 *
	 * @dataProvider provide_order_shipping_lines
	 *
	 * @param string[] $method_ids      Method ids of the shipping lines on the order.
	 * @param bool     $expect_rejected Whether the shipping country should be rejected.
	 */
	public function test_validate_existing_order_shipping_country_uses_order_shipping_lines( array $method_ids, bool $expect_rejected ): void {
		$order = $this->create_order_for_us_only_store( $method_ids, self::GERMAN_SHIPPING_ADDRESS );

		if ( $expect_rejected ) {
			$this->expectException( RouteException::class );
			$this->expectExceptionMessage( 'Sorry, we do not ship orders to the provided country (Germany)' );
		}

		$this->assertNull( $this->sut->validate_existing_order_before_payment( $order ) );
	}

	/**
	 * Shipping lines on an order and whether a country the store does not ship to should be rejected.
	 *
	 * @return array<string, array{0: string[], 1: bool}>
	 */
	public function provide_order_shipping_lines(): array {
		return array(
			'flat rate'                  => array( array( 'flat_rate' ), true ),
			'no shipping line'           => array( array(), true ),
			'local pickup and flat rate' => array( array( 'local_pickup', 'flat_rate' ), true ),
			'local pickup'               => array( array( 'local_pickup' ), false ),
			'pickup location'            => array( array( 'pickup_location' ), false ),
			'legacy local pickup'        => array( array( 'legacy_local_pickup' ), false ),
		);
	}

	/**
	 * @testdox Validating an existing order before an update rejects a shipping country the store does not ship to.
	 */
	public function test_validate_existing_order_before_update_rejects_shipping_country_the_store_does_not_ship_to(): void {
		$order = $this->create_order_for_us_only_store( array( 'flat_rate' ), self::GERMAN_SHIPPING_ADDRESS );

		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'Sorry, we do not ship orders to the provided country (Germany)' );

		$this->sut->validate_existing_order_before_update( $order );
	}

	/**
	 * @testdox Updating an existing order's addresses rejects a shipping country the store does not ship to.
	 */
	public function test_update_existing_order_addresses_rejects_shipping_country_the_store_does_not_ship_to(): void {
		$order = $this->create_order_for_us_only_store( array( 'flat_rate' ) );

		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'Sorry, we do not ship orders to the provided country (Germany)' );

		$this->sut->update_existing_order_addresses(
			$order,
			$order->get_address( 'billing' ),
			array_merge( $order->get_address( 'shipping' ), self::GERMAN_SHIPPING_ADDRESS )
		);
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
	 * @testdox validate_address_fields() enforces the default required fields when the default locale entry is missing or not an array.
	 *
	 * @dataProvider provide_broken_default_locale_entries
	 *
	 * @param mixed $default_entry Value to put in the default locale entry, or null to remove the entry.
	 */
	public function test_validate_address_fields_enforces_default_fields_without_a_default_locale_entry( $default_entry ): void {
		$countries = wc()->countries;
		$countries->get_country_locale();
		if ( null === $default_entry ) {
			unset( $countries->locale['default'] );
		} else {
			$countries->locale['default'] = $default_entry;
		}
		$order = WC_Helper_Order::create_order();
		$order->set_billing_postcode( '' );
		$errors = new \WP_Error();

		try {
			$this->sut->validate_address_fields( $order, 'billing', $errors );
		} finally {
			// Drop the broken settings so the next lookup rebuilds them.
			$countries->locale = array();
		}

		$this->assertCount( 1, $errors->get_error_messages( 'billing' ), 'Only the missing default field should be reported.' );
		$this->assertSame( 'postcode', $errors->get_error_data( 'billing' ), 'The default required fields should still be enforced.' );
	}

	/**
	 * Broken values for the default entry of the country locale settings.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provide_broken_default_locale_entries(): array {
		return array(
			'entry removed'      => array( null ),
			'entry not an array' => array( false ),
		);
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
	 * @testdox add_flat_rate_to_default_zone() adds a flat rate to the default shipping zone.
	 */

	/**
	 * Add a flat rate to the catch-all zone, optionally with a cost so it actually offers a rate.
	 *
	 * @param string $cost Flat rate cost. The default leaves the rate off, as an unconfigured method is.
	 */
	private function add_flat_rate_to_default_zone( string $cost = '' ): void {
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		$default_zone->add_shipping_method( 'flat_rate' );
		$default_zone->save();

		if ( '' === $cost ) {
			return;
		}

		$zone_methods                         = \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods();
		$flat_rate                            = reset( $zone_methods );
		$flat_rate->instance_settings['cost'] = $cost;
		update_option( $flat_rate->get_instance_option_key(), $flat_rate->instance_settings );
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();
	}

	/**
	 * Put the cart in the state the bug produced: a shippable product, a method left in the
	 * session by an earlier request, and no package ever rated, so the shipping-method check has
	 * nothing to reject.
	 */
	private function add_product_to_cart_without_rating_shipping(): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$this->assertFalse( WC()->cart->has_calculated_shipping() );

		// Any value will do: with no packages calculated, only its presence is checked.
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );
	}

	/**
	 * An order carrying the complete address validate_addresses() asks for, so the shipping guard
	 * is the only thing left that can refuse it.
	 */
	private function create_order_with_full_shipping_address(): \WC_Order {
		$order = WC_Helper_Order::create_order();
		$this->set_shipping_address( $order );
		$order->save();

		return $order;
	}

	/**
	 * Creates a saved order that needs shipping, for a store that only ships to the US and has an empty cart.
	 *
	 * @param string[]             $method_ids       Method ids of the shipping lines on the order.
	 * @param array<string,string> $shipping_address Shipping address fields to override on the order.
	 * @return \WC_Order
	 */
	private function create_order_for_us_only_store( array $method_ids, array $shipping_address = array() ): \WC_Order {
		update_option( 'woocommerce_ship_to_countries', 'specific' );
		update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );
		WC()->cart->empty_cart();

		$order = WC_Helper_Order::create_order();
		$order->remove_order_items( 'shipping' );
		foreach ( $method_ids as $method_id ) {
			$shipping_line = new \WC_Order_Item_Shipping();
			$shipping_line->set_method_id( $method_id );
			$order->add_item( $shipping_line );
		}
		$this->set_shipping_address( $order, $shipping_address );
		$order->save();
		$this->assertTrue( $order->needs_shipping(), 'The order must need shipping for the country check to apply.' );

		return $order;
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

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
		// country the store ships to. The cart ships to WC()->customer, so give it a US/CA location.
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
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wpdb;

		try {
			$this->unlock_orders();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( OrderController::ORDER_LOCK_OPTION_PREFIX ) . '%' ) );
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
	 * @testdox Existing-order validation removes an invalid coupon line that has not been saved yet.
	 */
	public function test_validate_existing_order_before_payment_removes_unsaved_invalid_coupon() {
		$coupon = CouponHelper::create_coupon( 'unsaved-exhausted', 'publish', array( 'usage_limit' => 1 ) );
		$coupon->increase_usage_count();

		$order = WC_Helper_Order::create_order();
		$item  = new \WC_Order_Item_Coupon();
		$item->set_code( $coupon->get_code() );
		$order->add_item( $item );

		try {
			$this->sut->validate_existing_order_before_payment( $order );
			$this->fail( 'Expected a RouteException for the exhausted coupon.' );
		} catch ( RouteException $e ) {
			$this->assertEquals( 409, $e->getCode() );
		}

		$this->assertEmpty( $order->get_coupon_codes(), 'The coupon line should be removed from the order.' );
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_coupon_codes(), 'The coupon line should not be saved.' );
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
	 * @testdox update_order_from_cart() rebuilds line items from a changed cart even when an extension set the order's cart hash to match it.
	 */
	public function test_update_order_from_cart_rebuilds_line_items_when_an_extension_set_the_cart_hash(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		CouponHelper::create_coupon(
			'ten-percent',
			'publish',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '10',
			)
		);
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );

		$order = $this->sut->create_order_from_cart();
		$order->save();
		$this->assertSame( 10.0, (float) $order->get_total(), 'The order should start at the undiscounted product price.' );

		WC()->cart->apply_coupon( 'ten-percent' );
		WC()->cart->calculate_totals();
		// Subscriptions sets the cart hash of the renewal order it resumes on every cart load, before checkout syncs the order.
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();

		$this->sut->update_order_from_cart( $order );

		$line_items = $order->get_items();
		$this->assertSame( 9.0, (float) reset( $line_items )->get_total(), 'The line item should be rebuilt with the coupon discount.' );
		$this->assertSame( 9.0, (float) $order->get_total(), 'The order total should include the coupon discount.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps unchanged line items, but still updates the cart hash when only the cart total changes.
	 */
	public function test_update_order_from_cart_keeps_line_items_and_updates_cart_hash_when_only_the_total_changes(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );

		$order = $this->sut->create_order_from_cart();
		$order->save();
		$line_item_ids = array_keys( $order->get_items() );

		$add_fee = static function ( \WC_Cart $cart ) {
			$cart->add_fee( 'Handling', 5 );
		};
		add_action( 'woocommerce_cart_calculate_fees', $add_fee );

		try {
			$this->sut->update_order_from_cart( $order );
			$order->save();
		} finally {
			remove_action( 'woocommerce_cart_calculate_fees', $add_fee );
		}

		$this->assertSame( $line_item_ids, array_keys( $order->get_items() ), 'Line items should be kept when the cart contents did not change.' );
		$this->assertSame( 15.0, (float) $order->get_total(), 'The order total should include the new fee.' );
		$this->assertTrue( $order->has_cart_hash( WC()->cart->get_cart_hash() ), 'The cart hash should follow the cart total so checkout can resume the order.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps every item of an unchanged retried order taxed at two rates.
	 *
	 * @testWith ["12.34", "yes", "yes"]
	 *           ["19.99", "yes", "no"]
	 *           ["23.45", "no", "yes"]
	 *           ["19.99", "no", "no"]
	 *
	 * @param string $price              The product price.
	 * @param string $prices_include_tax Whether prices include tax.
	 * @param string $round_at_subtotal  Whether tax is rounded at subtotal.
	 */
	public function test_update_order_from_cart_keeps_items_of_an_unchanged_retried_order( string $price, string $prices_include_tax, string $round_at_subtotal ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', $prices_include_tax );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		foreach ( array( '7.2500', '1.5000' ) as $priority => $tax_rate ) {
			\WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => '',
					'tax_rate'          => $tax_rate,
					'tax_rate_name'     => "Rate {$priority}",
					'tax_rate_priority' => (string) ( $priority + 1 ),
					'tax_rate_compound' => '0',
					'tax_rate_shipping' => '1',
					'tax_rate_order'    => (string) ( $priority + 1 ),
					'tax_rate_class'    => '',
				)
			);
		}
		$coupon = \WC_Helper_Coupon::create_coupon(
			'retry-two-rates',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '15',
			)
		);
		$order  = $this->create_retried_order_with_shipping_and_fee( $price, 3.33, $coupon->get_code() );
		$before = $this->get_item_ids_by_type( $order );
		$calls  = did_action( 'woocommerce_checkout_create_order_line_item' ) + did_action( 'woocommerce_checkout_create_order_shipping_item' ) + did_action( 'woocommerce_checkout_create_order_fee_item' );

		$order = $this->sync_order_in_a_new_request( $order );

		$this->assertNotEmpty( $before['coupon'], 'The order should have a coupon line.' );
		$this->assertCount( 2, $before['tax'], 'The order should have a line for each tax rate.' );
		$this->assertSame( $before, $this->get_item_ids_by_type( $order ), 'Every item should keep its ID.' );
		$this->assertSame( $calls, did_action( 'woocommerce_checkout_create_order_line_item' ) + did_action( 'woocommerce_checkout_create_order_shipping_item' ) + did_action( 'woocommerce_checkout_create_order_fee_item' ), 'No item should be created again.' );
	}

	/**
	 * @testdox update_order_from_cart() rebuilds the items a merchant edited on a retried order, so the order total matches the cart.
	 *
	 * @testWith ["shipping"]
	 *           ["fee"]
	 *           ["line_item"]
	 *
	 * @param string $item_type The item type the merchant edits.
	 */
	public function test_update_order_from_cart_rebuilds_items_a_merchant_edited( string $item_type ): void {
		$order  = $this->create_retried_order_with_shipping_and_fee();
		$before = $this->get_item_ids_by_type( $order );

		// Like editing the item in the admin and saving it without recalculating the order.
		$item = current( $order->get_items( $item_type ) );
		$item->set_total( (float) $item->get_total() + 5 );
		$item->save();

		$order = $this->sync_order_in_a_new_request( $order );
		$after = $this->get_item_ids_by_type( $order );

		$this->assertCount( 1, $after[ $item_type ], "The {$item_type} line should be added again once." );
		$this->assertNotContains( $item->get_id(), $after[ $item_type ], "The edited {$item_type} line should be replaced." );
		foreach ( array_diff( array( 'line_item', 'shipping', 'fee' ), array( $item_type ) ) as $other_type ) {
			$this->assertSame( $before[ $other_type ], $after[ $other_type ], "The {$other_type} lines should keep their IDs." );
		}
		$this->assertSame( (float) WC()->cart->get_total( 'edit' ), (float) $order->get_total(), 'The order total should match the cart.' );
		$this->assertSame( 25.0, (float) $order->get_total(), 'The order should be charged the cart total, not the edited amount.' );
	}

	/**
	 * @testdox update_order_from_cart() adds back the coupon a merchant removed from a retried order.
	 */
	public function test_update_order_from_cart_adds_back_a_coupon_removed_from_a_retried_order(): void {
		$coupon = \WC_Helper_Coupon::create_coupon( 'retry10', array( 'coupon_amount' => 5 ) );
		$order  = $this->create_retried_order_with_shipping_and_fee( '10', 5, $coupon->get_code() );

		$order->remove_coupon( $coupon->get_code() );
		$order->save();
		$this->assertEmpty( $order->get_coupons(), 'Removing the coupon should remove its line.' );

		$order = $this->sync_order_in_a_new_request( $order );

		$this->assertSame( array( $coupon->get_code() ), $order->get_coupon_codes(), 'The coupon line should be added back.' );
		$this->assertSame( (float) WC()->cart->get_total( 'edit' ), (float) $order->get_total(), 'The order total should match the cart.' );
		$this->assertSame( 20.0, (float) $order->get_total(), 'The order should be charged the discounted total.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps repaired items, and items an extension added, on the next unchanged sync.
	 */
	public function test_update_order_from_cart_keeps_repaired_and_extension_items_on_the_next_sync(): void {
		$order = $this->create_retried_order_with_shipping_and_fee();

		$extension_fee = new \WC_Order_Item_Fee();
		$extension_fee->set_name( 'Extension surcharge' );
		$extension_fee->set_total( '2' );
		$order->add_item( $extension_fee );
		$order->save();

		$shipping = current( $order->get_shipping_methods() );
		$shipping->set_total( '15' );
		$shipping->save();

		$order    = $this->sync_order_in_a_new_request( $order );
		$repaired = $this->get_item_ids_by_type( $order );
		$this->assertNotContains( $shipping->get_id(), $repaired['shipping'], 'The edited shipping line should be replaced.' );
		$this->assertContains( $extension_fee->get_id(), $repaired['fee'], 'The extension fee should be kept.' );

		$order = $this->sync_order_in_a_new_request( $order );

		$this->assertSame( $repaired, $this->get_item_ids_by_type( $order ), 'Every item should keep its ID on the next sync.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps the items of an order without a snapshot, and rebuilds them once they are edited after that.
	 */
	public function test_update_order_from_cart_starts_checking_edits_on_an_order_without_a_snapshot(): void {
		$order = $this->create_retried_order_with_shipping_and_fee();
		$order->delete_meta_data( '_store_api_cart_order_items_snapshot' );
		$order->save();
		$before = $this->get_item_ids_by_type( $order );

		$order = $this->sync_order_in_a_new_request( $order );
		$this->assertSame( $before, $this->get_item_ids_by_type( $order ), 'Every item should keep its ID.' );

		$fee = current( $order->get_fees() );
		$fee->set_total( '15' );
		$fee->save();
		$order = $this->sync_order_in_a_new_request( $order );

		$this->assertNotContains( $fee->get_id(), array_keys( $order->get_fees() ), 'The edited fee should be replaced.' );
		$this->assertSame( (float) WC()->cart->get_total( 'edit' ), (float) $order->get_total(), 'The order total should match the cart.' );
	}

	/**
	 * Creates a pending Store API order from a cart with one product, a fee and flat rate shipping, as a failed payment
	 * leaves it.
	 *
	 * @param string      $price       The product price.
	 * @param float       $fee         The fee amount.
	 * @param string|null $coupon_code A coupon to apply, if any.
	 * @return \WC_Order
	 */
	private function create_retried_order_with_shipping_and_fee( string $price = '10', float $fee = 5, ?string $coupon_code = null ): \WC_Order {
		$this->add_flat_rate_to_default_zone( '10' );
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( \WC_Cart $cart ) use ( $fee ) {
				$cart->add_fee( 'Handling', $fee, true );
			}
		);
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $price ) );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		if ( $coupon_code ) {
			WC()->cart->apply_coupon( $coupon_code );
		}
		WC()->cart->calculate_totals();
		$this->assertTrue( WC()->cart->has_calculated_shipping(), 'The cart should have calculated shipping.' );

		$order = $this->sut->create_order_from_cart();
		$order->update_status( OrderStatus::PENDING );
		$this->assertCount( 1, $order->get_shipping_methods(), 'The order should have a shipping line.' );
		$this->assertCount( 1, $order->get_fees(), 'The order should have a fee.' );

		return $order;
	}

	/**
	 * Reads the order again, as the next checkout request does, and syncs it with the cart.
	 *
	 * @param \WC_Order $order The order.
	 * @return \WC_Order The synced order.
	 */
	private function sync_order_in_a_new_request( \WC_Order $order ): \WC_Order {
		wp_cache_flush();
		$order = wc_get_order( $order->get_id() );
		$this->sut->update_order_from_cart( $order );

		return $order;
	}

	/**
	 * Reads an order's item IDs, grouped by item type.
	 *
	 * @param \WC_Order $order The order.
	 * @return array<string, int[]>
	 */
	private function get_item_ids_by_type( \WC_Order $order ): array {
		$ids_by_type = array();
		foreach ( array( 'line_item', 'shipping', 'fee', 'tax', 'coupon' ) as $type ) {
			$ids_by_type[ $type ] = array_keys( $order->get_items( $type ) );
		}

		return $ids_by_type;
	}

	/**
	 * @testdox update_order_from_cart() keeps the shopper's session choice on the order even when the default payment method filter returns nothing.
	 */
	public function test_update_order_from_cart_keeps_the_session_payment_choice_with_a_callback(): void {
		$order = $this->sync_order_from_cart_with_cod_enabled( 'cod' );

		$this->assertSame( 'cod', $order->get_payment_method() );
	}

	/**
	 * @testdox update_order_from_cart() sets the payment method title alongside the id, matching the gateway's own title.
	 */
	public function test_update_order_from_cart_sets_payment_method_title_with_the_session_choice(): void {
		$order = $this->sync_order_from_cart_with_cod_enabled( 'cod' );

		$this->assertSame( WC()->payment_gateways()->payment_gateways()['cod']->get_title(), $order->get_payment_method_title() );
	}

	/**
	 * @testdox update_order_from_cart() writes the filtered default on the order when the shopper has not chosen a method.
	 */
	public function test_update_order_from_cart_writes_the_filtered_default_without_a_session_choice(): void {
		$order = $this->sync_order_from_cart_with_cod_enabled( null );

		$this->assertSame( '', $order->get_payment_method() );
	}

	/**
	 * @testdox update_order_from_cart() still pairs the title with the id when the chosen gateway is registered but disabled.
	 */
	public function test_update_order_from_cart_sets_payment_method_title_for_a_disabled_registered_gateway(): void {
		$bacs                = WC()->payment_gateways()->payment_gateways()['bacs'];
		$bacs_enabled_before = $bacs->enabled;
		$bacs->enabled       = 'no';
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		try {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );
			$order = WC_Helper_Order::create_order();
			$order->set_payment_method_title( 'Stale Title' );

			$this->sut->update_order_from_cart( $order );

			$this->assertSame( 'bacs', $order->get_payment_method() );
			$this->assertSame( $bacs->get_title(), $order->get_payment_method_title() );
		} finally {
			WC()->cart->empty_cart();
			$bacs->enabled = $bacs_enabled_before;
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * Sync an order from a one-item cart with COD enabled, the given session choice and a callback that returns nothing.
	 *
	 * Gateway instances and the session choice are restored afterwards; the base class does not reset them.
	 *
	 * @param string|null $chosen_payment_method What the shopper chose, or null for nothing.
	 * @return \WC_Order
	 */
	private function sync_order_from_cart_with_cod_enabled( ?string $chosen_payment_method ): \WC_Order {
		$gateways_before         = WC()->payment_gateways()->payment_gateways;
		$cod_settings            = (array) get_option( 'woocommerce_cod_settings', array() );
		$cod_settings['enabled'] = 'yes';
		update_option( 'woocommerce_cod_settings', $cod_settings );
		WC()->payment_gateways()->init();
		WC()->session->set( 'chosen_payment_method', $chosen_payment_method );
		add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );

		try {
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( WC_Helper_Product::create_simple_product()->get_id() );
			$order = WC_Helper_Order::create_order();

			$this->sut->update_order_from_cart( $order );

			return $order;
		} finally {
			WC()->cart->empty_cart();
			wp_cache_delete( 'woocommerce_cod_settings', 'options' );
			WC()->payment_gateways()->payment_gateways = $gateways_before;
			WC()->session->set( 'chosen_payment_method', null );
		}
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
	 * @testdox Order locks are re-entrant within a request and released by their owner.
	 */
	public function test_order_lock_is_reentrant_and_released(): void {
		global $wpdb;

		$order_id    = 123;
		$option_name = OrderController::ORDER_LOCK_OPTION_PREFIX . $order_id;

		$this->assertTrue( OrderController::lock_order( $order_id, 0 ), 'The first acquisition should lock the order.' );
		$shutdown_callback = $this->get_order_lock_shutdown_callback();
		$this->assertInstanceOf( \Closure::class, $shutdown_callback );
		$this->assertSame( PHP_INT_MAX, has_action( 'shutdown', $shutdown_callback ), 'The lock should be released after session data is persisted.' );
		$first_expiry = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) );

		$this->assertTrue( OrderController::lock_order( $order_id, 0 ), 'The same request should retain ownership.' );
		$renewed_expiry = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) );
		$this->assertGreaterThan( (float) $first_expiry, (float) $renewed_expiry, 'Re-entering the lock should renew its lease.' );

		$this->unlock_orders();

		$this->assertNull(
			$wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) ),
			'Releasing the lock should remove its options-table row.'
		);
		$this->assertFalse( has_action( 'shutdown', $shutdown_callback ), 'Manual cleanup should remove the shutdown callback.' );
	}

	/**
	 * @testdox A live order lock owned by another request blocks acquisition.
	 */
	public function test_order_lock_rejects_another_owner(): void {
		global $wpdb;

		$order_id = 456;
		$inserted = $wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => OrderController::ORDER_LOCK_OPTION_PREFIX . $order_id,
				'option_value' => number_format( microtime( true ) + 60, 6, '.', '' ),
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$this->assertSame( 1, $inserted, 'The competing request lock should be inserted.' );
		$this->assertFalse( OrderController::lock_order( $order_id, 0 ), 'The active lock should prevent acquisition.' );
	}

	/**
	 * @testdox An expired order lock can be taken over and released by the new owner.
	 */
	public function test_order_lock_takes_over_expired_lock(): void {
		global $wpdb;

		$order_id    = 789;
		$option_name = OrderController::ORDER_LOCK_OPTION_PREFIX . $order_id;
		$inserted    = $wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => $option_name,
				'option_value' => number_format( microtime( true ) - 1, 6, '.', '' ),
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$this->assertSame( 1, $inserted, 'The stale lock should be inserted.' );
		$this->assertTrue( OrderController::lock_order( $order_id, 0 ), 'The expired lock should be available for takeover.' );
		$this->assertGreaterThan(
			microtime( true ),
			(float) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) ),
			'The takeover should store a new future expiry.'
		);

		$this->unlock_orders();

		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) ) );
	}

	/**
	 * @testdox Releasing a stale lock does not delete a replacement owner's lock.
	 */
	public function test_unlock_does_not_delete_replacement_lock(): void {
		global $wpdb;

		$order_id          = 987;
		$option_name       = OrderController::ORDER_LOCK_OPTION_PREFIX . $order_id;
		$replacement_value = number_format( microtime( true ) + 120, 6, '.', '' );

		$this->assertTrue( OrderController::lock_order( $order_id, 0 ) );
		$this->assertSame(
			1,
			$wpdb->update(
				$wpdb->options,
				array( 'option_value' => $replacement_value ),
				array( 'option_name' => $option_name ),
				array( '%s' ),
				array( '%s' )
			),
			'The replacement request should take ownership of the row.'
		);
		$this->assertFalse( OrderController::lock_order( $order_id, 0 ), 'The stale owner should not renew a replacement owner\'s lock.' );

		$this->unlock_orders();

		$this->assertSame(
			$replacement_value,
			$wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name ) ),
			'Releasing the stale owner should preserve the replacement lock.'
		);
	}

	/**
	 * Get the registered order-lock shutdown callback.
	 *
	 * @return \Closure|null
	 */
	private function get_order_lock_shutdown_callback(): ?\Closure {
		$property = new \ReflectionProperty( OrderController::class, 'unlock_orders_on_shutdown' );
		$property->setAccessible( true );

		return $property->getValue();
	}

	/**
	 * Release order locks registered by the current test.
	 */
	private function unlock_orders(): void {
		$callback = $this->get_order_lock_shutdown_callback();
		if ( $callback ) {
			$callback();
		}
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

	/**
	 * Build a saved order for a customer and billing email that carries a coupon, without recording its usage.
	 *
	 * @param int    $customer_id   Customer ID, 0 for a guest.
	 * @param string $billing_email Billing email.
	 * @param string $code          Coupon code.
	 * @return \WC_Order
	 */
	private function create_order_with_coupon( int $customer_id, string $billing_email, string $code ): \WC_Order {
		$order = WC_Helper_Order::create_order( $customer_id );
		$order->set_billing_email( $billing_email );
		$this->set_shipping_address( $order );
		$item = new \WC_Order_Item_Coupon();
		$item->set_code( $code );
		$order->add_item( $item );
		$order->save();
		return $order;
	}

	/**
	 * Run payment validation and return the coupon error message, or null when the coupon was kept.
	 *
	 * @param \WC_Order $order Order to validate.
	 * @return string|null
	 */
	private function get_coupon_error( \WC_Order $order ): ?string {
		try {
			$this->sut->validate_order_before_payment( $order );
			return null;
		} catch ( RouteException $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * @testdox A guest is refused a per-user coupon with the email of an account that used it, after that account changes its email.
	 */
	public function test_validate_coupon_usage_limit_after_account_email_change(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'vip-store@example.com' ) );
		$coupon  = CouponHelper::create_coupon(
			'vip-store',
			'publish',
			array(
				'customer_email'       => array( 'vip-store@example.com' ),
				'usage_limit_per_user' => 1,
			)
		);

		$first_order = $this->create_order_with_coupon( $user_id, 'invoices@example.com', 'vip-store' );
		$coupon->increase_usage_count( (string) $user_id, $first_order );

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'changed-store@example.com',
			)
		);

		$error = $this->get_coupon_error( $this->create_order_with_coupon( 0, 'vip-store@example.com', 'vip-store' ) );

		$this->assertStringContainsString( 'Usage limit for coupon', (string) $error );
	}

	/**
	 * @testdox A second account is refused a per-user coupon billed to an email already used, after the first customer edits their saved billing email.
	 */
	public function test_validate_coupon_usage_limit_after_saved_billing_email_change(): void {
		$first_id  = $this->factory->user->create( array( 'user_email' => 'first-store@example.com' ) );
		$second_id = $this->factory->user->create( array( 'user_email' => 'second-store@example.com' ) );
		$coupon    = CouponHelper::create_coupon( 'billing-store', 'publish', array( 'usage_limit_per_user' => 1 ) );

		$first_order = $this->create_order_with_coupon( $first_id, 'shared-store@example.com', 'billing-store' );
		$coupon->increase_usage_count( (string) $first_id, $first_order );
		update_user_meta( $first_id, 'billing_email', 'moved-store@example.com' );

		$second_error = $this->get_coupon_error( $this->create_order_with_coupon( $second_id, 'shared-store@example.com', 'billing-store' ) );
		$guest_error  = $this->get_coupon_error( $this->create_order_with_coupon( 0, 'shared-store@example.com', 'billing-store' ) );

		$this->assertStringContainsString( 'Usage limit for coupon', (string) $second_error );
		$this->assertStringContainsString( 'Usage limit for coupon', (string) $guest_error );
	}

	/**
	 * @testdox A per-user limit of two still accepts a second use by a guest with the email of an account that used it once.
	 */
	public function test_validate_coupon_usage_limit_allows_uses_up_to_limit(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'two-store@example.com' ) );
		$coupon  = CouponHelper::create_coupon( 'two-store', 'publish', array( 'usage_limit_per_user' => 2 ) );
		$coupon->increase_usage_count( (string) $user_id, $this->create_order_with_coupon( $user_id, 'two-store@example.com', 'two-store' ) );

		$this->assertNull( $this->get_coupon_error( $this->create_order_with_coupon( 0, 'two-store@example.com', 'two-store' ) ) );
	}

	/**
	 * @testdox A second account is refused a per-user coupon when it bills to the account email of a customer who used it.
	 */
	public function test_validate_coupon_usage_limit_second_account_billing_to_used_account_email(): void {
		$first_id  = $this->factory->user->create( array( 'user_email' => 'owner-store@example.com' ) );
		$second_id = $this->factory->user->create( array( 'user_email' => 'other-store@example.com' ) );
		$coupon    = CouponHelper::create_coupon( 'owner-store', 'publish', array( 'usage_limit_per_user' => 1 ) );
		$coupon->increase_usage_count( (string) $first_id, $this->create_order_with_coupon( $first_id, 'receipts-store@example.com', 'owner-store' ) );

		$error = $this->get_coupon_error( $this->create_order_with_coupon( $second_id, 'owner-store@example.com', 'owner-store' ) );

		$this->assertStringContainsString( 'Usage limit for coupon', (string) $error );
	}
}

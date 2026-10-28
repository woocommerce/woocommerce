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

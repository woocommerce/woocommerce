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
	 * @testdox update_order_from_cart() replaces line items a merchant edited, even though the order's cart hash still matches the cart.
	 */
	public function test_update_order_from_cart_replaces_line_items_that_no_longer_match_the_cart(): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		$order = $this->sut->create_order_from_cart();
		$item  = array_values( $order->get_items() )[0];
		$item->set_quantity( 2 );
		$item->save();

		$this->sut->update_order_from_cart( $order );

		$items = array_values( $order->get_items() );
		$this->assertCount( 1, $items, 'The order should have one line item per cart item.' );
		$this->assertSame( 1, (int) $items[0]->get_quantity(), 'The line item should have the cart quantity again.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps line items when prices include tax and tax is rounded at subtotal.
	 *
	 * @testWith ["7.2500", "12.34"]
	 *           ["8.8750", "23.45"]
	 *
	 * @param string $tax_rate The tax rate percentage.
	 * @param string $price    The product price, including tax.
	 */
	public function test_update_order_from_cart_keeps_line_items_with_prices_including_tax_rounded_at_subtotal( string $tax_rate, string $price ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate'          => $tax_rate,
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $price ) );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		// create_order_from_cart() recalculates the order totals, which recalculates the item taxes without rounding.
		$order           = $this->sut->create_order_from_cart();
		$line_item_ids   = array_keys( $order->get_items() );
		$line_item_calls = did_action( 'woocommerce_checkout_create_order_line_item' );

		$this->sut->update_order_from_cart( $order );

		$this->assertSame( $line_item_ids, array_keys( $order->get_items() ), 'The line items should be kept.' );
		$this->assertSame( $line_item_calls, did_action( 'woocommerce_checkout_create_order_line_item' ), 'The line items should not be created again.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps every item of an unedited retried order taxed at two rates, with prices including tax.
	 *
	 * @testWith ["12.34", "yes"]
	 *           ["23.45", "yes"]
	 *           ["19.99", "yes"]
	 *           ["23.45", "no"]
	 *           ["19.99", "no"]
	 *
	 * @param string $price             The product price, including tax.
	 * @param string $round_at_subtotal Whether tax is rounded at subtotal.
	 */
	public function test_update_order_from_cart_keeps_items_of_a_retried_order_taxed_at_two_rates( string $price, string $round_at_subtotal ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
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
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( \WC_Cart $cart ) {
				$cart->add_fee( 'Handling', 3.33, true );
			}
		);
		\WC_Helper_Shipping::create_simple_flat_rate( 7.77 );
		try {
			$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $price ) );
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $product->get_id(), 2 );
			WC()->cart->apply_coupon( $coupon->get_code() );
			WC()->cart->calculate_totals();
			$order = $this->sut->create_order_from_cart();
			$this->place_order( $order );
			$before = $this->get_item_ids_by_type( $order );

			$this->sut->update_order_from_cart( $order );
			$cart_total = (float) WC()->cart->get_total( 'edit' );
		} finally {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		}

		$this->assertNotEmpty( $before['coupon'], 'The order should have a coupon line.' );
		$this->assertSame( $before, $this->get_item_ids_by_type( $order ), 'Every item should keep its ID.' );
		$this->assertSame( $cart_total, (float) $order->get_total(), 'The order total should match the cart.' );
	}

	/**
	 * @testdox update_order_from_cart() replaces shipping and fee lines whose amounts a merchant edited, so the order total matches the cart.
	 *
	 * @testWith ["none", "", false]
	 *           ["shipping_total", "shipping", false]
	 *           ["fee_total", "fee", false]
	 *           ["none", "", true]
	 *           ["shipping_total", "shipping", true]
	 *           ["fee_total", "fee", true]
	 *
	 * @param string $edit          The merchant edit to make to the order.
	 * @param string $replaced_type The item type that should be replaced, or an empty string when none should be.
	 * @param bool   $retried       Whether the order is a retried one with a record of its cart items, or a draft.
	 */
	public function test_update_order_from_cart_replaces_shipping_and_fees_with_edited_amounts( string $edit, string $replaced_type, bool $retried ): void {
		try {
			$order = $this->create_order_with_shipping_and_fee();
			if ( $retried ) {
				$this->place_order( $order );
			}
			$before = $this->get_item_ids_by_type( $order );
			if ( 'shipping_total' === $edit ) {
				$shipping = array_values( $order->get_shipping_methods() )[0];
				$shipping->set_total( (float) $shipping->get_total() + 5 );
				$shipping->save();
			} elseif ( 'fee_total' === $edit ) {
				$fee = array_values( $order->get_fees() )[0];
				$fee->set_total( (float) $fee->get_total() + 10 );
				$fee->save();
			}

			$this->sut->update_order_from_cart( $order );
			$after      = $this->get_item_ids_by_type( $order );
			$cart_total = (float) WC()->cart->get_total( 'edit' );
		} finally {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		}

		foreach ( $before as $type => $ids ) {
			if ( $type === $replaced_type ) {
				$this->assertCount( count( $ids ), $after[ $type ], "The edited {$type} lines should be added again once." );
				$this->assertEmpty( array_intersect( $ids, $after[ $type ] ), "The edited {$type} lines should be replaced." );
			} else {
				$this->assertSame( $ids, $after[ $type ], "The {$type} lines should keep their IDs." );
			}
		}
		$this->assertSame( $cart_total, (float) $order->get_total(), 'The order total should match the cart.' );
	}

	/**
	 * @testdox update_order_from_cart() keeps repaired shipping and fee lines on the next unchanged synchronization.
	 *
	 * @testWith ["shipping", "pending"]
	 *           ["shipping", "failed"]
	 *           ["fee", "pending"]
	 *           ["fee", "failed"]
	 *
	 * @param string $item_type The item type to edit.
	 * @param string $status    The retried order status.
	 */
	public function test_update_order_from_cart_keeps_repaired_items_on_consecutive_updates( string $item_type, string $status ): void {
		try {
			$order = $this->create_order_with_shipping_and_fee();
			$this->place_order( $order );
			$order->update_status( $status );
			$item = current( $order->get_items( $item_type ) );
			$item->set_total( (float) $item->get_total() + 5 );
			$item->save();

			$this->sut->update_order_from_cart( $order );
			$repaired_ids = $this->get_item_ids_by_type( $order );
			$this->assertNotContains( $item->get_id(), $repaired_ids[ $item_type ], 'The edited item should be replaced on the first update.' );
			$repaired_item = current( $order->get_items( $item_type ) );
			$repaired_item->update_meta_data( 'extension_reference', 'preserved' );
			$repaired_item->save();
			$creation_calls = did_action( "woocommerce_checkout_create_order_{$item_type}_item" );

			$extra_fee = new \WC_Order_Item_Fee();
			$extra_fee->set_name( 'Extension surcharge' );
			$extra_fee->set_total( '2' );
			$order->add_item( $extra_fee );
			$order->save();

			$order = wc_get_order( $order->get_id() );
			$this->sut->update_order_from_cart( $order );

			$this->assertSame( $repaired_ids, $this->get_item_ids_by_type( $order ), 'Repaired cart items should keep their IDs, and the added fee should be removed.' );
			$this->assertSame( 'preserved', current( $order->get_items( $item_type ) )->get_meta( 'extension_reference' ), 'Metadata on the repaired item should survive unchanged updates.' );
			$this->assertSame( $creation_calls, did_action( "woocommerce_checkout_create_order_{$item_type}_item" ), 'Unchanged items should not fire their creation hook again.' );
			$this->assertSame( (float) WC()->cart->get_total( 'edit' ), (float) $order->get_total(), 'The order total should match the cart.' );
		} finally {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		}
	}

	/**
	 * @testdox update_order_from_cart() keeps shipping and fee lines added outside the cart to a draft, or to a retried order with no record of its cart items.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $retried Whether the order is a pending one placed before its cart items were recorded, or a draft.
	 */
	public function test_update_order_from_cart_keeps_shipping_and_fees_added_outside_the_cart( bool $retried ): void {
		try {
			$order = $this->create_order_with_shipping_and_fee();
			if ( $retried ) {
				$order->update_status( OrderStatus::PENDING );
			}
			$extra_fee = new \WC_Order_Item_Fee();
			$extra_fee->set_name( 'Extension surcharge' );
			$extra_fee->set_total( '2' );
			$extra_shipping = new \WC_Order_Item_Shipping();
			$extra_shipping->set_method_title( 'Extension shipping' );
			$extra_shipping->set_total( '3' );
			$order->add_item( $extra_fee );
			$order->add_item( $extra_shipping );
			$order->save();
			$before = $this->get_item_ids_by_type( $order );

			$this->sut->update_order_from_cart( $order );
			$this->sut->update_order_from_cart( $order );
		} finally {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		}

		$after = $this->get_item_ids_by_type( $order );
		$this->assertSame( $before['fee'], $after['fee'], 'The cart fee and the extension fee should keep their IDs.' );
		$this->assertSame( $before['shipping'], $after['shipping'], 'The cart shipping line and the extension shipping line should keep their IDs.' );
	}

	/**
	 * @testdox update_order_from_cart() removes shipping and fee lines a merchant added to a retried order, so the order total matches the cart.
	 */
	public function test_update_order_from_cart_removes_shipping_and_fees_added_to_a_retried_order(): void {
		try {
			$order = $this->create_order_with_shipping_and_fee();
			$this->place_order( $order );
			$cart_item_ids = $this->get_item_ids_by_type( $order );

			// The added fee has the same amount as the cart's fee, which then gets edited, so matching by amount isn't enough.
			$added_fee = new \WC_Order_Item_Fee();
			$added_fee->set_name( 'Merchant fee' );
			$added_fee->set_total( '5' );
			$added_shipping = new \WC_Order_Item_Shipping();
			$added_shipping->set_method_title( 'Merchant shipping' );
			$added_shipping->set_total( '3' );
			$order->add_item( $added_fee );
			$order->add_item( $added_shipping );
			$cart_fee = array_values( $order->get_fees() )[0];
			$cart_fee->set_total( '15' );
			$order->save();

			$this->sut->update_order_from_cart( $order );
			$cart_total = (float) WC()->cart->get_total( 'edit' );
		} finally {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		}

		$after = $this->get_item_ids_by_type( $order );
		$this->assertCount( 1, $after['fee'], 'Only the cart fee should be on the order.' );
		$this->assertEmpty( array_intersect( array( $added_fee->get_id(), $cart_fee->get_id() ), $after['fee'] ), 'The added fee and the edited cart fee should be removed.' );
		$this->assertSame( $cart_item_ids['shipping'], $after['shipping'], 'Only the cart shipping line should be on the order, with the same ID.' );
		$this->assertSame( $cart_item_ids['line_item'], $after['line_item'], 'The line items should keep their IDs.' );
		$this->assertSame( $cart_total, (float) $order->get_total(), 'The order total should match the cart.' );
	}

	/**
	 * @testdox update_order_from_cart() adds back the coupon line of a retried order a merchant removed the coupon from.
	 */
	public function test_update_order_from_cart_adds_back_a_coupon_removed_from_a_retried_order(): void {
		$coupon  = \WC_Helper_Coupon::create_coupon( 'retry10', array( 'coupon_amount' => 10 ) );
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();
		$order = $this->sut->create_order_from_cart();
		$this->place_order( $order );

		$order->remove_coupon( $coupon->get_code() );
		$order->save();
		$this->assertEmpty( $order->get_coupons(), 'Removing the coupon should remove its line.' );

		$this->sut->update_order_from_cart( $order );

		$this->assertSame( array( $coupon->get_code() ), $order->get_coupon_codes(), 'The coupon line should be added back.' );
		$this->assertSame( (float) WC()->cart->get_total( 'edit' ), (float) $order->get_total(), 'The order total should match the cart.' );
	}

	/**
	 * Saves an order as pending and records its cart items, as the Store API checkout does when it places the order.
	 *
	 * @param \WC_Order $order The order.
	 */
	private function place_order( \WC_Order $order ): void {
		$cart_order_items = WC()->checkout()->get_cart_order_items( $order );
		$order->update_status( OrderStatus::PENDING );
		WC()->checkout()->save_cart_order_item_ids( $order, $cart_order_items );
	}

	/**
	 * Creates a Store API order from a cart with one product, one fee and flat rate shipping.
	 * The caller deletes the flat rate.
	 *
	 * @return \WC_Order
	 */
	private function create_order_with_shipping_and_fee(): \WC_Order {
		\WC_Helper_Shipping::create_simple_flat_rate( 10 );
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( \WC_Cart $cart ) {
				$cart->add_fee( 'Handling', 5 );
			}
		);
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		$this->assertTrue( WC()->cart->has_calculated_shipping(), 'The cart should have calculated shipping.' );

		$order = $this->sut->create_order_from_cart();
		$this->assertCount( 1, $order->get_shipping_methods(), 'The order should have a shipping line.' );
		$this->assertCount( 1, $order->get_fees(), 'The order should have a fee.' );

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
		foreach ( array( 'line_item', 'shipping', 'fee', 'coupon' ) as $type ) {
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
	 * @testdox update_order_from_cart() writes the filtered default on the order when the shopper has not chosen a method.
	 */
	public function test_update_order_from_cart_writes_the_filtered_default_without_a_session_choice(): void {
		$order = $this->sync_order_from_cart_with_cod_enabled( null );

		$this->assertSame( '', $order->get_payment_method() );
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

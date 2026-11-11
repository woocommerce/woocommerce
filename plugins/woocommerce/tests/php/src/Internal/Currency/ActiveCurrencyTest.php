<?php
/**
 * ActiveCurrencyTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Currency;

use Automattic\WooCommerce\Currency\ActiveCurrency;
use Automattic\WooCommerce\Internal\Admin\Notes\NewSalesRecord;

/**
 * Tests for ActiveCurrency and the order and formatting behaviour that depends on it.
 */
class ActiveCurrencyTest extends \WC_Unit_Test_Case {

	/**
	 * Decimals a no-argument wc_get_price_decimals() returned at woocommerce_order_before_calculate_totals.
	 *
	 * @var int|null
	 */
	private $decimals_seen_before_totals = null;

	/**
	 * Set up the test filter that gives JPY and KWD their own decimals.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wc_get_price_decimals', array( $this, 'filter_decimals_by_currency' ), 10, 2 );
	}

	/**
	 * Remove the test filter and action.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'wc_get_price_decimals', array( $this, 'filter_decimals_by_currency' ), 10 );
		remove_action( 'woocommerce_order_before_calculate_totals', array( $this, 'record_decimals_before_totals' ) );
		parent::tearDown();
	}

	/**
	 * Return 0 decimals for JPY, 3 for KWD and the value unchanged otherwise.
	 *
	 * @param int    $decimals Decimals from the store option.
	 * @param string $currency Currency code the caller asked for, or an empty string.
	 * @return int
	 */
	public function filter_decimals_by_currency( $decimals, $currency = '' ) {
		if ( 'JPY' === $currency ) {
			return 0;
		}

		if ( 'KWD' === $currency ) {
			return 3;
		}

		return $decimals;
	}

	/**
	 * Record the decimals a no-argument call sees.
	 *
	 * @return void
	 */
	public function record_decimals_before_totals(): void {
		$this->decimals_seen_before_totals = wc_get_price_decimals();
	}

	/**
	 * Create an order in the given currency.
	 *
	 * @param string $currency Currency code.
	 * @return \WC_Order
	 */
	private function create_order( string $currency ): \WC_Order {
		$order = wc_create_order();
		$order->set_currency( $currency );
		$order->save();

		return $order;
	}

	/**
	 * Read the innermost operation name of the active scopes, or null when none names one.
	 *
	 * @return string|null
	 */
	public static function current_operation(): ?string {
		$entry = self::innermost_operation_entry();
		return $entry ? $entry['operation'] : null;
	}

	/**
	 * Read the subject of the innermost scope that names an operation, or null when there is none.
	 *
	 * @return object|null
	 */
	public static function current_subject(): ?object {
		$entry = self::innermost_operation_entry();
		return $entry ? $entry['subject'] : null;
	}

	/**
	 * Find the innermost stack entry that names an operation.
	 *
	 * @return array|null
	 */
	private static function innermost_operation_entry(): ?array {
		$property = new \ReflectionProperty( ActiveCurrency::class, 'stack' );
		$property->setAccessible( true );
		foreach ( array_reverse( $property->getValue() ) as $entry ) {
			if ( null !== $entry['operation'] ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * The active currency is set only while the scope is held.
	 *
	 * @return void
	 */
	public function test_scope_activates_the_currency_only_while_held(): void {
		$this->assertNull( ActiveCurrency::get() );

		$scope = ActiveCurrency::scope( 'JPY' );
		$seen  = ActiveCurrency::get();
		$scope->release();

		$this->assertSame( 'JPY', $seen );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * A held scope is released when an exception unwinds the function that opened it.
	 *
	 * @return void
	 */
	public function test_scope_is_released_when_the_function_throws(): void {
		$this->expectException( \RuntimeException::class );

		try {
			( function () {
				$scope = ActiveCurrency::scope( 'JPY' );
				throw new \RuntimeException( 'Failed.' );
			} )();
		} finally {
			$this->assertNull( ActiveCurrency::get() );
		}
	}

	/**
	 * A no-argument decimals call inside the totals hook uses the order's currency, and nothing stays active afterwards.
	 *
	 * @return void
	 */
	public function test_no_argument_decimals_use_the_order_currency_during_calculate_totals(): void {
		$order = $this->create_order( 'JPY' );
		add_action( 'woocommerce_order_before_calculate_totals', array( $this, 'record_decimals_before_totals' ) );

		$order->calculate_totals();

		$this->assertSame( 0, $this->decimals_seen_before_totals );
		$this->assertNull( ActiveCurrency::get() );
		$this->assertSame( 2, wc_get_price_decimals() );
	}

	/**
	 * A fixed cart coupon applied to a JPY order is rounded to whole yen, not to two decimals.
	 *
	 * @return void
	 */
	public function test_applied_fixed_coupon_discount_is_rounded_to_zero_decimals_for_jpy(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( '100' );
		$product->save();

		$coupon = new \WC_Coupon();
		$coupon->set_code( 'half-yen-off' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( '10.5' );
		$coupon->save();

		$order = $this->create_order( 'JPY' );
		$order->add_product( $product, 1 );
		$order->save();

		$this->assertTrue( $order->apply_coupon( 'half-yen-off' ) );
		$this->assertEquals( 11, $order->get_discount_total() );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * wc_price() with a currency code formats with that currency's decimals.
	 *
	 * @return void
	 */
	public function test_wc_price_uses_the_requested_currency_decimals(): void {
		$price = wc_price( 1234.567, array( 'currency' => 'KWD' ) );

		$this->assertStringContainsString( '1,234.567', $price );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * Without the currency filter, formatting and decimals stay as they were.
	 *
	 * @return void
	 */
	public function test_formatting_is_unchanged_without_the_currency_filter(): void {
		remove_filter( 'wc_get_price_decimals', array( $this, 'filter_decimals_by_currency' ), 10 );

		$this->assertSame( 2, wc_get_price_decimals() );
		$this->assertStringContainsString( '1,234.57', wc_price( 1234.567, array( 'currency' => 'KWD' ) ) );
	}

	/**
	 * Record the decimals a no-argument call sees inside the taxes stage.
	 *
	 * @return void
	 */
	public function record_decimals_before_taxes(): void {
		$this->decimals_seen_before_totals = wc_get_price_decimals();
	}

	/**
	 * WOOPMNT-6678: a JPY order whose fixed coupon is re-applied on reload keeps whole-yen discounts and totals.
	 *
	 * @return void
	 */
	public function test_recalculated_coupons_on_reloaded_jpy_order_keep_zero_decimals(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( '100' );
		$product->save();

		$coupon = new \WC_Coupon();
		$coupon->set_code( 'yen-half-off' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( '10.5' );
		$coupon->save();

		$order = $this->create_order( 'JPY' );
		$order->add_product( $product, 1 );
		$order->save();
		$this->assertTrue( $order->apply_coupon( 'yen-half-off' ) );

		$reloaded = new \WC_Order( $order->get_id() );
		$reloaded->recalculate_coupons();

		$this->assertEquals( 11, $reloaded->get_discount_total() );
		$this->assertStringNotContainsString( '.', (string) $reloaded->get_discount_total() );
		$this->assertStringNotContainsString( '.', (string) $reloaded->get_total() );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * A JPY order loaded from a stored total of 1234.6 comes back with 0 decimals.
	 *
	 * @return void
	 */
	public function test_loading_jpy_order_rounds_stored_total_to_zero_decimals(): void {
		$order = $this->create_order( 'JPY' );
		if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			global $wpdb;
			$wpdb->update( \Automattic\WooCommerce\Utilities\OrderUtil::get_table_for_orders(), array( 'total_amount' => '1234.6' ), array( 'id' => $order->get_id() ) );
		} else {
			update_post_meta( $order->get_id(), '_order_total', '1234.6' );
		}

		$loaded = new \WC_Order( $order->get_id() );

		$this->assertSame( '1235', (string) $loaded->get_total() );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * The price format filter gets the active currency while a scope is open and no extra argument otherwise.
	 *
	 * @return void
	 */
	public function test_price_format_filter_receives_the_active_currency(): void {
		$received = array();
		add_filter(
			'woocommerce_price_format',
			function ( $format, $position, ...$args ) use ( &$received ) {
				$received[] = $args;
				return $format;
			},
			10,
			PHP_INT_MAX
		);

		$scope = ActiveCurrency::scope( 'JPY' );
		get_woocommerce_price_format();
		$scope->release();
		get_woocommerce_price_format();

		remove_all_filters( 'woocommerce_price_format' );
		$this->assertSame( array( array( 'JPY' ), array() ), $received );
	}

	/**
	 * A deferred woocommerce_order_after_calculate_totals callback runs from save() with the order currency active.
	 *
	 * @return void
	 */
	public function test_deferred_after_calculate_totals_runs_with_the_order_currency_from_save(): void {
		$order = $this->create_order( 'JPY' );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'record_decimals_after_totals' ) );

		$order->stage_totals();
		$this->assertNull( $this->decimals_seen_after_totals );

		$order->save();
		remove_action( 'woocommerce_order_after_calculate_totals', array( $this, 'record_decimals_after_totals' ) );

		$this->assertSame( 0, $this->decimals_seen_after_totals );
	}

	/**
	 * Decimals a no-argument wc_get_price_decimals() returned at woocommerce_order_after_calculate_totals.
	 *
	 * @var int|null
	 */
	private $decimals_seen_after_totals = null;

	/**
	 * Record the decimals seen by the after-totals hook.
	 *
	 * @return void
	 */
	public function record_decimals_after_totals(): void {
		$this->decimals_seen_after_totals = wc_get_price_decimals();
	}

	/**
	 * A scope with an empty currency activates nothing, so the outer currency stays active.
	 *
	 * @return void
	 */
	public function test_empty_currency_scope_inherits_the_outer_currency(): void {
		$outer = ActiveCurrency::scope( 'JPY' );

		$empty = ActiveCurrency::scope( '' );
		$this->assertSame( 'JPY', ActiveCurrency::get() );
		$this->assertSame( 0, wc_get_price_decimals() );

		unset( $empty, $outer );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * The Store API order response reports the order currency, not the store currency.
	 *
	 * @return void
	 */
	public function test_store_api_order_response_uses_the_order_currency(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->set_billing_email( 'shopper@example.com' );
		$order->save();

		wp_set_current_user( 0 );
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'JPY', $data['totals']->currency_code );
		$this->assertSame( 0, $data['totals']->currency_minor_unit );
	}

	/**
	 * Checkout sets the store currency on the order before it stages the cart line items.
	 *
	 * @return void
	 */
	public function test_checkout_order_stages_line_items_in_the_store_currency(): void {
		$previous_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'JPY' );

		$product = new \WC_Product_Simple();
		$product->set_name( 'Currency test product' );
		$product->set_regular_price( '1000.4' );
		$product->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->set_currency( 'USD' );
		$order->save();

		$seen_currencies = array();
		$spy             = function ( $decimals, $currency = '' ) use ( &$seen_currencies ) {
			$seen_currencies[] = $currency;
			return $decimals;
		};
		add_filter( 'wc_get_price_decimals', $spy, 10, 2 );

		( new \Automattic\WooCommerce\StoreApi\Utilities\OrderController() )->update_order_from_cart( $order );

		remove_filter( 'wc_get_price_decimals', $spy, 10 );
		WC()->cart->empty_cart();
		$product->delete( true );
		update_option( 'woocommerce_currency', $previous_currency );

		$this->assertSame( 'JPY', $order->get_currency() );
		$this->assertContains( 'JPY', $seen_currencies );
		$this->assertNotContains( 'USD', $seen_currencies );
	}

	/**
	 * Nested scopes report the innermost named operation and its subject, and the outer one returns after release.
	 *
	 * @return void
	 */
	public function test_nested_scopes_report_the_innermost_operation_and_subject(): void {
		$outer = new \stdClass();

		$outer_scope = ActiveCurrency::scope( 'JPY', 'order', $outer );
		$this->assertSame( 'order', self::current_operation() );
		$this->assertSame( $outer, self::current_subject() );

		$inner_scope = ActiveCurrency::scope( 'EUR', 'my-plugin.package' );
		$this->assertSame( 'my-plugin.package', self::current_operation() );
		$this->assertNull( self::current_subject() );
		$this->assertSame( 'EUR', ActiveCurrency::get() );

		$inner_scope = null;
		$this->assertSame( 'order', self::current_operation() );
		$this->assertSame( $outer, self::current_subject() );

		$outer_scope = null;
		$this->assertNull( self::current_operation() );
		$this->assertNull( self::current_subject() );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * is_in_operation() matches an operation at any depth, including outer scopes.
	 *
	 * @return void
	 */
	public function test_is_in_operation_sees_outer_operations(): void {
		$cart = new \stdClass();

		$outer_scope = ActiveCurrency::scope( 'USD', 'cart.add_to_cart', $cart );
		$inner_scope = ActiveCurrency::scope( 'USD', 'cart.calculate_totals', $cart );

		$this->assertTrue( ActiveCurrency::is_in_operation( 'cart.add_to_cart' ) );
		$this->assertTrue( ActiveCurrency::is_in_operation( 'cart.calculate_totals' ) );
		$this->assertFalse( ActiveCurrency::is_in_operation( 'order' ) );

		$inner_scope = null;
		$outer_scope = null;
		$this->assertFalse( ActiveCurrency::is_in_operation( 'cart.add_to_cart' ) );
	}

	/**
	 * get_subject_of() finds the nearest scope with the operation, even when helper scopes are innermost.
	 *
	 * @return void
	 */
	public function test_get_subject_of_sees_through_helper_scopes(): void {
		$method       = new \stdClass();
		$outer_method = new \stdClass();

		$outer_scope  = ActiveCurrency::scope( '', 'shipping.rates', $outer_method );
		$middle_scope = ActiveCurrency::scope( '', 'shipping.rates', $method );
		$helper_scope = ActiveCurrency::scope( '', 'my-plugin.helper' );

		$this->assertSame( 'my-plugin.helper', self::current_operation() );
		$this->assertNull( self::current_subject() );
		$this->assertSame( $method, ActiveCurrency::get_subject_of( 'shipping.rates' ) );
		$this->assertNull( ActiveCurrency::get_subject_of( 'order' ) );

		$helper_scope = null;
		$middle_scope = null;
		$this->assertSame( $outer_method, ActiveCurrency::get_subject_of( 'shipping.rates' ) );

		$outer_scope = null;
		$this->assertNull( ActiveCurrency::get_subject_of( 'shipping.rates' ) );
	}

	/**
	 * Saving an order announces order.save once, for the order, instead of nesting a duplicate scope.
	 *
	 * @return void
	 */
	public function test_order_save_announces_a_single_order_save_scope(): void {
		$order = new \WC_Order();
		$order->set_currency( 'EUR' );

		$subjects = array();
		$probe    = function () use ( &$subjects ) {
			$subjects[] = ActiveCurrency::get_subject_of( 'order' );
		};
		add_action( 'woocommerce_before_order_object_save', $probe );

		$order->save();

		remove_action( 'woocommerce_before_order_object_save', $probe );
		$this->assertNotEmpty( $subjects );
		$this->assertSame( $order, $subjects[0] );
		$this->assertNull( ActiveCurrency::get_subject_of( 'order' ) );
		$order->delete( true );
	}

	/**
	 * An empty-code scope inherits the outer currency but still records its own operation.
	 *
	 * @return void
	 */
	public function test_empty_code_scope_inherits_currency_and_keeps_operation(): void {
		$order = new \stdClass();

		$outer_scope = ActiveCurrency::scope( 'JPY', 'order', $order );
		$empty_scope = ActiveCurrency::scope( '', 'my-plugin.helper' );

		$this->assertSame( 'JPY', ActiveCurrency::get() );
		$this->assertSame( 'my-plugin.helper', self::current_operation() );
		$this->assertNull( self::current_subject() );
		$this->assertTrue( ActiveCurrency::is_in_operation( 'order' ) );

		$empty_scope = null;
		$outer_scope = null;
	}

	/**
	 * A woocommerce_add_to_cart callback runs inside the cart.add_to_cart operation, with the cart as its subject.
	 *
	 * @return void
	 */
	public function test_add_to_cart_callback_runs_inside_the_add_to_cart_operation(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$seen     = array();
		$callback = function () use ( &$seen ) {
			$seen = array(
				'operation'   => self::current_operation(),
				'subject'     => self::current_subject(),
				'in_add_cart' => ActiveCurrency::is_in_operation( 'cart.add_to_cart' ),
			);
		};

		add_action( 'woocommerce_add_to_cart', $callback );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		remove_action( 'woocommerce_add_to_cart', $callback );

		$this->assertTrue( $seen['in_add_cart'] );
		$this->assertSame( 'cart.add_to_cart', $seen['operation'] );
		$this->assertSame( WC()->cart, $seen['subject'] );
		$this->assertNull( self::current_operation() );
	}

	/**
	 * A shipping method's calculate_shipping() runs inside shipping.rates, with the method as its subject.
	 *
	 * @return void
	 */
	public function test_shipping_method_calculation_reports_the_rates_operation_and_method(): void {
		$method = new class() extends \WC_Shipping_Method {
			public $seen_operation = null;
			public $seen_subject   = null;

			public function __construct() {
				$this->id      = 'active_currency_test';
				$this->enabled = 'yes';
				parent::__construct( 0 );
			}

			public function calculate_shipping( $package = array() ) {
				$this->seen_operation = ActiveCurrencyTest::current_operation();
				$this->seen_subject   = ActiveCurrencyTest::current_subject();
			}
		};

		$package = array(
			'contents'    => array(),
			'destination' => array( 'country' => 'US' ),
		);
		$method->get_rates_for_package( $package );

		$this->assertSame( 'shipping.rates', $method->seen_operation );
		$this->assertSame( $method, $method->seen_subject );
		$this->assertNull( self::current_operation() );
	}

	/**
	 * The order totals hook runs inside the order.calculate_totals operation.
	 *
	 * @return void
	 */
	public function test_order_totals_hook_runs_inside_the_calculate_totals_operation(): void {
		$order = $this->create_order( 'JPY' );
		$seen  = null;

		$callback = function () use ( &$seen ) {
			$seen = ActiveCurrency::is_in_operation( 'order' );
		};
		add_action( 'woocommerce_order_before_calculate_totals', $callback );

		$order->calculate_totals();

		remove_action( 'woocommerce_order_before_calculate_totals', $callback );
		$this->assertTrue( $seen );
		$this->assertNull( self::current_operation() );
	}

	/**
	 * A third-party scope is readable by other code, and nests inside a core operation.
	 *
	 * @return void
	 */
	public function test_third_party_scope_is_readable_and_nests_inside_a_core_operation(): void {
		$core_scope   = ActiveCurrency::scope( 'JPY', 'order' );
		$subject      = new \stdClass();
		$plugin_scope = ActiveCurrency::scope( 'EUR', 'woocommerce-bookings.calculate_booking_cost', $subject );

		$this->assertSame( 'EUR', ActiveCurrency::get() );
		$this->assertSame( 'woocommerce-bookings.calculate_booking_cost', self::current_operation() );
		$this->assertSame( $subject, self::current_subject() );
		$this->assertTrue( ActiveCurrency::is_in_operation( 'order' ) );
		$this->assertTrue( ActiveCurrency::is_in_operation( 'woocommerce-bookings.calculate_booking_cost' ) );

		$plugin_scope->release();

		$this->assertSame( 'JPY', ActiveCurrency::get() );
		$this->assertSame( 'order', self::current_operation() );
		$this->assertNull( self::current_subject() );
		$this->assertFalse( ActiveCurrency::is_in_operation( 'woocommerce-bookings.calculate_booking_cost' ) );

		$core_scope->release();

		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * Calling release() more than once, then letting the object be destroyed, does not remove a later scope.
	 *
	 * @return void
	 */
	public function test_explicit_release_is_idempotent(): void {
		$first = ActiveCurrency::scope( 'JPY', 'my-plugin.first' );
		$first->release();
		$first->release();

		$this->assertNull( ActiveCurrency::get() );

		$second = ActiveCurrency::scope( 'EUR', 'my-plugin.second' );
		$first->release();
		unset( $first );

		$this->assertSame( 'EUR', ActiveCurrency::get() );
		$this->assertSame( 'my-plugin.second', self::current_operation() );

		$second->release();
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * Releasing an outer scope first, then an inner one, keeps scopes pushed in between intact.
	 *
	 * @return void
	 */
	public function test_out_of_order_release_does_not_remove_scopes_pushed_later(): void {
		$outer = ActiveCurrency::scope( 'JPY', 'my-plugin.outer' );
		$inner = ActiveCurrency::scope( 'EUR', 'my-plugin.inner' );

		$outer->release();
		$this->assertNull( ActiveCurrency::get() );

		$later = ActiveCurrency::scope( 'GBP', 'my-plugin.later' );
		$other = ActiveCurrency::scope( 'USD', 'my-plugin.other' );

		$inner->release();
		unset( $inner );

		$this->assertSame( 'USD', ActiveCurrency::get() );
		$this->assertSame( 'my-plugin.other', self::current_operation() );
		$this->assertTrue( ActiveCurrency::is_in_operation( 'my-plugin.later' ) );

		$other->release();
		$later->release();

		$this->assertNull( ActiveCurrency::get() );
		$this->assertNull( self::current_operation() );
	}

	/**
	 * An invalid operation name is reported with wc_doing_it_wrong() and the currency is still activated.
	 *
	 * @return void
	 */
	public function test_invalid_operation_name_triggers_doing_it_wrong(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Currency\ActiveCurrency::scope' );

		$scope = ActiveCurrency::scope( 'JPY', 'Bad Name!' );

		$this->assertSame( 'JPY', ActiveCurrency::get() );
		$this->assertNull( self::current_operation() );

		$scope->release();
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * Operations seen by the product price filter, one entry per call.
	 *
	 * @var bool[]
	 */
	private $price_filter_saw_display_operation = array();

	/**
	 * Record whether product.price_for_display is active when the price filter runs.
	 *
	 * @param mixed $price Price.
	 * @return mixed
	 */
	public function record_display_operation_on_price( $price ) {
		$this->price_filter_saw_display_operation[] = ActiveCurrency::is_in_operation( 'product.price_for_display' );
		return $price;
	}

	/**
	 * product.price_for_display is active only when a price is computed through the display functions.
	 *
	 * @return void
	 */
	public function test_price_for_display_operation_is_active_only_inside_display_price_functions(): void {
		$product = \WC_Helper_Product::create_simple_product();
		add_filter( 'woocommerce_product_get_price', array( $this, 'record_display_operation_on_price' ) );

		wc_get_price_including_tax( $product );
		$product->get_price();

		remove_filter( 'woocommerce_product_get_price', array( $this, 'record_display_operation_on_price' ) );
		$this->assertSame( array( true, false ), $this->price_filter_saw_display_operation );
	}

	/**
	 * cart.product_subtotal is active inside WC_Cart::get_product_subtotal() with the cart as subject.
	 *
	 * @return void
	 */
	public function test_cart_product_subtotal_operation_is_active_inside_get_product_subtotal(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$cart    = WC()->cart;
		$seen    = array();
		$probe   = function ( $value ) use ( &$seen ) {
			$seen[] = array( self::current_operation(), self::current_subject() );
			return $value;
		};
		add_filter( 'woocommerce_product_get_price', $probe );

		$cart->get_product_subtotal( $product, 2 );

		remove_filter( 'woocommerce_product_get_price', $probe );
		$this->assertContains( 'cart.product_subtotal', array_column( $seen, 0 ) );
		$this->assertSame( $cart, $seen[0][1] );
		$this->assertNull( self::current_operation() );
	}

	/**
	 * NewSalesRecord::sum_sales_for_date() runs inside admin.new_sales_record with the store currency active.
	 *
	 * @return void
	 */
	public function test_new_sales_record_sum_runs_in_store_currency_operation(): void {
		\WC_Helper_Order::create_order();
		$seen  = array();
		$probe = function ( $total ) use ( &$seen ) {
			$seen[] = array( ActiveCurrency::is_in_operation( 'admin.new_sales_record' ), ActiveCurrency::get() );
			return $total;
		};
		add_filter( 'woocommerce_order_get_total', $probe );

		NewSalesRecord::sum_sales_for_date( gmdate( 'Y-m-d' ) );

		remove_filter( 'woocommerce_order_get_total', $probe );
		$this->assertNotEmpty( $seen );
		$this->assertSame( array( true, get_option( 'woocommerce_currency' ) ), $seen[0] );
		$this->assertFalse( ActiveCurrency::is_in_operation( 'admin.new_sales_record' ) );
	}
}

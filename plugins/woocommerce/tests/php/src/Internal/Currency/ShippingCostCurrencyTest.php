<?php
/**
 * ShippingCostCurrencyTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Currency;

use Automattic\WooCommerce\Currency\ActiveCurrency;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * Tests that merchant-entered shipping costs are evaluated in the store currency, and that orders take the cart currency.
 */
class ShippingCostCurrencyTest extends \WC_Unit_Test_Case {

	/**
	 * Cart currency used by the tests.
	 */
	private const CART_CURRENCY = 'JPY';

	/**
	 * Flat rate cost entered by the merchant, in the store currency.
	 */
	private const COST_FORMULA = '10 + [fee percent="10" min_fee="1.234"]';

	/**
	 * Package contents cost, passed as the [cost] argument.
	 */
	private const PACKAGE_COST = 15;

	/**
	 * Currency codes seen by the decimals filter, in call order.
	 *
	 * @var string[]
	 */
	private $decimals_currencies = array();

	/**
	 * Empty the cart and reset the cart currency.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		WC()->cart->empty_cart();
		WC()->cart->set_currency( '' );
	}

	/**
	 * Remove the test filters, the cart currency and the cart.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_all_filters( 'wc_get_price_decimals' );
		WC()->cart->set_currency( '' );
		WC()->cart->empty_cart();
		parent::tearDown();
	}

	/**
	 * Record the currency passed to the decimals filter, and give JPY no decimals and every other currency two.
	 *
	 * @return void
	 */
	private function add_decimals_filter(): void {
		add_filter(
			'wc_get_price_decimals',
			function ( $decimals, $currency = '' ) {
				$this->decimals_currencies[] = $currency;
				return self::CART_CURRENCY === $currency ? 0 : 2;
			},
			10,
			2
		);
	}

	/**
	 * Create a simple product and add it to the cart.
	 *
	 * @return void
	 */
	private function add_product_to_cart(): void {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Shipping cost currency product' );
		$product->set_regular_price( '10' );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * An order created by the classic checkout takes the cart currency.
	 *
	 * @return void
	 */
	public function test_classic_checkout_order_takes_the_cart_currency(): void {
		$this->add_product_to_cart();
		WC()->cart->set_currency( self::CART_CURRENCY );
		WC()->cart->calculate_totals();

		$order_id = WC()->checkout()->create_order( array( 'payment_method' => '' ) );
		$order    = wc_get_order( $order_id );

		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertSame( self::CART_CURRENCY, $order->get_currency() );
		$this->assertNull( ActiveCurrency::get() );

		$order->delete( true );
	}

	/**
	 * An order created by the Store API takes the cart currency.
	 *
	 * @return void
	 */
	public function test_store_api_order_takes_the_cart_currency(): void {
		$this->add_product_to_cart();
		WC()->cart->set_currency( self::CART_CURRENCY );
		WC()->cart->calculate_totals();

		$order = ( new OrderController() )->create_order_from_cart();

		$this->assertInstanceOf( \WC_Order::class, $order );
		$this->assertSame( self::CART_CURRENCY, $order->get_currency() );
		$this->assertNull( ActiveCurrency::get() );

		$order->delete( true );
	}

	/**
	 * A shipping method calculates its rates in the store currency while a package currency is active.
	 *
	 * @return void
	 */
	public function test_shipping_method_calculates_in_the_store_currency(): void {
		$store_currency = get_option( 'woocommerce_currency' );
		$method         = new CurrencyRecordingShippingMethod();
		$package        = array(
			'contents'      => array(),
			'contents_cost' => 0,
			'currency'      => self::CART_CURRENCY,
			'destination'   => array(),
			'ship_via'      => array(),
		);

		// The package currency is active, as WC_Shipping::calculate_shipping_for_package() does.
		$package_scope = ActiveCurrency::scope( self::CART_CURRENCY );
		$rates         = $method->get_rates_for_package( $package );
		unset( $package_scope );

		$this->assertSame( $store_currency, $method->seen_currency );
		$this->assertArrayHasKey( 'recording', $rates );
		$this->assertNull( ActiveCurrency::get() );
	}

	/**
	 * The cost arguments filter receives the currency of the cost value, defaulting to the store currency.
	 *
	 * @return void
	 */
	public function test_flat_rate_cost_args_filter_receives_the_cost_currency(): void {
		$received      = null;
		$method        = new \WC_Shipping_Flat_Rate();
		$evaluate_cost = new \ReflectionMethod( $method, 'evaluate_cost' );

		add_filter(
			'woocommerce_evaluate_shipping_cost_args',
			function ( $args ) use ( &$received ) {
				$received = $args;
				return $args;
			}
		);

		$evaluate_cost->invoke(
			$method,
			'10',
			array(
				'qty'      => 1,
				'cost'     => self::PACKAGE_COST,
				'currency' => self::CART_CURRENCY,
				'weight'   => 0,
			)
		);
		$this->assertSame( self::CART_CURRENCY, $received['currency'] );

		$evaluate_cost->invoke(
			$method,
			'10',
			array(
				'qty'    => 1,
				'cost'   => 1,
				'weight' => 0,
			)
		);
		$this->assertSame( get_option( 'woocommerce_currency' ), $received['currency'] );

		remove_all_filters( 'woocommerce_evaluate_shipping_cost_args' );
	}

	/**
	 * A coupon rebuilt from an order item is in the order currency, and a saved coupon is in the store currency.
	 *
	 * @return void
	 */
	public function test_coupon_amount_currency_follows_the_order_currency(): void {
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'amount-currency-' . wp_generate_password( 6, false ) );
		$coupon->set_amount( 10 );
		$coupon->save();

		$saved = new \WC_Coupon( $coupon->get_id() );
		$this->assertSame( '', $saved->get_amount_currency() );

		$order = new \WC_Order();
		$order->set_currency( self::CART_CURRENCY );
		$order->save();

		$coupon_item = new \WC_Order_Item_Coupon();
		$coupon_item->set_code( 'deleted-coupon' );
		$coupon_item->add_meta_data( 'coupon_data', (object) array( 'discount_type' => 'fixed_cart', 'amount' => 100 ), true );
		$order->add_item( $coupon_item );

		$get_temporary_coupon = new \ReflectionMethod( $order, 'get_temporary_coupon' );
		$rebuilt              = $get_temporary_coupon->invoke( $order, $coupon_item );

		$this->assertSame( '', $rebuilt->get_amount_currency(), 'Legacy coupon_data amounts are in the store currency.' );

		$info_item = new \WC_Order_Item_Coupon();
		$info_item->set_code( 'deleted-coupon' );
		$info_item->add_meta_data( 'coupon_info', wp_json_encode( array( 0, 'deleted-coupon', 100, 'fixed_cart' ) ), true );
		$rebuilt = $get_temporary_coupon->invoke( $order, $info_item );

		$this->assertSame( self::CART_CURRENCY, $rebuilt->get_amount_currency(), 'coupon_info amounts are in the order currency.' );

		$coupon->delete( true );
		$order->delete( true );
	}

	/**
	 * The woocommerce_add_to_cart action sees the cart currency active.
	 *
	 * @return void
	 */
	public function test_add_to_cart_runs_with_the_cart_currency_active(): void {
		$seen     = 'not called';
		$callback = function () use ( &$seen ) {
			$seen = ActiveCurrency::get();
		};

		add_action( 'woocommerce_add_to_cart', $callback );
		WC()->cart->set_currency( self::CART_CURRENCY );
		$this->add_product_to_cart();
		remove_action( 'woocommerce_add_to_cart', $callback );

		$this->assertSame( self::CART_CURRENCY, $seen );
	}
}

/**
 * Shipping method that records the active currency when it calculates rates.
 */
class CurrencyRecordingShippingMethod extends \WC_Shipping_Method {

	/**
	 * Currency active inside calculate_shipping().
	 *
	 * @var string|null
	 */
	public $seen_currency = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'currency_recording';
		$this->method_title = 'Currency recording';
		$this->supports     = array( 'shipping-zones', 'instance-settings' );
		parent::__construct( 0 );
	}

	/**
	 * Always available for the test package.
	 *
	 * @param array $package Package.
	 * @return bool
	 */
	public function is_available( $package ) {
		return true;
	}

	/**
	 * Record the active currency and add one rate.
	 *
	 * @param array $package Package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$this->seen_currency = ActiveCurrency::get();
		$this->add_rate(
			array(
				'id'    => 'recording',
				'label' => 'Recording',
				'cost'  => 1,
			)
		);
	}
}

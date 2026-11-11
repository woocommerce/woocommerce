<?php
/**
 * CartCurrencyTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Currency;

use Automattic\WooCommerce\Currency\ActiveCurrency;

/**
 * Tests for the cart currency: persistence, recalculation, hashes, shipping packages and active-currency scope.
 */
class CartCurrencyTest extends \WC_Unit_Test_Case {

	/**
	 * Shopper currency used by the tests that change it.
	 */
	private const SHOPPER_CURRENCY = 'EUR';

	/**
	 * Currency the cart is set to in the tests that set one explicitly.
	 */
	private const CART_CURRENCY = 'GBP';

	/**
	 * Currency codes seen by the decimals filter, in call order.
	 *
	 * @var string[]
	 */
	private $decimals_currencies = array();

	/**
	 * Empty the cart and reset the test state.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		WC()->cart->empty_cart();
		WC()->session->set( 'cart_currency', null );
	}

	/**
	 * Remove the test filters and empty the cart.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_currency' );
		remove_all_filters( 'wc_get_price_decimals' );
		remove_all_filters( 'woocommerce_cart_updated' );
		WC()->cart->set_currency( '' );
		WC()->cart->empty_cart();
		parent::tearDown();
	}

	/**
	 * Create a simple product and add it to the cart.
	 *
	 * @return void
	 */
	private function add_product_to_cart(): void {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Cart currency product' );
		$product->set_regular_price( '10' );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * The cart currency is the shopper currency until it is set.
	 *
	 * @return void
	 */
	public function test_cart_currency_defaults_to_the_shopper_currency(): void {
		add_filter( 'woocommerce_currency', fn() => self::SHOPPER_CURRENCY );

		$this->assertSame( self::SHOPPER_CURRENCY, WC()->cart->get_currency() );
	}

	/**
	 * An explicit cart currency is returned, and an empty value restores the shopper currency.
	 *
	 * @return void
	 */
	public function test_set_currency_overrides_the_shopper_currency_until_cleared(): void {
		WC()->cart->set_currency( self::CART_CURRENCY );
		$this->assertSame( self::CART_CURRENCY, WC()->cart->get_currency() );

		WC()->cart->set_currency( '' );
		$this->assertSame( get_woocommerce_currency(), WC()->cart->get_currency() );
	}

	/**
	 * The cart hash changes with the cart currency.
	 *
	 * @return void
	 */
	public function test_cart_hash_changes_with_the_cart_currency(): void {
		$this->add_product_to_cart();

		$hash_in_shopper_currency = WC()->cart->get_cart_hash();

		WC()->cart->set_currency( self::CART_CURRENCY );
		$hash_in_cart_currency = WC()->cart->get_cart_hash();

		$this->assertNotSame( $hash_in_shopper_currency, $hash_in_cart_currency );
	}

	/**
	 * The woocommerce_cart_updated action receives the cart as its argument.
	 *
	 * @return void
	 */
	public function test_cart_updated_action_receives_the_cart(): void {
		$received = null;
		add_action(
			'woocommerce_cart_updated',
			function ( $cart ) use ( &$received ) {
				$received = $cart;
			}
		);

		$this->add_product_to_cart();

		$this->assertSame( WC()->cart, $received );
	}

	/**
	 * The cart currency is stored in the session next to the cart totals.
	 *
	 * @return void
	 */
	public function test_session_stores_the_cart_currency(): void {
		WC()->cart->set_currency( self::CART_CURRENCY );
		$this->add_product_to_cart();

		$this->assertSame( self::CART_CURRENCY, WC()->session->get( 'cart_currency' ) );
	}

	/**
	 * A stored currency that differs from the shopper currency makes the cart recalculate and store the shopper currency.
	 *
	 * @return void
	 */
	public function test_loading_a_cart_stored_in_another_currency_recalculates_it(): void {
		$this->add_product_to_cart();
		$shopper_currency = get_woocommerce_currency();

		WC()->session->set( 'cart_currency', self::CART_CURRENCY );
		WC()->cart->get_cart_from_session();

		$this->assertSame( $shopper_currency, WC()->session->get( 'cart_currency' ) );
	}

	/**
	 * Shipping packages carry the cart currency only when it is not the store currency.
	 *
	 * @return void
	 */
	public function test_shipping_packages_carry_a_non_store_cart_currency(): void {
		$store_currency = get_option( 'woocommerce_currency' );

		WC()->cart->set_currency( $store_currency );
		$this->assertArrayNotHasKey( 'currency', WC()->cart->get_shipping_packages()[0] );

		WC()->cart->set_currency( self::CART_CURRENCY );
		$this->assertSame( self::CART_CURRENCY, WC()->cart->get_shipping_packages()[0]['currency'] );
	}

	/**
	 * Totals are calculated with the cart currency active, so no-argument formatting functions see it.
	 *
	 * @return void
	 */
	public function test_totals_are_calculated_with_the_cart_currency_active(): void {
		$this->add_product_to_cart();
		WC()->cart->set_currency( self::CART_CURRENCY );

		add_filter(
			'wc_get_price_decimals',
			function ( $decimals, $currency = '' ) {
				$this->decimals_currencies[] = $currency;
				return $decimals;
			},
			10,
			2
		);

		WC()->cart->calculate_totals();

		$this->assertContains( self::CART_CURRENCY, $this->decimals_currencies );
		$this->assertNull( ActiveCurrency::get() );
	}
}

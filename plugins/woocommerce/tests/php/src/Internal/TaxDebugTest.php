<?php
/**
 * TaxDebugTest class file.
 */

declare( strict_types=1 );
namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\TaxDebug;

/**
 * Tests for TaxDebug.
 */
class TaxDebugTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var TaxDebug
	 */
	private $sut;

	/**
	 * Products created during tests for cleanup.
	 *
	 * @var array
	 */
	private array $created_products = array();

	/**
	 * Original default country value to restore after tests.
	 *
	 * @var string|false
	 */
	private $original_default_country;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TaxDebug();

		$this->original_default_country = get_option( 'woocommerce_default_country' );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		TaxDebug::reset_notices_flag();
		wc_clear_notices();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_tax_debug_mode' );
		delete_option( 'woocommerce_tax_based_on' );
		delete_option( 'woocommerce_calc_taxes' );

		if ( false === $this->original_default_country ) {
			delete_option( 'woocommerce_default_country' );
		} else {
			update_option( 'woocommerce_default_country', $this->original_default_country );
		}

		foreach ( $this->created_products as $product ) {
			$product->delete( true );
		}
		$this->created_products = array();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		parent::tearDown();
	}

	/**
	 * @testdox is_debug_mode_enabled returns false when option is not set.
	 */
	public function test_is_debug_mode_enabled_returns_false_by_default() {
		delete_option( 'woocommerce_tax_debug_mode' );

		$this->assertFalse( TaxDebug::is_debug_mode_enabled() );
	}

	/**
	 * @testdox is_debug_mode_enabled returns false when option is no.
	 */
	public function test_is_debug_mode_enabled_returns_false_when_no() {
		update_option( 'woocommerce_tax_debug_mode', 'no' );

		$this->assertFalse( TaxDebug::is_debug_mode_enabled() );
	}

	/**
	 * @testdox is_debug_mode_enabled returns true when option is yes.
	 */
	public function test_is_debug_mode_enabled_returns_true_when_yes() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );

		$this->assertTrue( TaxDebug::is_debug_mode_enabled() );
	}

	/**
	 * @testdox maybe_show_debug_notices does not add notices when debug mode is disabled.
	 */
	public function test_no_notices_when_debug_mode_disabled() {
		update_option( 'woocommerce_tax_debug_mode', 'no' );
		$cart = $this->create_cart_with_product();

		$this->sut->maybe_show_debug_notices( $cart );

		$this->assertEmpty( wc_get_notices() );
	}

	/**
	 * @testdox maybe_show_debug_notices does not add notices when cart is empty.
	 */
	public function test_no_notices_when_cart_is_empty() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		$cart = WC()->cart;
		$cart->empty_cart();

		$this->sut->maybe_show_debug_notices( $cart );

		$this->assertEmpty( wc_get_notices() );
	}

	/**
	 * @testdox maybe_show_debug_notices does not add notices when taxes are disabled.
	 */
	public function test_no_notices_when_taxes_disabled() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$cart = $this->create_cart_with_product();

		$this->sut->maybe_show_debug_notices( $cart );

		$this->assertEmpty( wc_get_notices() );
	}

	/**
	 * @testdox maybe_show_debug_notices adds tax location notice when debug mode is enabled.
	 */
	public function test_adds_tax_location_notice_when_enabled() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		$cart = $this->create_cart_with_product();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices = wc_get_notices( 'notice' );
		$this->assertNotEmpty( $notices );

		$notice_messages     = array_map(
			function ( $notice ) {
				return $notice['notice'];
			},
			$notices
		);
		$has_location_notice = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'Tax calculated based on' ) !== false ) {
				$has_location_notice = true;
				break;
			}
		}
		$this->assertTrue( $has_location_notice, 'Expected tax location notice to be added' );
	}

	/**
	 * @testdox maybe_show_debug_notices shows shipping address source when tax_based_on is shipping.
	 */
	public function test_shows_shipping_address_source() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		$cart = $this->create_cart_with_product();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices         = wc_get_notices( 'notice' );
		$notice_messages = wp_list_pluck( $notices, 'notice' );
		$found           = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'shipping address' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected notice to mention shipping address' );
	}

	/**
	 * @testdox maybe_show_debug_notices shows billing address source when tax_based_on is billing.
	 */
	public function test_shows_billing_address_source() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'billing' );
		$cart = $this->create_cart_with_product();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices         = wc_get_notices( 'notice' );
		$notice_messages = wp_list_pluck( $notices, 'notice' );
		$found           = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'billing address' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected notice to mention billing address' );
	}

	/**
	 * @testdox maybe_show_debug_notices shows store base address source when tax_based_on is base.
	 */
	public function test_shows_base_address_source() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		$cart = $this->create_cart_with_product();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices         = wc_get_notices( 'notice' );
		$notice_messages = wp_list_pluck( $notices, 'notice' );
		$found           = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'store base address' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected notice to mention store base address' );
	}

	/**
	 * @testdox maybe_show_debug_notices does not duplicate notices on multiple calls.
	 */
	public function test_does_not_duplicate_notices() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		$cart = $this->create_cart_with_product();

		$this->sut->maybe_show_debug_notices( $cart );
		$first_count = count( wc_get_notices( 'notice' ) );

		$this->sut->maybe_show_debug_notices( $cart );
		$second_count = count( wc_get_notices( 'notice' ) );

		$this->assertSame( $first_count, $second_count, 'Notices should not be duplicated' );
	}

	/**
	 * @testdox maybe_show_debug_notices shows no rates found message when no tax rates match.
	 */
	public function test_shows_no_rates_found_message() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		$cart = $this->create_cart_with_product();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices         = wc_get_notices( 'notice' );
		$notice_messages = wp_list_pluck( $notices, 'notice' );
		$found           = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'No tax rates found' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected no tax rates found notice' );
	}

	/**
	 * @testdox maybe_show_debug_notices shows applied tax rate when rates exist.
	 */
	public function test_shows_applied_tax_rate() {
		update_option( 'woocommerce_tax_debug_mode', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		$tax_rate_id = $this->create_tax_rate( 'US', 'CA', '10' );
		$cart        = $this->create_cart_with_product();
		$cart->calculate_totals();

		TaxDebug::reset_notices_flag();
		wc_clear_notices();
		$this->sut->maybe_show_debug_notices( $cart );

		$notices         = wc_get_notices( 'notice' );
		$notice_messages = wp_list_pluck( $notices, 'notice' );
		$found           = false;
		foreach ( $notice_messages as $message ) {
			if ( strpos( $message, 'Tax rate applied' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected tax rate applied notice' );

		\WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Create a cart with a simple product and customer.
	 *
	 * @return \WC_Cart
	 */
	private function create_cart_with_product() {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_tax_status( 'taxable' );
		$product->save();

		$this->created_products[] = $product;

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );

		$customer = WC()->customer;
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_state( 'CA' );
		$customer->set_shipping_postcode( '94110' );
		$customer->set_shipping_city( 'San Francisco' );
		$customer->set_billing_country( 'US' );
		$customer->set_billing_state( 'NY' );
		$customer->set_billing_postcode( '10001' );
		$customer->set_billing_city( 'New York' );

		return WC()->cart;
	}

	/**
	 * Create a tax rate.
	 *
	 * @param string $country  Country code.
	 * @param string $state    State code.
	 * @param string $rate     Tax rate percentage.
	 * @param string $name     Tax rate name.
	 * @return int Tax rate ID.
	 */
	private function create_tax_rate( $country, $state, $rate, $name = 'Tax' ) {
		return \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => $country,
				'tax_rate_state'    => $state,
				'tax_rate'          => $rate,
				'tax_rate_name'     => $name,
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
	}
}

<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Internal\Tax\ShopBaseTaxEstimateNotice;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Tests for the ShopBaseTaxEstimateNotice class.
 */
class ShopBaseTaxEstimateNoticeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShopBaseTaxEstimateNotice
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShopBaseTaxEstimateNotice();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_postcode', '94110' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		delete_option( 'wc_connect_taxes_enabled' );
	}

	/**
	 * Insert a standard-class tax rate, optionally scoped to postcodes or cities.
	 *
	 * @param array    $overrides Rate field overrides.
	 * @param string[] $postcodes Postcodes to scope the rate to.
	 * @param string[] $cities    Cities to scope the rate to.
	 * @return int
	 */
	private function add_standard_rate( array $overrides = array(), array $postcodes = array(), array $cities = array() ): int {
		$rate_id = WC_Tax::_insert_tax_rate(
			array_merge(
				array(
					'tax_rate_country'  => 'US',
					'tax_rate_state'    => 'CA',
					'tax_rate'          => '20.0000',
					'tax_rate_name'     => 'CA',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 1,
					'tax_rate_order'    => 0,
					'tax_rate_class'    => '',
				),
				$overrides
			)
		);

		if ( ! empty( $postcodes ) ) {
			WC_Tax::_update_tax_rate_postcodes( $rate_id, implode( ';', $postcodes ) );
		}
		if ( ! empty( $cities ) ) {
			WC_Tax::_update_tax_rate_cities( $rate_id, implode( ';', $cities ) );
		}

		return $rate_id;
	}

	/**
	 * @testdox Shows nothing when taxes are disabled.
	 */
	public function test_hidden_when_taxes_disabled(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );

		$this->assertSame(
			array(
				'estimate' => '',
				'warning'  => '',
			),
			$this->sut->get_notice()
		);
	}

	/**
	 * @testdox Hides by default when an automated tax provider is enabled.
	 */
	public function test_hidden_when_automated_taxes_enabled(): void {
		update_option( 'wc_connect_taxes_enabled', 'yes' );

		$this->assertSame(
			array(
				'estimate' => '',
				'warning'  => '',
			),
			$this->sut->get_notice()
		);
	}

	/**
	 * @testdox The automated-tax default can be overridden with the filter.
	 */
	public function test_filter_can_force_the_estimate_on(): void {
		update_option( 'wc_connect_taxes_enabled', 'yes' );
		add_filter( 'woocommerce_show_shop_base_tax_estimate', '__return_true' );

		$notice = $this->sut->get_notice();
		remove_filter( 'woocommerce_show_shop_base_tax_estimate', '__return_true' );

		$this->assertNotSame( '', $notice['estimate'] );
	}

	/**
	 * @testdox Explains that no tax is shown when there is no default customer location.
	 */
	public function test_no_location_message(): void {
		update_option( 'woocommerce_default_customer_address', '' );

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'No tax is shown until the customer enters their address', $notice['estimate'] );
		$this->assertSame( '', $notice['warning'] );
	}

	/**
	 * @testdox Explains that the estimate comes from the detected location when geolocating.
	 */
	public function test_geolocation_message(): void {
		update_option( 'woocommerce_default_customer_address', 'geolocation' );

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'estimated from their detected country and state', $notice['estimate'] );
		$this->assertSame( '', $notice['warning'] );
	}

	/**
	 * @testdox Shows the store address, the per-class rates, and a way to change it for customer-based tax.
	 */
	public function test_shop_base_customer_mode_shows_rates(): void {
		$this->add_standard_rate();

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'tax is estimated at your store base address', $notice['estimate'] );
		$this->assertStringContainsString( 'San Francisco, 94110', $notice['estimate'] );
		$this->assertStringContainsString( 'Standard rate: 20%', $notice['estimate'] );
		$this->assertStringContainsString( 'Change this with Default customer location', $notice['estimate'] );
		$this->assertSame( '', $notice['warning'] );
	}

	/**
	 * @testdox Says all orders are taxed at the base and omits the change hint in shop-base tax mode.
	 */
	public function test_shop_base_tax_mode_message(): void {
		update_option( 'woocommerce_tax_based_on', 'base' );
		$this->add_standard_rate();

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'All orders are taxed at your store base address', $notice['estimate'] );
		$this->assertStringNotContainsString( 'Change this with Default customer location', $notice['estimate'] );
	}

	/**
	 * @testdox Shows a country-and-state lead when the store has no postcode or city.
	 */
	public function test_empty_store_address_shows_country_and_state_lead(): void {
		update_option( 'woocommerce_store_postcode', '' );
		update_option( 'woocommerce_store_city', '' );
		$this->add_standard_rate();

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'country and state only', $notice['estimate'] );
		$this->assertSame( '', $notice['warning'] );
	}

	/**
	 * @testdox The no-rate warning drops the un-addressed qualifier in shop-base tax mode.
	 */
	public function test_shop_base_tax_mode_no_rate_warning(): void {
		update_option( 'woocommerce_tax_based_on', 'base' );

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'No standard tax rate matches your store address', $notice['warning'] );
		$this->assertStringNotContainsString( 'has not entered an address', $notice['warning'] );
	}

	/**
	 * @testdox The rate summary reflects the effective rate when a compound rate applies.
	 */
	public function test_rate_summary_reflects_compound_rates(): void {
		$this->add_standard_rate(
			array(
				'tax_rate'          => '10.0000',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
			)
		);
		$this->add_standard_rate(
			array(
				'tax_rate'          => '10.0000',
				'tax_rate_priority' => 2,
				'tax_rate_compound' => 1,
			)
		);

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'Standard rate: 21%', $notice['estimate'] );
	}

	/**
	 * @testdox Warns when no standard rate matches the store address.
	 */
	public function test_warns_when_no_rate_matches(): void {
		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'No standard tax rate matches your store address', $notice['warning'] );
	}

	/**
	 * @testdox Warns specifically when scoped rates exist but the store has no postcode or city.
	 */
	public function test_warns_when_scoped_rates_but_no_store_address(): void {
		$this->add_standard_rate( array(), array( '94110' ) );
		update_option( 'woocommerce_store_postcode', '' );
		update_option( 'woocommerce_store_city', '' );

		$notice = $this->sut->get_notice();

		$this->assertStringContainsString( 'postcode- or city-scoped standard rates', $notice['warning'] );
	}
}

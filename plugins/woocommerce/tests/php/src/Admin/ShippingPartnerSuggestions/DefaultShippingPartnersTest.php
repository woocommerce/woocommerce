<?php

namespace Automattic\WooCommerce\Tests\Admin\Features\ShippingPartnerSuggestions;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\DefaultShippingPartners;
use WC_Unit_Test_Case;

/**
 * DefaultShippingPartners test.
 *
 * @class DefaultShippingPartnersTest
 */
class DefaultShippingPartnersTest extends WC_Unit_Test_Case {

	/**
	 * Set things up before each test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_default_country', 'US:CA' );

		/*
		 * Required for the BaseLocationCountryRuleProcessor
		 * to not return false for "US:CA" country-state combo.
		 */
		update_option( 'woocommerce_store_address', 'foo' );

		update_option( 'active_plugins', array( 'foo/foo.php' ) );
	}

	/**
	 * Tests if in a default situation there are no errors.
	 *
	 * @return void
	 */
	public function test_it_evaluates_with_no_errors() {
		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
	}

	/**
	 * Tests if WCS&T is present by default.
	 *
	 * @return void
	 */
	public function test_wcservices_is_present() {
		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 1, $results['suggestions'] );
		$this->assertEquals( 'woocommerce-services', $results['suggestions'][0]->id );
	}

	/**
	 * Asserts WCS&T is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcservices_is_absent_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}

	/**
	 * Asserts no extensions are recommended if WooCommerce Shipping is active.
	 *
	 * @return void
	 */
	public function test_no_extensions_are_recommended_if_woocommerce_shipping_is_active() {
		update_option( 'active_plugins', array( 'woocommerce-shipping/woocommerce-shipping.php' ) );

		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}

	/**
	 * Asserts no extensions are recommended if WooCommerce Tax is active.
	 *
	 * @return void
	 */
	public function test_no_extensions_are_recommended_if_woocommerce_tax_is_active() {
		update_option( 'active_plugins', array( 'woocommerce-tax/woocommerce-tax.php' ) );

		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}

	/**
	 * Asserts no extensions are recommended if both WooCommerce Shipping and WooCommerce Tax are active.
	 *
	 * @return void
	 */
	public function test_no_extensions_are_recommended_if_both_woocommerce_shipping_and_woocommerce_tax_are_active() {
		update_option( 'active_plugins', array( 'woocommerce-shipping/woocommerce-shipping.php', 'woocommerce-tax/woocommerce-tax.php' ) );

		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}
}

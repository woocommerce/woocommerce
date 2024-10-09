<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\DefaultPromotions;
use WC_Unit_Test_Case;

/**
 * DefaultPromotionsTest test.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\WCPayPromotion\DefaultPromotions
 */
class DefaultPromotionsTest extends WC_Unit_Test_Case {

	/**
	 * Set up.
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

		EvaluateSuggestion::reset_memo();
	}

	/**
	 * Tests if in a default situation there are no errors.
	 *
	 * @return void
	 */
	public function test_it_evaluates_with_no_errors() {
		$specs   = DefaultPromotions::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
	}

	/**
	 * Tests if WooPayments WooPay is present by default.
	 *
	 * @return void
	 */
	public function test_woopay_recommendation_is_present() {
		$specs   = DefaultPromotions::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 2, $results['suggestions'] );
		$this->assertEquals( 'woocommerce_payments:woopay', $results['suggestions'][0]->id );
	}

	/**
	 * Tests if WooPayments WooPay is NOT present in unsupported country.
	 *
	 * @return void
	 */
	public function test_woopay_recommendation_is_not_present() {
		update_option( 'woocommerce_default_country', 'RO' );

		$specs   = DefaultPromotions::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 1, $results['suggestions'] );
		$this->assertEquals( 'woocommerce_payments', $results['suggestions'][0]->id );
	}

	/**
	 * Asserts WooPayments is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_no_recommendations_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$specs   = DefaultPromotions::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}
}

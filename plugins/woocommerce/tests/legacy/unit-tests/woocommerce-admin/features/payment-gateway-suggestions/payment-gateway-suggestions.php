<?php
/**
 * Test the class that parses the payment suggestions.
 *
 * @package WooCommerce\Admin\Tests\PaymentGatewaySuggestions
 */

use Automattic\WooCommerce\Admin\DataSourcePoller;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init as PaymentGatewaySuggestions;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\PaymentGatewaySuggestionsDataSourcePoller;

/**
 * class WC_Admin_Tests_PaymentGatewaySuggestions_Init
 */
class WC_Admin_Tests_PaymentGatewaySuggestions_Init extends WC_Unit_Test_Case {

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		delete_option( 'woocommerce_show_marketplace_suggestions' );
		add_filter(
			'transient_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			function( $value ) {
				if ( $value ) {
					return $value;
				}

				return array(
					array(
						'id' => 'default-gateway',
					),
				);
			}
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		PaymentGatewaySuggestions::delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );
	}

	/**
	 * Add test specs.
	 */
	public function get_mock_specs() {
		return array(
			'en_US' => array(
				array(
					'id'         => 'mock-gateway',
					'is_visible' => (object) array(
						'type'      => 'base_location_country',
						'value'     => 'ZA',
						'operation' => '=',
					),
				),
			),
		);
	}

	/**
	 * Test that default gateways are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_all_filters( 'transient_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function() {
				return array();
			}
		);
		$specs    = PaymentGatewaySuggestions::get_specs();
		$defaults = DefaultPaymentGateways::get_all();
		remove_all_filters( DataSourcePoller::FILTER_NAME );
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id' => 'mock-gateway1',
					),
					array(
						'id' => 'mock-gateway2',
					),
				),
			)
		);
		$suggestions = PaymentGatewaySuggestions::get_suggestions();
		$this->assertCount( 2, $suggestions );
	}

	/**
	 * Test that non-matched suggestions are not shown.
	 */
	public function test_non_matching_suggestions() {
		update_option( 'woocommerce_default_country', 'US' );
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			$this->get_mock_specs()
		);
		$suggestions = PaymentGatewaySuggestions::get_suggestions();
		$this->assertCount( 0, $suggestions );
	}

	/**
	 * Test that matched suggestions are shown.
	 */
	public function test_matching_suggestions() {
		update_option( 'woocommerce_default_country', 'ZA' );
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			$this->get_mock_specs()
		);
		$suggestions = PaymentGatewaySuggestions::get_suggestions();
		$this->assertEquals( 'mock-gateway', $suggestions[0]->id );
	}

	/**
	 * Test that matched locale specs are read from cache.
	 */
	public function test_specs_locale_transient() {
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id' => 'mock-gateway',
					),
				),
				'zh_TW' => array(
					array(
						'id' => 'default-gateway',
					),
				),
			)
		);

		add_filter(
			'locale',
			function( $_locale ) {
				return 'zh_TW';
			}
		);

		$suggestions = PaymentGatewaySuggestions::get_suggestions();
		$this->assertEquals( 'default-gateway', $suggestions[0]->id );
	}

	/**
	 * Test that the suggestions can be displayed when a user has marketplace
	 * suggestions enabled and is a user capable of installing plugins.
	 */
	public function test_should_display() {
		update_option( 'woocommerce_show_marketplace_suggestions', 'yes' );
		$this->assertTrue( PaymentGatewaySuggestions::should_display() );
	}

	/**
	 * Test that suggestions are not shown when the marketplace suggestions are off.
	 */
	public function test_should_not_display_when_marketplace_suggestions_off() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
		$this->assertFalse( PaymentGatewaySuggestions::should_display() );
	}

	/**
	 * Test dismissing suggestions.
	 */
	public function test_dismiss() {
		$this->assertEquals( 'no', get_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION, 'no' ) );
		wp_set_current_user( $this->user );

		PaymentGatewaySuggestions::dismiss();

		$this->assertEquals( 'yes', get_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION ) );
		delete_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION );
	}

}

<?php
/**
 * Test the class that parses the payment suggestions.
 *
 * @package WooCommerce\Admin\Tests\PaymentGatewaySuggestions
 */

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
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
		PaymentGatewaySuggestions::delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );

		parent::tearDown();
	}

	/**
	 * Test that default specs are used when remote specs are empty.
	 */
	public function test_use_default_specs_when_remote_specs_empty() {
		// Arrange.
		remove_all_filters( 'transient_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function() {
				return array();
			}
		);

		// Act.
		$specs = PaymentGatewaySuggestions::get_specs();

		// Assert.
		$defaults = DefaultPaymentGateways::get_all();
		$this->assertEquals( $defaults, $specs );

		// Clean up.
		remove_all_filters( DataSourcePoller::FILTER_NAME );
	}

	/**
	 * Test that default gateways are provided when remote sources don't exist.
	 */
	public function test_use_default_specs_when_marketplace_suggestions_off() {
		// Arrange.
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

		// Act.
		$specs    = PaymentGatewaySuggestions::get_specs();
		$defaults = DefaultPaymentGateways::get_all();

		// Assert.
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		// Arrange.
		$expected_suggestions = array(
			array(
				'id' => 'mock-gateway1',
			),
			array(
				'id' => 'mock-gateway2',
			),
		);
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => $expected_suggestions,
			)
		);

		// Act.
		$suggestions = PaymentGatewaySuggestions::get_suggestions();

		// Assert.
		$this->assertCount( count( $expected_suggestions ), $suggestions );
		$this->assertEquals( $expected_suggestions[0]['id'], $suggestions[0]->id );
		$this->assertEquals( $expected_suggestions[1]['id'], $suggestions[1]->id );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_cached_or_default_suggestions_when_cache_exist() {
		// Arrange.
		$expected_suggestions = array(
			array(
				'id' => 'mock-gateway1',
			),
			array(
				'id' => 'mock-gateway2',
			),
		);
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => $expected_suggestions,
			)
		);

		// Act.
		$suggestions = PaymentGatewaySuggestions::get_cached_or_default_suggestions();

		// Assert.
		$this->assertCount( count( $expected_suggestions ), $suggestions );
		$this->assertEquals( $expected_suggestions[0]['id'], $suggestions[0]->id );
		$this->assertEquals( $expected_suggestions[1]['id'], $suggestions[1]->id );
	}

	/**
	 * Test that specs are read from default when cache is empty.
	 */
	public function test_cached_or_default_suggestions_when_cache_empty() {
		// Arrange.
		PaymentGatewaySuggestionsDataSourcePoller::get_instance()->delete_specs_transient();

		// Act.
		$suggestions = PaymentGatewaySuggestions::get_cached_or_default_suggestions();

		// Assert.
		$default_suggestions = EvaluateSuggestion::evaluate_specs( DefaultPaymentGateways::get_all() )['suggestions'];

		$this->assertEquals( $default_suggestions, $suggestions );
	}


	/**
	 * Test that default gateways are provided when remote sources don't exist.
	 */
	public function test_cached_or_default_suggestions_when_marketplace_suggestions_off() {
		// Arrange.
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
		PaymentGatewaySuggestionsDataSourcePoller::get_instance()->delete_specs_transient();

		// Act.
		$suggestions         = PaymentGatewaySuggestions::get_cached_or_default_suggestions();
		$default_suggestions = EvaluateSuggestion::evaluate_specs( DefaultPaymentGateways::get_all() )['suggestions'];

		// Assert.
		$this->assertEquals( $suggestions, $default_suggestions );
	}


	/**
	 * Test that non-matched suggestions are not shown.
	 */
	public function test_matching_suggestions() {
		// Arrange.
		update_option( 'woocommerce_default_country', 'US' );
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id'         => 'mock-gateway-1',
						'is_visible' => (object) array(
							'type'      => 'base_location_country',
							'value'     => 'ZA',
							'operation' => '=',
						),
					),
					array(
						'id'         => 'mock-gateway-2',
						'is_visible' => (object) array(
							'type'      => 'base_location_country',
							'value'     => 'US',
							'operation' => '=',
						),
					),
				),
			)
		);

		// Act.
		$suggestions = PaymentGatewaySuggestions::get_suggestions();

		// Assert.
		// Only the second suggestion should be returned since it matches the store base country.
		$this->assertCount( 1, $suggestions );
		$this->assertEquals( 'mock-gateway-2', $suggestions[0]->id );
	}

	/**
	 * Test that matched locale specs are read from cache.
	 */
	public function test_specs_locale_transient() {
		// Arrange.
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id' => 'us-gateway',
					),
				),
				'zh_TW' => array(
					array(
						'id' => 'tw-gateway',
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

		// Act.
		$suggestions = PaymentGatewaySuggestions::get_suggestions();

		// Assert.
		$this->assertEquals( 'tw-gateway', $suggestions[0]->id );
	}

	/**
	 * Test that empty remote suggestions fallback to defaults.
	 */
	public function test_empty_remote_suggestions_fallback_to_defaults() {
		// Arrange.
		update_option( 'woocommerce_default_country', 'US' );
		// Make sure there are no specs in the transient.
		set_transient(
			'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(),
			)
		);

		// Replace the external data sources.
		add_filter(
			PaymentGatewaySuggestionsDataSourcePoller::FILTER_NAME,
			function () {
				return array(
					'mock-payment-gateway-suggestions-data-source.json',
				);
			}
		);

		// Intercept the request to the data source and return a non-empty array to allow us to
		// skip defaulting to the default payment gateways suggestions too early.
		add_filter(
			'pre_http_request',
			function ( $pre, $parsed_args, $url ) {
				if ( false !== strpos( $url, 'mock-payment-gateway-suggestions-data-source.json' ) ) {
					return array(
						'body' => wp_json_encode(
							array(
								array(
									'id' => 'mock-gateway1',
								),
								array(
									'id' => 'mock-gateway2',
								),
							)
						),
					);
				}

				return $pre;
			},
			10,
			3
		);

		// Finally return empty specs that should default the suggestions to the default payment gateways suggestions.
		add_filter(
			'woocommerce_admin_payment_gateway_suggestion_specs',
			function () {
				return array();
			}
		);

		// Act.
		$suggestions               = PaymentGatewaySuggestions::get_suggestions();
		$stored_specs_in_transient = get_transient( 'woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );

		// Assert.
		$default_specs       = DefaultPaymentGateways::get_all();
		$default_suggestions = EvaluateSuggestion::evaluate_specs( $default_specs )['suggestions'];

		$this->assertEquals( $default_suggestions, $suggestions );

		$this->assertEquals( $stored_specs_in_transient['en_US'], $default_specs );
		$expires = (int) get_transient( '_transient_timeout_woocommerce_admin_' . PaymentGatewaySuggestionsDataSourcePoller::ID . '_specs' );
		$this->assertTrue( ( $expires - time() ) <= 3 * HOUR_IN_SECONDS );

		// Clean up.
		remove_all_filters( PaymentGatewaySuggestionsDataSourcePoller::FILTER_NAME );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'woocommerce_admin_payment_gateway_suggestion_specs' );
	}

	/**
	 * Test that the suggestions can be displayed when a user has marketplace suggestions enabled.
	 */
	public function test_should_display() {
		update_option( 'woocommerce_show_marketplace_suggestions', 'yes' );
		$this->assertTrue( PaymentGatewaySuggestions::should_display() );
	}

	/**
	 * Test that suggestions are not shown when the marketplace suggestions are off.
	 */
	public function test_should_not_display_when_marketplace_suggestions_off() {
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
		$this->assertFalse( PaymentGatewaySuggestions::should_display() );
	}

	/**
	 * Test dismissing suggestions.
	 */
	public function test_dismiss() {
		// Arrange.
		delete_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION );

		// Act.
		PaymentGatewaySuggestions::dismiss();

		// Assert.
		$this->assertEquals( 'yes', get_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION ) );

		// Clean up.
		delete_option( PaymentGatewaySuggestions::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION );
	}

}

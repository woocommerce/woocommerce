<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ShippingPartnerSuggestions;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Shipping;
use Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\DefaultShippingPartners;
use Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\ShippingPartnerSuggestions;
use Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\ShippingPartnerSuggestionsDataSourcePoller;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use WC_Unit_Test_Case;

/**
 * class WC_Admin_Tests_ShippingPartnerSuggestions_Init
 *
 * @covers \Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\ShippingPartnerSuggestions
 */
class ShippingPartnerSuggestionsTest extends WC_Unit_Test_Case {

	/**
	 * The mock logger.
	 *
	 * @var WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_logger;

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
			'transient_woocommerce_admin_' . ShippingPartnerSuggestionsDataSourcePoller::ID . '_specs',
			function ( $value ) {
				if ( $value ) {
					return $value;
				}

				$locale = get_user_locale();

				return array(
					$locale => array(
						(object) array(
							'id'         => 'mock-shipping-partner-1',
							'is_visible' => (object) array(
								'type'      => 'base_location_country',
								'value'     => 'ZA',
								'operation' => '=',
							),
						),
						(object) array(
							'id'         => 'mock-shipping-partner-2',
							'is_visible' => (object) array(
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							),
						),
					),
				);
			}
		);

		// Have a mock logger used by the suggestions rule evaluator.
		$this->mock_logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		add_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		EvaluateSuggestion::reset_memo();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		ShippingPartnerSuggestionsDataSourcePoller::get_instance()->delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . ShippingPartnerSuggestionsDataSourcePoller::ID . '_specs' );
		update_option( 'woocommerce_default_country', 'US' );
		remove_filter( 'woocommerce_logging_class', array( $this, 'override_wc_logger' ) );

		parent::tearDown();
	}

	/**
	 * Test that default specs are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_all_filters( 'transient_woocommerce_admin_' . ShippingPartnerSuggestionsDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function () {
				return array();
			}
		);
		$specs    = ShippingPartnerSuggestions::get_specs();
		$defaults = DefaultShippingPartners::get_all();
		remove_all_filters( DataSourcePoller::FILTER_NAME );
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		set_transient(
			'woocommerce_admin_' . ShippingPartnerSuggestionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id' => 'mock1',
					),
					array(
						'id' => 'mock2',
					),
				),
			)
		);
		$specs = ShippingPartnerSuggestions::get_specs();
		$this->assertCount( 2, $specs );
	}

	/**
	 * Test that non-matching suggestions are not shown.
	 */
	public function test_non_matching_suggestions() {
		update_option( 'woocommerce_default_country', 'US' );
		$suggestions = ShippingPartnerSuggestions::get_suggestions();
		$this->assertCount( 1, $suggestions );
	}

	/**
	 * Test that matched suggestions are shown.
	 */
	public function test_matching_suggestions() {
		update_option( 'woocommerce_default_country', 'ZA' );
		$suggestions = ShippingPartnerSuggestions::get_suggestions();
		$this->assertEquals( 'mock-shipping-partner-1', $suggestions[0]->id );
	}

	/**
	 * Test that suggestions rules evaluations are logged.
	 */
	public function test_suggestion_evaluations_are_logged() {
		add_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );

		$logger_debug_calls_args = array(
			array(
				'[mock-shipping-partner-1] base_location_country: passed',
				array( 'source' => 'wc-shipping-partner-suggestions' ),
			),
			array(
				'[mock-shipping-partner-2] base_location_country: failed',
				array( 'source' => 'wc-shipping-partner-suggestions' ),
			),
		);
		$this->mock_logger
			->expects( $this->exactly( count( $logger_debug_calls_args ) ) )
			->method( 'debug' )
			->willReturnCallback(
				function ( ...$args ) use ( &$logger_debug_calls_args ) {
					$expected_args = array_shift( $logger_debug_calls_args );
					$this->assertSame( $expected_args, $args );
				}
			);

		update_option( 'woocommerce_default_country', 'ZA' );
		ShippingPartnerSuggestions::get_suggestions();

		remove_filter( 'woocommerce_admin_remote_specs_evaluator_should_log', '__return_true' );
	}

	/**
	 * Overrides the WC logger.
	 *
	 * @return mixed
	 */
	public function override_wc_logger() {
		return $this->mock_logger;
	}
}

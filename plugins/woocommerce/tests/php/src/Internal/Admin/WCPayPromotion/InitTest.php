<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;

use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\DefaultPromotions;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init as WCPayPromotion;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\WCPayPromotionDataSourcePoller;
use WC_Unit_Test_Case;

/**
 * class WC_Admin_Tests_WCPayPromotion_Init
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init
 */
class InitTest extends WC_Unit_Test_Case {

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( 'woocommerce_show_marketplace_suggestions' );
		add_filter(
			'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			function ( $value ) {
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

		EvaluateSuggestion::reset_memo();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		WCPayPromotion::delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );
		update_option( 'woocommerce_default_country', 'US' );

		parent::tearDown();
	}

	/**
	 * Test that default specs are used when remote specs are empty.
	 */
	public function test_use_default_specs_when_remote_specs_empty() {
		// Arrange.
		remove_all_filters( 'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function () {
				return array();
			}
		);

		// Act.
		$specs = WCPayPromotion::get_specs();

		// Assert.
		$defaults = DefaultPromotions::get_all();
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
		$specs    = WCPayPromotion::get_specs();
		$defaults = DefaultPromotions::get_all();

		// Assert.
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		// Arrange.
		$expected_promotions = array(
			array(
				'id' => 'mock-gateway1',
			),
			array(
				'id' => 'mock-gateway2',
			),
		);
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			array(
				'en_US' => $expected_promotions,
			)
		);

		// Act.
		$promotions = WCPayPromotion::get_promotions();

		// Assert.
		$this->assertCount( count( $expected_promotions ), $promotions );
		$this->assertEquals( $expected_promotions[0]['id'], $promotions[0]->id );
		$this->assertEquals( $expected_promotions[1]['id'], $promotions[1]->id );
	}

	/**
	 * Test that matched promotions are shown.
	 */
	public function test_matching_promotions() {
		// Arrange.
		update_option( 'woocommerce_default_country', 'US' );
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id'         => 'mock-promotion-1',
						'is_visible' => (object) array(
							'type'      => 'base_location_country',
							'value'     => 'ZA',
							'operation' => '=',
						),
					),
					array(
						'id'         => 'mock-promotion-2',
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
		$promotions = WCPayPromotion::get_promotions();

		// Assert.
		// Only the second promotion should be returned since it matches the store base country.
		$this->assertCount( 1, $promotions );
		$this->assertEquals( 'mock-promotion-2', $promotions[0]->id );
	}

	/**
	 * Test that non-matching promotions are not shown.
	 */
	public function test_non_matching_promotions() {
		// Arrange.
		// Use a bogus country code so no suggestions match.
		update_option( 'woocommerce_default_country', 'XX' );
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'id'         => 'mock-promotion-1',
						'is_visible' => (object) array(
							'type'      => 'base_location_country',
							'value'     => 'ZA',
							'operation' => '=',
						),
					),
					array(
						'id'         => 'mock-promotion-2',
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
		$promotions = WCPayPromotion::get_promotions();

		// Assert.
		$this->assertCount( 0, $promotions );
	}

	/**
	 * Test that matched locale specs are read from cache.
	 */
	public function test_specs_locale_transient() {
		// Arrange.
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
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
			function () {
				return 'zh_TW';
			}
		);

		// Act.
		$promotions = WCPayPromotion::get_promotions();

		// Assert.
		$this->assertEquals( 'tw-gateway', $promotions[0]->id );
	}

	/**
	 * Test that empty remote promotions fallback to defaults.
	 */
	public function test_empty_remote_promotions_fallback_to_defaults() {
		// Arrange.
		update_option( 'woocommerce_default_country', 'US' );
		update_option( 'woocommerce_show_marketplace_suggestions', 'yes' );
		// Make sure there are no specs in the transient.
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(),
			)
		);

		// Replace the external data sources.
		add_filter(
			WCPayPromotionDataSourcePoller::FILTER_NAME,
			function () {
				return array(
					'mock-woopayments-promotions-data-source.json',
				);
			}
		);

		// Intercept the request to the data source and return a specs that will not match the store base country,
		// so we end up with no promotions and we expect the default ones to be returned
		// since they will match the store base country.
		add_filter(
			'pre_http_request',
			function ( $pre, $parsed_args, $url ) {
				if ( false !== strpos( $url, 'mock-woopayments-promotions-data-source.json' ) ) {
					return array(
						'body' => wp_json_encode(
							array(
								array(
									'id'         => 'mock-promotion-1',
									'is_visible' => (object) array(
										'type'      => 'base_location_country',
										'value'     => 'ZA',
										'operation' => '=',
									),
								),
								array(
									'id'         => 'mock-promotion-2',
									'is_visible' => (object) array(
										'type'      => 'base_location_country',
										'value'     => 'RO',
										'operation' => '=',
									),
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

		// Act.
		$promotions                = WCPayPromotion::get_promotions();
		$stored_specs_in_transient = get_transient( 'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );

		// Assert.
		$default_specs      = DefaultPromotions::get_all();
		$default_promotions = EvaluateSuggestion::evaluate_specs( $default_specs )['suggestions'];

		$this->assertTrue( count( $stored_specs_in_transient['en_US'] ) === 0 );
		$this->assertEquals( $default_promotions, $promotions );

		$expires = (int) get_transient( '_transient_timeout_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );
		$this->assertTrue( ( $expires - time() ) <= 3 * HOUR_IN_SECONDS );

		// Clean up.
		remove_all_filters( WCPayPromotionDataSourcePoller::FILTER_NAME );
		remove_all_filters( WCPayPromotionDataSourcePoller::FILTER_NAME_SPECS );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'woocommerce_admin_payment_gateway_suggestion_specs' );
	}
}

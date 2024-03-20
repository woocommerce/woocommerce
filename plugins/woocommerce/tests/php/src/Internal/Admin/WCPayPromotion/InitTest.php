<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;

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

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		delete_option( 'woocommerce_show_marketplace_suggestions' );
		add_filter(
			'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
			function( $value ) {
				if ( $value ) {
					return $value;
				}

				$locale = get_user_locale();

				return array(
					$locale => array(
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
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WCPayPromotion::delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );
		update_option( 'woocommerce_default_country', 'US' );
	}

	/**
	 * Test that default specs are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_all_filters( 'transient_woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function() {
				return array();
			}
		);
		$specs = WCPayPromotion::get_specs();
		remove_all_filters( DataSourcePoller::FILTER_NAME );
		$this->assertEquals( array(), $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		set_transient(
			'woocommerce_admin_' . WCPayPromotionDataSourcePoller::ID . '_specs',
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
		$specs = WCPayPromotion::get_specs();
		$this->assertCount( 2, $specs );
	}

	/**
	 * Test that non-matching suggestions are not shown.
	 */
	public function test_non_matching_extensions() {
		update_option( 'woocommerce_default_country', 'US' );
		$promotions = WCPayPromotion::get_promotions();
		$this->assertCount( 0, $promotions );
	}

	/**
	 * Test that matched suggestions are shown.
	 */
	public function test_matching_suggestions() {
		update_option( 'woocommerce_default_country', 'ZA' );
		$promotions = WCPayPromotion::get_promotions();
		$this->assertEquals( 'mock-gateway', $promotions[0]->id );
	}
}

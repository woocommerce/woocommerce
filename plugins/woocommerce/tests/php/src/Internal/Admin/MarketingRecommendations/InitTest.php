<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ShippingPartnerSuggestions;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use Automattic\WooCommerce\Admin\Features\MarketingRecommendations\Init;
use Automattic\WooCommerce\Admin\Features\MarketingRecommendations\DefaultMarketingRecommendations;
use Automattic\WooCommerce\Admin\Features\MarketingRecommendations\MarketingRecommendationsDataSourcePoller;
use WC_Unit_Test_Case;

/**
 * class WC_Admin_Tests_MarketingRecommendations_Init
 *
 * @covers \Automattic\WooCommerce\Admin\Features\MarketingRecommendations\Init
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
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		MarketingRecommendationsDataSourcePoller::get_instance()->delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . MarketingRecommendationsDataSourcePoller::ID . '_specs' );
	}

	/**
	 * Test that default specs are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_all_filters( 'transient_woocommerce_admin_' . MarketingRecommendationsDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function() {
				return array();
			}
		);
		$specs    = Init::get_specs();
		$defaults = DefaultMarketingRecommendations::get_all();
		remove_all_filters( DataSourcePoller::FILTER_NAME );
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		set_transient(
			'woocommerce_admin_' . MarketingRecommendationsDataSourcePoller::ID . '_specs',
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
		$specs = Init::get_specs();
		$this->assertCount( 2, $specs );
	}

	/**
	 * Test that recursive objects does not cause an error and returns null for the recursive child.
	 */
	public function test_matching_suggestions() {
		$node1        = new \StdClass();
		$node2        = new \StdClass();
		$node1->child = $node2;
		$node2->child = $node1;
		$result       = Init::object_to_array( $node1 );
		$this->assertEquals( array( 'child' => array( 'child' => null ) ), $result );
	}
}

<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init as RemoteFreeExtensions;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\DefaultFreeExtensions;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\RemoteFreeExtensionsDataSourcePoller;
use WC_Unit_Test_Case;

/**
 * class WC_Admin_Tests_RemoteFreeExtensions_Init
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init
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
			'transient_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs',
			function( $value ) {
				if ( $value ) {
					return $value;
				}

				$locale = get_user_locale();

				return array(
					$locale => array(
						array(
							'key'     => 'obw/basics',
							'title'   => __( 'Get the basics', 'woocommerce' ),
							'plugins' => array(
								array(
									'name'       => 'mock-extension-1',
									'key'        => 'mock-extension-1',
									'is_visible' => (object) array(
										'type'      => 'base_location_country',
										'value'     => 'ZA',
										'operation' => '=',
									),
								),
								array(
									'name'       => 'mock-extension-2',
									'key'        => 'mock-extension-2',
									'is_visible' => (object) array(
										'type'      => 'base_location_country',
										'value'     => 'US',
										'operation' => '=',
									),
								),
							),
						),
					),
				);
			},
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		RemoteFreeExtensions::delete_specs_transient();
		remove_all_filters( 'transient_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );
		update_option( 'woocommerce_default_country', 'US' );
	}

	/**
	 * Test that default extensions are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_all_filters( 'transient_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );
		add_filter(
			DataSourcePoller::FILTER_NAME,
			function() {
				return array();
			}
		);
		$specs    = RemoteFreeExtensions::get_specs();
		$defaults = DefaultFreeExtensions::get_all();
		remove_all_filters( DataSourcePoller::FILTER_NAME );
		$this->assertEquals( $defaults, $specs );
	}

	/**
	 * Test that specs are read from cache when they exist.
	 */
	public function test_specs_transient() {
		set_transient(
			'woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(
					array(
						'name' => 'mock1',
					),
					array(
						'name' => 'mock2',
					),
				),
			)
		);
		$specs = RemoteFreeExtensions::get_specs();
		$this->assertCount( 2, $specs );
	}


	/**
	 * Test that matched extensions are shown.
	 */
	public function test_matching_extensions() {
		update_option( 'woocommerce_default_country', 'ZA' );
		$bundles = RemoteFreeExtensions::get_extensions();
		$this->assertEquals( 'mock-extension-1', $bundles[0]['plugins'][0]->name );
		$this->assertCount( 1, $bundles[0]['plugins'] );
	}

	/**
	 * Test that empty bundles are replaced with defaults.
	 */
	public function test_empty_extensions() {
		set_transient(
			'woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs',
			array(
				'en_US' => array(),
			)
		);

		$bundles           = RemoteFreeExtensions::get_extensions();
		$stored_transients = get_transient( 'woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );
		$this->assertTrue( count( $bundles ) > 1 );
		$this->assertEquals( count( $stored_transients['en_US'] ), count( DefaultFreeExtensions::get_all() ) );

		$expires = (int) get_transient( '_transient_timeout_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );
		$this->assertTrue( ( $expires - time() ) < 3 * HOUR_IN_SECONDS );
	}
}

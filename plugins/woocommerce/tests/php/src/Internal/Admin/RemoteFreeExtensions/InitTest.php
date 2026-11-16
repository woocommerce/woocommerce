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
	/** @var callable|null Filter used to provide local remote-extension specs. */
	private $specs_transient_filter;

	/** @var bool Whether child fixture setup reached its mutation phase. */
	private bool $fixture_setup_started = false;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->assertFalse( (bool) wp_using_ext_object_cache(), 'This test requires database-backed transients so that the rollback removes them.' );
		$this->assert_option_cache_matches_raw_state( 'woocommerce_default_country' );
		$this->assert_option_cache_matches_raw_state( 'woocommerce_show_marketplace_suggestions' );
		$this->fixture_setup_started = true;

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		delete_option( 'woocommerce_show_marketplace_suggestions' );
		$this->specs_transient_filter = function ( $value ) {
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
		};
		add_filter(
			'transient_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs',
			$this->specs_transient_filter,
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		try {
			if ( $this->fixture_setup_started ) {
				RemoteFreeExtensions::delete_specs_transient();
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Get the specs transient name.
	 *
	 * @return string
	 */
	private function get_specs_transient_name(): string {
		return 'woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs';
	}

	/**
	 * Read an option without default filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$wpdb->last_error = '';

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( "Failed to read option {$option_name}: {$wpdb->last_error}" ) );
		}

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Assert the option cache represents the row currently stored in the database.
	 *
	 * @param string $option_name Option name.
	 */
	private function assert_option_cache_matches_raw_state( string $option_name ): void {
		$state    = $this->get_raw_option_state( $option_name );
		$expected = $state['exists'] ? maybe_unserialize( $state['value'] ) : false;

		$this->assertSame( $expected, get_option( $option_name ) );
	}

	/**
	 * Test that default extensions are provided when remote sources don't exist.
	 */
	public function test_get_default_specs() {
		remove_filter( 'transient_' . $this->get_specs_transient_name(), $this->specs_transient_filter );
		$data_sources_filter = function () {
			return array();
		};
		add_filter( DataSourcePoller::FILTER_NAME, $data_sources_filter );
		try {
			$specs    = RemoteFreeExtensions::get_specs();
			$defaults = DefaultFreeExtensions::get_all();
			$this->assertEquals( $defaults, $specs );
		} finally {
			remove_filter( DataSourcePoller::FILTER_NAME, $data_sources_filter );
		}
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
		$defaults          = DefaultFreeExtensions::get_all();
		$stored_transients = get_transient( 'woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );

		$this->assertTrue( count( $stored_transients['en_US'] ) === 0 );
		$this->assertTrue( count( $bundles ) > 1 );
		$this->assertEquals( count( $bundles ), count( $defaults ) );
		foreach ( $bundles as $key => $bundle ) {
			$this->assertEquals( $defaults[ $key ]->key, $bundle['key'] );
		}

		$expires = (int) get_transient( '_transient_timeout_woocommerce_admin_' . RemoteFreeExtensionsDataSourcePoller::ID . '_specs' );
		$this->assertTrue( ( $expires - time() ) < 3 * HOUR_IN_SECONDS );
	}
}

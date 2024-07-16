<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\PluginUtil;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * A collection of tests for the PluginUtil class.
 */
class PluginUtilTests extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var PluginUtil
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_container_resolutions();
		$this->reset_legacy_proxy_mocks();

		$this->mock_plugin_functions();
		$this->sut = $this->get_instance_of( PluginUtil::class );
	}

	/**
	 * @testdox `get_all_active_valid_plugins` gets the active plugins *that actually exist* and returns them
	 *          as a list of absolute file paths.
	 *
	 * The tested function is just a wrapper around two core WP functions that are marked as "private" so this is
	 * mostly just to ensure that there haven't been any breaking changes to those functions.
	 */
	public function test_get_all_active_valid_plugins() {
		self::touch( WP_PLUGIN_DIR . '/test1/test1.php' );
		self::touch( WP_PLUGIN_DIR . '/test2/test2_x.php' );
		self::touch( WP_PLUGIN_DIR . '/test3/test3.php' );

		$orig_local_plugins   = get_option( 'active_plugins' );
		$orig_network_plugins = get_site_option( 'active_sitewide_plugins' );

		update_option(
			'active_plugins',
			array(
				'test1/test1.php',
				'test2/test2.php',
			)
		);

		update_site_option(
			'active_sitewide_plugins',
			array( 'test3/test3.php' )
		);

		$active_valid_plugins = $this->sut->get_all_active_valid_plugins();

		if ( is_multisite() ) {
			$this->assertCount( 2, $active_valid_plugins );
			$this->assertContains( WP_PLUGIN_DIR . '/test3/test3.php', $active_valid_plugins );
		} else {
			$this->assertCount( 1, $active_valid_plugins );
		}

		$this->assertContains( WP_PLUGIN_DIR . '/test1/test1.php', $active_valid_plugins );

		if ( false === $orig_local_plugins ) {
			delete_option( 'active_plugins' );
		} else {
			update_option( 'active_plugins', $orig_local_plugins );
		}

		if ( false === $orig_network_plugins ) {
			delete_site_option( 'active_sitewide_plugins' );
		} else {
			update_site_option( 'active_sitewide_plugins', $orig_network_plugins );
		}

		$this->rmdir( WP_PLUGIN_DIR . '/test1' );
		$this->rmdir( WP_PLUGIN_DIR . '/test2' );
		$this->rmdir( WP_PLUGIN_DIR . '/test3' );
		$this->delete_folders( WP_PLUGIN_DIR . '/test1' );
		$this->delete_folders( WP_PLUGIN_DIR . '/test2' );
		$this->delete_folders( WP_PLUGIN_DIR . '/test3' );
	}

	/**
	 * @testdox 'get_woocommerce_aware_plugins' properly gets the names of all the existing WooCommerce aware plugins.
	 */
	public function test_get_all_woo_aware_plugins() {
		$result = $this->sut->get_woocommerce_aware_plugins( false );

		$expected = array(
			'woo_aware_1',
			'woo_aware_2',
			'woo_aware_3',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'get_woocommerce_aware_plugins' properly gets the names of the active WooCommerce aware plugins.
	 */
	public function test_get_active_woo_aware_plugins() {
		$result = $this->sut->get_woocommerce_aware_plugins( true );

		$expected = array(
			'woo_aware_1',
			'woo_aware_2',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * 'test_get_plugin_name' returns the printable plugin name when available.
	 */
	public function test_get_plugin_name_with_name() {
		$result = $this->sut->get_plugin_name( 'woo_aware_1' );
		$this->assertEquals( 'The WooCommerce aware plugin #1', $result );
	}

	/**
	 * 'test_get_plugin_name' returns back the plugin id when printable plugin name is not available.
	 */
	public function test_get_plugin_name_with_no_name() {
		$result = $this->sut->get_plugin_name( 'woo_aware_2' );
		$this->assertEquals( 'woo_aware_2', $result );
	}

	/**
	 * @testDox 'is_woocommerce_aware_plugin' works as expected when a plugin id (path/file.php) is passed.
	 *
	 * @testWith ["woo_aware_1", true]
	 *           ["not_woo_aware_2", false]
	 *           ["NOT_EXISTS", false]
	 *
	 * @param string $plugin_file The plugin file name to test.
	 * @param bool   $expected_result The expected result from the method.
	 */
	public function test_is_woocommerce_aware_plugin_by_plugin_file( string $plugin_file, bool $expected_result ) {
		$result = $this->sut->is_woocommerce_aware_plugin( $plugin_file );
		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Data provider for test_is_woocommerce_aware_plugin_by_plugin_data.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_is_woocommerce_aware_plugin_by_plugin_data() {
		return array(
			array( array( 'WC tested up to' => '1.0' ), true ),
			array( array( 'WC tested up to' => '' ), false ),
			array( array(), false ),
		);
	}

	/**
	 * @testDox 'is_woocommerce_aware_plugin' works as expected when a an array of plugin data is passed.
	 *
	 * @dataProvider data_provider_for_test_is_woocommerce_aware_plugin_by_plugin_data
	 *
	 * @param array $plugin_data The plugin data to test.
	 * @param bool  $expected_result The expected result from the method.
	 */
	public function test_get_is_woocommerce_aware_plugin_by_plugin_data( array $plugin_data, bool $expected_result ) {
		$result = $this->sut->is_woocommerce_aware_plugin( $plugin_data );
		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @testDox 'get_wp_plugin_id' works with output from __FILE__ and manual 'my-plugin/my-plugin.php' input.
	 */
	public function test_get_wp_plugin_id() {
		$this->reset_legacy_proxy_mocks();
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_plugins' => function () {
					return array(
						'woocommerce/woocommerce.php' => array( 'WC tested up to' => '1.0' ),
						'jetpack/jetpack.php'         => array( 'foo' => 'bar' ),
						'classic-editor/classic-editor.php' => array( 'foo' => 'bar' ),
					);
				},
			)
		);

		// Unix style.
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( 'woocommerce/woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( '6.9.2/woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( '/srv/htdocs/woocommerce/latest/woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( '../../../../wordpress/plugins/woocommerce/latest/woocommerce.php' ) );

		// Windows style.
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( 'woocommerce\\woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( '6.9.2\\woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( 'D:\\WordPress\\plugins\\woocommerce\\6.9.2\\woocommerce.php' ) );
		$this->assertEquals( 'woocommerce/woocommerce.php', $this->sut->get_wp_plugin_id( '..\\..\\..\\..\\WordPress\\plugins\\woocommerce\\6.9.2\\woocommerce.php' ) );

		// This shouldn't throw an exception.
		$this->assertFalse( $this->sut->get_wp_plugin_id( 'woocommerce-bookings/woocommerce-bookings.php' ) );
	}

	/**
	 * Forces a fake list of plugins to be used by the tests.
	 */
	private function mock_plugin_functions() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_plugins'      => function () {
					return array(
						'woo_aware_1'     => array( 'WC tested up to' => '1.0' ),
						'woo_aware_2'     => array( 'WC tested up to' => '2.0' ),
						'woo_aware_3'     => array( 'WC tested up to' => '2.0' ),
						'not_woo_aware_1' => array( 'WC tested up to' => '' ),
						'not_woo_aware_2' => array( 'foo' => 'bar' ),
					);
				},
				'is_plugin_active' => function ( $plugin_name ) {
					return 'woo_aware_3' !== $plugin_name;
				},
				'get_plugin_data'  => function ( $plugin_name ) {
					return StringUtil::ends_with( $plugin_name, 'woo_aware_1' ) ?
						array(
							'WC tested up to' => '1.0',
							'Name'            => 'The WooCommerce aware plugin #1',
						) :
						array( 'WC tested up to' => '1.0' );
				},
			)
		);
	}

	/**
	 * @testdox Test the `get_items_considered_incompatible` method.
	 */
	public function test_get_items_considered_incompatible() {
		$this->reset_container_resolutions();

		add_action(
			'woocommerce_register_feature_definitions',
			function ( $features_controller ) {
				$features = array(
					'test_feature_1' => array(
						'name' => 'Test feature 1',
						'plugins_are_incompatible_by_default' => true,
					),
					'test_feature_2' => array(
						'name' => 'Test feature 2',
						'plugins_are_incompatible_by_default' => false,
					),
					'test_feature_3' => array(
						'name' => 'Test feature 2',
					),
				);

				foreach ( $features as $slug => $definition ) {
					$features_controller->add_feature_definition( $slug, $definition['name'], $definition );
				}
			},
			20
		);

		$plugin_compatibility_info = array(
			'compatible'   => array(
				'compatible_1.php',
				'compatible_2.php',
			),
			'incompatible' => array(
				'incompatible_1.php',
				'incompatible_2.php',
			),
			'uncertain'    => array(
				'uncertain_1.php',
				'uncertain_2.php',
			),
		);

		$expected = array(
			'incompatible_1.php',
			'incompatible_2.php',
			'uncertain_1.php',
			'uncertain_2.php',
		);

		$actual = $this->sut->get_items_considered_incompatible( 'test_feature_1', $plugin_compatibility_info );

		sort( $actual );
		sort( $expected );
		$this->assertEquals( $expected, $actual );

		$expected = array(
			'incompatible_1.php',
			'incompatible_2.php',
		);

		$actual = $this->sut->get_items_considered_incompatible( 'test_feature_2', $plugin_compatibility_info );
		sort( $actual );
		$this->assertEquals( $expected, $actual );

		$actual = $this->sut->get_items_considered_incompatible( 'test_feature_3', $plugin_compatibility_info );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}
}

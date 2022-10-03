<?php

namespace Automattic\WooCommerce\Tests\Utilities;

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
		$this->mock_plugin_functions();
		$this->sut = $this->get_instance_of( PluginUtil::class );
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
	 * Forces a fake list of plugins to be used by the tests.
	 */
	private function mock_plugin_functions() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_plugins'      => function() {
					return array(
						'woo_aware_1'     => array( 'WC tested up to' => '1.0' ),
						'woo_aware_2'     => array( 'WC tested up to' => '2.0' ),
						'woo_aware_3'     => array( 'WC tested up to' => '2.0' ),
						'not_woo_aware_1' => array( 'WC tested up to' => '' ),
						'not_woo_aware_2' => array( 'foo' => 'bar' ),
					);
				},
				'is_plugin_active' => function( $plugin_name ) {
					return 'woo_aware_3' !== $plugin_name;
				},
				'get_plugin_data'  => function( $plugin_name ) {
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
}

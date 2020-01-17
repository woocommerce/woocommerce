<?php
/**
 * Package loader.
 *
 * @package WooCommerce\Tests
 */

/**
 * WC_Tests_Packages class.
 */
class WC_Tests_Packages extends WC_Unit_Test_Case {

	/**
	 * Setup test class.
	 *
	 * @return void
	 */
	public function setUp() {
		if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			$this->markTestSkipped( 'Packages are disabled unless running PHP 5.6+' );
		}
	}

	/**
	 * Test packages exist - this requires composer install to have ran.
	 */
	public function test_packages_exist() {
		$this->assertTrue( \Automattic\WooCommerce\Packages::package_exists( 'woocommerce-blocks' ) );
		$this->assertTrue( \Automattic\WooCommerce\Packages::package_exists( 'woocommerce-rest-api' ) );
	}

	/**
	 * Test packages autoload correctly.
	 */
	public function test_autoload_packages() {
		$this->assertTrue( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) );
		$this->assertTrue( class_exists( '\Automattic\WooCommerce\RestApi\Package' ) );
	}

	/**
	 * Check API package returns values.
	 *
	 * @return void
	 */
	public function test_api_package() {
		$this->assertNotNull( wc()->api->get_rest_api_package_version() );
		$this->assertNotNull( wc()->api->get_rest_api_package_path() );
	}

	/**
	 * Test that the REST API package is working by hitting some endpoints.
	 */
	public function test_api_endpoints_exist() {
		$response = wc()->api->get_endpoint_data( '/wc/v3' );
		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals( 'wc/v3', $response['namespace'] ); // phpcs:ignore
	}
}

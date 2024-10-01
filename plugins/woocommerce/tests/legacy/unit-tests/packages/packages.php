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
	 * Test packages exist - this requires composer install to have ran.
	 */
	public function test_packages_exist() {
		$this->assertTrue( \Automattic\WooCommerce\Packages::package_exists( 'woocommerce-admin' ) );
	}

	/**
	 * Test packages autoload correctly.
	 */
	public function test_autoload_packages() {
		$this->assertTrue( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) );
		$this->assertTrue( class_exists( '\Automattic\WooCommerce\RestApi\Package' ) );
	}
}

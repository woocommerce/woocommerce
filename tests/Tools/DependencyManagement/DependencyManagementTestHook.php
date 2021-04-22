<?php
/**
 * DependencyManagementTestHook class file.
 *
 * @package Automattic\WooCommerce\Testing\Tools\DependencyManagement
 */

namespace Automattic\WooCommerce\Testing\Tools\DependencyManagement;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use PHPUnit\Runner\BeforeTestHook;

/**
 * Hook to perform dependency management related setup in PHPUnit. To use, add this to phpunit.xml:
 *
 *    <extensions>
 *      <extension class="DependencyManagementTestHook" />
 *    </extensions>
 */
final class DependencyManagementTestHook implements BeforeTestHook {

	/**
	 * Runs before each test.
	 *
	 * @param string $test "TestClass::TestMethod".
	 */
	public function executeBeforeTest( string $test ): void {
		// Reset the instance of MockableLegacyProxy that was registered during bootstrap,
		// in order to start the test in a clean state (without anything mocked).
		wc_get_container()->get( LegacyProxy::class )->reset();
	}
}


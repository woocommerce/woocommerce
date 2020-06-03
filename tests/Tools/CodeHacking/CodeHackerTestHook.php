<?php
/**
 * CodeHackerTestHook class file.
 *
 * @package WooCommerce/Testing
 */

namespace Automattic\WooCommerce\Testing\Tools\CodeHacking;

use PHPUnit\Runner\BeforeTestHook;

/**
 * Helper to use the CodeHacker class in PHPUnit. To use, add this to phpunit.xml:
 *
 *    <extensions>
 *      <extension class="CodeHackerTestHook" />
 *    </extensions>
 */
final class CodeHackerTestHook implements BeforeTestHook {

	/**
	 * Runs before each test.
	 *
	 * @param string $test "TestClass::TestMethod".
	 *
	 * @throws \ReflectionException Thrown by execute_before_methods.
	 */
	public function executeBeforeTest( string $test ): void {
		CodeHacker::reset_hacks();
	}
}


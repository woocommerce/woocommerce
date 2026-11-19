<?php
/**
 * DataStoreHasCallableExtensionTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PHPStan\Testing\TypeInferenceTestCase;

/**
 * Tests for DataStoreHasCallableExtension.
 *
 * Runs in its own process. Sharing the process with the StagedWriteRule tests makes them fail when this test runs
 * first.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DataStoreHasCallableExtensionTest extends TypeInferenceTestCase {

	/**
	 * @testdox A true has_callable() check with a known method name adds that method to the data store type.
	 */
	public function test_has_callable_narrows_the_data_store_type(): void {
		foreach ( self::gatherAssertTypes( __DIR__ . '/data/has-callable.php' ) as $assertion ) {
			$this->assertFileAsserts( ...$assertion );
		}
	}

	/**
	 * Config that registers the extension.
	 *
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array {
		return array( __DIR__ . '/extensions.neon' );
	}
}

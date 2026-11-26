<?php
/**
 * StagedFiltersReturnTypeExtensionTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PHPStan\Testing\TypeInferenceTestCase;

/**
 * Tests for StagedFiltersReturnTypeExtension.
 */
final class StagedFiltersReturnTypeExtensionTest extends TypeInferenceTestCase {

	/**
	 * Type assertions of the fixture.
	 *
	 * @return iterable<mixed>
	 */
	public static function provide_asserts(): iterable {
		yield from self::gatherAssertTypes( __DIR__ . '/data/staged-filter-types.php' );
	}

	/**
	 * @testdox apply_staged_filters() returns the type of the first @param tag of its docblock, or mixed without one.
	 * @dataProvider provide_asserts
	 *
	 * @param string $assert_type Assertion type.
	 * @param string $file        Fixture file.
	 * @param mixed  ...$args     Assertion arguments.
	 */
	public function test_apply_staged_filters_return_type( string $assert_type, string $file, ...$args ): void {
		$this->assertFileAsserts( $assert_type, $file, ...$args );
	}

	/**
	 * Config files with the extension and the docblock reader of phpstan-wordpress.
	 *
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array {
		return array( __DIR__ . '/data/staged-hooks.neon' );
	}
}

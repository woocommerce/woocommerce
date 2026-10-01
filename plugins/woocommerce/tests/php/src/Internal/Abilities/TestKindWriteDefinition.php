<?php
/**
 * Test kind write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * Renames a product, declaring an extension-defined subject kind.
 */
class TestKindWriteDefinition extends TestInMemoryWriteDefinition {

	public const ABILITY_ID = 'test-extension/rename-kind';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Kind of object the write changes.
	 */
	public static function subject_type(): string {
		return 'test_kind';
	}
}

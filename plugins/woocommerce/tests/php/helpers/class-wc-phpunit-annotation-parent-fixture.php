<?php
/**
 * Parent source metadata used by annotation cache tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Fixture with metadata inherited by a child class.
 *
 * @requires PHP >=7.4
 */
abstract class WC_PHPUnit_Annotation_Parent_Fixture extends PHPUnit\Framework\TestCase {
	/** @var int Number of provider calls. */
	public static $provider_calls = 0;

	/** @var int Number of hook calls. */
	public static $hook_calls = 0;

	/**
	 * Fixture method for annotation parsing.
	 *
	 * @dataProvider provide_context
	 * @requires PHP >=7.4
	 * @coversNothing
	 */
	public function exercise(): void {
	}

	/**
	 * Provide the called class as context.
	 *
	 * @return array Provider data.
	 */
	public static function provide_context(): array {
		++self::$provider_calls;
		return array( array( static::class ) );
	}

	/**
	 * Record a before hook call.
	 *
	 * @before
	 */
	protected function before_fixture(): void {
		++self::$hook_calls;
	}

	/**
	 * Record an after-class hook call.
	 *
	 * @afterClass
	 */
	public static function after_fixture_class(): void {
		++self::$hook_calls;
	}
}

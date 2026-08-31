<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Infrastructure;

/**
 * Class resolver for the dummy code-API used by the GraphQL infrastructure
 * tests.
 *
 * Mirrors the public signature ApiBuilder requires: a public static
 * `resolve_class(string): object` method. Tests can swap the underlying instances via
 * {@see self::set_instance()} so a single resolver dispatch can be observed
 * with a known command instance.
 */
final class ClassResolver {
	/**
	 * @var array<class-string, object>
	 */
	private static array $instances = array();

	public static function set_instance( string $class_name, object $instance ): void {
		self::$instances[ $class_name ] = $instance;
	}

	public static function reset(): void {
		self::$instances = array();
	}

	public static function resolve_class( string $class_name ): object {
		if ( isset( self::$instances[ $class_name ] ) ) {
			return self::$instances[ $class_name ];
		}
		return new $class_name();
	}
}

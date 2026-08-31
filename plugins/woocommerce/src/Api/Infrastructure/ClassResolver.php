<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Infrastructure;

/**
 * Class resolver for code-API command classes and other infrastructure classes.
 *
 * Plugins that implement their own API and want their command and infrastructure
 * classes instantiated through a container of their own can ship their own
 * ClassResolver class at `<plugin-api-namespace>\Infrastructure\ClassResolver`
 * with the same public signature: ApiBuilder detects it during generation
 * and routes the generated resolvers through it. When no such class is present,
 * resolvers fall back to `new $class_name()`.
 */
final class ClassResolver {
	/**
	 * Resolve a class to an instance.
	 *
	 * @param string $class_name Fully qualified name of the class to resolve.
	 * @return object An instance of $class_name.
	 */
	public static function resolve_class( string $class_name ): object {
		return wc_get_container()->get( $class_name );
	}
}

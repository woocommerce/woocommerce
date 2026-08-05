<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Overrides the GraphQL type for a property with a custom scalar.
 *
 * By default the builder maps PHP types to built-in GraphQL scalars (String,
 * Int, Float, Boolean). Use this attribute when a property should use a custom
 * scalar type instead, such as `DateTime`.
 *
 * Example: `#[ScalarType(DateTime::class)]` on a `?string $date_created`
 * property produces the GraphQL type `DateTime` instead of `String`.
 */
#[Attribute]
final class ScalarType {
	/**
	 * Constructor.
	 *
	 * @param string $type The fully-qualified class name of the custom scalar
	 *                     (e.g. `DateTime::class`).
	 */
	public function __construct(
		public readonly string $type,
	) {
	}
}

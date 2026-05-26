<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Declares the element type for an array-typed property or return value.
 *
 * PHP arrays are untyped, so the builder cannot infer the element type via
 * reflection. Apply this attribute to tell the builder what GraphQL list type
 * to generate (e.g. `[Int!]`, `[String!]`).
 *
 * Example: `#[ArrayOf('int')]` on a `array $product_ids` property produces
 * the GraphQL type `[Int!]!`.
 */
#[Attribute]
final class ArrayOf {
	/**
	 * Constructor.
	 *
	 * @param string $type A scalar name ('int', 'string', 'float', 'bool') or
	 *                     a fully-qualified class name for output/enum types.
	 */
	public function __construct(
		public readonly string $type,
	) {
	}
}

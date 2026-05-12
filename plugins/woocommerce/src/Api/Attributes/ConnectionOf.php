<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a query's return type as a Relay-style connection of the given node type.
 *
 * Applied to the `execute()` method of a query class that returns a `Connection`.
 * The builder uses this to generate the corresponding connection and edge GraphQL
 * types (e.g. `CouponConnection`, `CouponEdge`) and to wire the correct return
 * type in the schema.
 */
#[Attribute]
final class ConnectionOf {
	/**
	 * Constructor.
	 *
	 * @param string $type The fully-qualified class name of the node type
	 *                     (e.g. `Coupon::class`).
	 */
	public function __construct(
		public readonly string $type,
	) {
	}
}

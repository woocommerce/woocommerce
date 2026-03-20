<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Overrides the GraphQL name derived from the PHP class or property name.
 *
 * By default the builder converts PHP names to GraphQL conventions automatically.
 * Use this attribute when you need a specific GraphQL name that differs from
 * the default conversion (e.g. a legacy name for backwards compatibility).
 */
#[Attribute]
final class Name {
	/**
	 * Constructor.
	 *
	 * @param string $name The exact name to use in the GraphQL schema.
	 */
	public function __construct(
		public readonly string $name,
	) {
	}
}

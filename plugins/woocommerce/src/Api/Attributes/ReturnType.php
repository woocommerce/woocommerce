<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Declares the GraphQL return type of execute() when it returns an interface.
 *
 * Since PHP cannot type-hint a trait, the execute() method uses `object` as its
 * return type and this attribute tells the builder which interface type to use
 * in the schema. The GraphQL engine then uses the interface's `resolveType`
 * callback to determine the concrete type at runtime.
 */
#[Attribute( Attribute::TARGET_METHOD )]
final class ReturnType {
	/**
	 * Constructor.
	 *
	 * @param string $type The fully-qualified class name of the interface trait
	 *                     (e.g. `ApiObject::class`).
	 */
	public function __construct(
		public readonly string $type,
	) {
	}
}

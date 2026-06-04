<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Adds a description to a query/mutation argument without overriding its type.
 *
 * Sets the description for a query/mutation argument. Can be used both for
 * arguments inferred from the `execute()` method signature and for arguments
 * declared via #[Parameter]. However, a parameter must not have a description
 * in both #[Parameter] and #[ParameterDescription] — that is a build error.
 * This attribute is repeatable: apply it once per argument that needs a
 * description.
 */
#[Attribute( Attribute::TARGET_ALL | Attribute::IS_REPEATABLE )]
final class ParameterDescription {
	/**
	 * Constructor.
	 *
	 * @param string $name        The argument name (must match the `execute()`
	 *                            parameter name).
	 * @param string $description Human-readable description for the schema.
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $description,
	) {
	}
}

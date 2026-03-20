<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Declares an explicit GraphQL argument for a query or mutation.
 *
 * Use this when the argument cannot be inferred from the `execute()` method
 * signature — for example, when a parameter needs a specific GraphQL type,
 * nullability, or default that differs from what reflection would produce.
 * This attribute is repeatable: apply it once per argument.
 */
#[Attribute( Attribute::TARGET_ALL | Attribute::IS_REPEATABLE )]
final class Parameter {
	/**
	 * Whether a default value was provided.
	 *
	 * @var bool
	 */
	public readonly bool $has_default;

	/**
	 * Constructor.
	 *
	 * @param string $name        The GraphQL argument name (not needed when unrolling).
	 * @param string $type        The PHP type name ('int', 'string', 'float', 'bool')
	 *                            or a fully-qualified class name for complex types.
	 * @param bool   $nullable    Whether the argument accepts null.
	 * @param bool   $array       Whether the argument is a list (e.g. `[Int!]`).
	 * @param mixed  $default     The default value if the argument is omitted.
	 * @param string $description Human-readable description for the schema.
	 * @param bool   $has_default Set to true to explicitly indicate a default is
	 *                            provided (needed when the default value is null).
	 * @param bool   $unroll      When true, the class given in $type is expanded into
	 *                            individual GraphQL arguments (one per public property).
	 */
	public function __construct(
		public readonly string $name = '',
		public readonly string $type = '',
		public readonly bool $nullable = false,
		public readonly bool $array = false,
		public readonly mixed $default = null,
		public readonly string $description = '',
		bool $has_default = false,
		public readonly bool $unroll = false,
	) {
		// We need a separate flag because null could be a valid default value.
		// Callers pass has_default: true when they supply a default, or we infer
		// it from the default value being non-null.
		$this->has_default = $has_default || null !== $default;
	}
}

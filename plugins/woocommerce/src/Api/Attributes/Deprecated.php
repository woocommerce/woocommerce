<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a field or enum value as deprecated in the GraphQL schema.
 *
 * Deprecated elements remain functional but are flagged with a deprecation
 * reason in introspection, signaling to API consumers that they should
 * migrate to an alternative.
 */
#[Attribute]
final class Deprecated {
	/**
	 * Constructor.
	 *
	 * @param string $reason A human-readable explanation of why the element is
	 *                       deprecated and what to use instead.
	 */
	public function __construct(
		public readonly string $reason,
	) {
	}
}

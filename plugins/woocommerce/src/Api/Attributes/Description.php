<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Provides a human-readable description for the annotated element.
 *
 * Can be applied to classes (types, queries, mutations, enums), properties, or
 * parameters. The text is exposed as the "description" field in the generated
 * GraphQL schema and is visible in tools like GraphiQL.
 */
#[Attribute]
final class Description {
	/**
	 * Constructor.
	 *
	 * @param string $description The text to expose as the GraphQL description.
	 */
	public function __construct(
		public readonly string $description,
	) {
	}
}

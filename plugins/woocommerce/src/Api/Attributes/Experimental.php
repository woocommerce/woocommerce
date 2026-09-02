<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a code-API element as experimental: present in the schema, but not
 * stable enough to be relied on in production.
 *
 * Discoverable through the `_apiMetadata` GraphQL field as an entry with
 * `name = "experimental"` and `value = true`. The marking is informational:
 * it does not gate access in any way.
 *
 * When a class, property or enum case has this attribute, the generated
 * GraphQL `description` is prefixed with `[Experimental] `, and
 * when the element has no `#[Description]` at all, a default body
 * (`[Experimental] Not to be used in production environments.`) is emitted
 * so the marker still reaches stock introspection.
 *
 * `#[Experimental]` on a class marks only that class: its fields and enum
 * cases are not implicitly marked too. A tool that wants to treat the
 * contents of an experimental type as experimental by association must
 * apply that rule itself when it reads the metadata.
 *
 * The attribute targets classes, properties, and enum cases; see
 * {@see Metadata} for the reasoning behind excluding methods.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS_CONSTANT )]
class Experimental extends Metadata {
	/**
	 * Construct an `experimental` metadata entry with value `true`.
	 */
	public function __construct() {
		parent::__construct( 'experimental', true );
	}

	/**
	 * Prepend `[Experimental] ` to the description, supplying a default body
	 * when the element has no `#[Description]` of its own. See
	 * {@see Metadata::transform_description()} for the contract.
	 *
	 * @param string $description Incoming description (empty when no `#[Description]`).
	 */
	public function transform_description( string $description ): string {
		if ( '' === $description ) {
			$description = 'Not to be used in production environments.';
		}
		return '[Experimental] ' . $description;
	}
}

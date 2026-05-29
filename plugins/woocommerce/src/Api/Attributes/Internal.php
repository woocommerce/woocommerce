<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a code-API element as for WooCommerce internal use.
 *
 * Discoverable through the `_apiMetadata` GraphQL field as an entry with
 * `name = "internal"` and `value = true`. The marking is informational —
 * authorization remains the job of {@see PublicAccess}, {@see RequiredCapability},
 * and any plugin-supplied authorization attributes.
 *
 * When a class, property or enum case has this attribute, the generated
 * GraphQL `description` is prefixed with `[Internal] `, and
 * when the element has no `#[Description]` at all, a default body
 * (`[Internal] For WooCommerce core internal usage only.`) is emitted so the
 * marker still reaches stock introspection.
 *
 * `#[Internal]` on a class marks only that class — its fields and enum cases
 * are not implicitly marked too. A tool that wants to treat the contents of
 * an internal type as internal by association must apply that rule itself
 * when it reads the metadata.
 *
 * The attribute targets classes, properties, and enum cases; see
 * {@see Metadata} for the reasoning behind excluding methods.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS_CONSTANT )]
class Internal extends Metadata {
	/**
	 * Construct an `internal` metadata entry with value `true`.
	 */
	public function __construct() {
		parent::__construct( 'internal', true );
	}

	/**
	 * Prepend `[Internal] ` to the description, supplying a default body when
	 * the element has no `#[Description]` of its own. See
	 * {@see Metadata::transform_description()} for the contract.
	 *
	 * @param string $description Incoming description (empty when no `#[Description]`).
	 */
	public function transform_description( string $description ): string {
		if ( '' === $description ) {
			$description = 'For WooCommerce core internal usage only.';
		}
		return '[Internal] ' . $description;
	}
}

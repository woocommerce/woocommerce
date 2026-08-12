<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Attaches a name/value metadata entry to a code-API element.
 * (class, class property, method parameter, method parameter, or enum case).
 *
 * Metadata entries are harvested by ApiBuilder and emitted into the generated
 * schema, where they can be queried at runtime through the top-level
 * `_apiMetadata` GraphQL field. The mechanism is intentionally open: subclass
 * this attribute to ship a category of metadata (e.g. {@see Internal} for
 * marking elements as for WooCommerce internal use). Tooling discovers metadata
 * by name; the value is scalar-only so it can flow through GraphQL without
 * additional encoding.
 *
 * Two metadata entries with the same name on the same element produce a
 * build-time error, see ApiBuilder for the duplicate-name detection. Multiple
 * distinct names on one element are allowed.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE )]
class Metadata {
	/**
	 * Constructor.
	 *
	 * @param string                     $name  Identifier for this entry. Must be unique per element across all Metadata subclasses applied to it.
	 * @param bool|int|float|string|null $value Scalar payload exposed to clients via `_apiMetadata`.
	 */
	public function __construct(
		private string $name,
		private bool|int|float|string|null $value,
	) {
	}

	/**
	 * The entry's name (e.g. `internal`, `beta`, `owner`).
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * The entry's scalar value.
	 */
	public function get_value(): bool|int|float|string|null {
		return $this->value;
	}

	/**
	 * Whether the element carrying this attribute should appear in the
	 * `_apiMetadata` discovery query.
	 *
	 * Returning `false` removes the element's row entirely from
	 * `_apiMetadata` — neither this metadata entry nor any other
	 * descriptor on the same target surfaces. The runtime gates and any
	 * description transforms are unaffected. Useful for plugins that
	 * attach internal routing or feature hints they prefer not to
	 * broadcast through the discovery channel.
	 *
	 * Despite the colloquial naming around it, this has nothing to do
	 * with native GraphQL introspection (`__schema` / `__type`); those
	 * queries continue to expose the schema's shape as usual.
	 *
	 * Because this is an instance method, subclasses can decide
	 * conditionally based on their own constructor arguments.
	 */
	public function shows_in_metadata_query(): bool {
		return true;
	}

	/**
	 * Transform the GraphQL `description` of the element this attribute is
	 * applied to.
	 *
	 * The base implementation is a no-op; the general `#[Metadata]` mechanism
	 * does not modify descriptions. Subclasses opt into the description-mirror
	 * convention by overriding this method — typically to prefix the input
	 * with a marker (`[Internal] `, `[Experimental] `, …) and supply a default
	 * body when the element has no `#[Description]` of its own.
	 *
	 * Conventions for overrides:
	 *  - An empty `$description` means the element has no `#[Description]`.
	 *    If the subclass wants the marker to still reach stock introspection,
	 *    it should supply a sensible default text and prefix it as usual.
	 *  - A non-empty `$description` may be either the developer's own text or
	 *    the output of a previous attribute's transform; the subclass should
	 *    not try to distinguish. Wrap-only (prefix the input, don't replace).
	 *
	 * When more than one transforming attribute is applied to the same
	 * element, ApiBuilder calls `transform_description()` once per attribute
	 * in PHP reflection (source) order, threading each return value into the
	 * next call. Because each subclass prefixes the input, the last attribute
	 * in source ends up as the outermost prefix in the final string. Order
	 * the attributes accordingly when the reading order matters.
	 *
	 * @internal Called by ApiBuilder; not part of any caller-visible contract.
	 *
	 * @param string $description The current description text (`''` when the element has no `#[Description]`, or the previous transform's output when chained).
	 */
	public function transform_description( string $description ): string {
		return $description;
	}
}

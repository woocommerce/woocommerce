<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marker attribute that opts a code-API target out of the `_apiMetadata`
 * discovery query without affecting any other behaviour. Apply on a
 * class (output / input type, query, mutation) or on a property to hide
 * that target's row — and all its metadata / authorization descriptors —
 * from the `_apiMetadata` endpoint.
 *
 * This is unrelated to native GraphQL introspection (`__schema` /
 * `__type`); those queries continue to expose the schema's shape as
 * usual. The marker only affects the custom `_apiMetadata` channel.
 *
 * The runtime authorization gates emitted into the generated resolvers
 * are unaffected: an authorization attribute placed alongside this one
 * still runs its `authorize()` method; this marker just removes the
 * declarative shape from the discovery channel.
 *
 * A target's `_apiMetadata` visibility is the AND of every attribute's
 * `shows_in_metadata_query()` on the target — so combining
 * `#[HiddenFromMetadataQuery]` with any other attribute that returns
 * `true` (or none at all) still hides the target.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS_CONSTANT )]
final class HiddenFromMetadataQuery {
	/**
	 * Always returns `false`. ApiBuilder calls this during the per-target
	 * `_apiMetadata` visibility check; the target is omitted from the
	 * discovery output as a result.
	 */
	public function shows_in_metadata_query(): bool {
		return false;
	}
}

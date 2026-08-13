<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Attributes;

use Attribute;
use Automattic\WooCommerce\Api\Infrastructure\Principal;

/**
 * Fixture authorization attribute that grants iff a `runtime_flag` metadata
 * entry (value `true`) is present in the requested `$_metadata` slice
 * (`query`, `type`, or `field`).
 *
 * Used to prove that the runtime `$_metadata` slices stay populated even when
 * the target carrying the metadata opts out of `_apiMetadata` via
 * `#[HiddenFromMetadataQuery]` — that opt-out is discovery-only and must not
 * starve the runtime gate.
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
final class GrantsIfMetadataFlag {
	/**
	 * @param string $slice Which `$_metadata` slice to read: `query`, `type`, or `field`.
	 */
	public function __construct( public readonly string $slice ) {
	}

	/**
	 * @param Principal $principal The resolved principal (unused; the decision is metadata-driven).
	 * @param array     $_metadata The harvested metadata slices.
	 */
	public function authorize( Principal $principal, array $_metadata ): bool {
		unset( $principal );
		return true === ( $_metadata[ $this->slice ]['runtime_flag'] ?? null );
	}
}

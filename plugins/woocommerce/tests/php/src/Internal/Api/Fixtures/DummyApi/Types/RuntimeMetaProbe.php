<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types;

use Automattic\WooCommerce\Api\Attributes\HiddenFromMetadataQuery;
use Automattic\WooCommerce\Api\Attributes\Metadata;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Attributes\GrantsIfMetadataFlag;

/**
 * Output type for the runtime-metadata-visibility regression. The type, one of
 * its fields, and the query that returns it ({@see \Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\HiddenFlaggedQuery})
 * all opt out of `_apiMetadata` via `#[HiddenFromMetadataQuery]` while carrying
 * a `runtime_flag` metadata entry. Each gated field reads a different
 * `$_metadata` slice and must still grant, proving the discovery opt-out does
 * not blank the runtime metadata.
 */
#[Metadata( 'runtime_flag', true )]
#[HiddenFromMetadataQuery]
class RuntimeMetaProbe {
	/**
	 * Reads the `type` slice: the enclosing (hidden) type carries `runtime_flag`.
	 */
	#[GrantsIfMetadataFlag( 'type' )]
	public ?string $by_type;

	/**
	 * Reads the `field` slice: this field is itself hidden and carries `runtime_flag`.
	 */
	#[Metadata( 'runtime_flag', true )]
	#[HiddenFromMetadataQuery]
	#[GrantsIfMetadataFlag( 'field' )]
	public ?string $by_field;

	/**
	 * Reads the `query` slice: the (hidden) query that resolves this type carries `runtime_flag`.
	 */
	#[GrantsIfMetadataFlag( 'query' )]
	public ?string $by_query;
}

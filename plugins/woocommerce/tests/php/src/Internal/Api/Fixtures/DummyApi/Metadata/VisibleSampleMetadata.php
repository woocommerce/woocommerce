<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Metadata;

use Attribute;
use Automattic\WooCommerce\Api\Attributes\Metadata;

/**
 * Plain fixture metadata subclass with the default
 * `shows_in_metadata_query()` (returns `true`). Provides a known entry
 * name (`visible_sample`) for tests that assert on the metadata query's
 * output without bringing the side effects of stock subclasses like
 * `#[Internal]` (which prefixes descriptions).
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
final class VisibleSampleMetadata extends Metadata {
	public function __construct() {
		parent::__construct( 'visible_sample', 'visible' );
	}
}

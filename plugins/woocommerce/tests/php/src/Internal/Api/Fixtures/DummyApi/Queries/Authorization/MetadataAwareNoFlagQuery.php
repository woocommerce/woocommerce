<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Authorization;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Attributes\RequiresInternalFlag;

/**
 * Gated by {@see RequiresInternalFlag} but carries no `#[Internal]`
 * metadata. The gate should deny: this is the negative path for the
 * metadata slot — `$_metadata['query']` is empty, so the attribute's
 * "is internal?" check returns false.
 */
#[Name( 'metadataAwareNoFlagQuery' )]
#[Description( 'Exercises RequiresInternalFlag without the matching #[Internal] entry.' )]
#[RequiresInternalFlag]
class MetadataAwareNoFlagQuery {
	public function execute(): string {
		return 'ok-no-internal';
	}
}

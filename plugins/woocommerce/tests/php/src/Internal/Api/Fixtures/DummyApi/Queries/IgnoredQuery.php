<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries;

use Automattic\WooCommerce\Api\Attributes\Ignore;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;

/**
 * Carries #[Ignore] so the ApiBuilder skips it entirely. Tests assert that
 * the generated schema does NOT expose any field for this class.
 */
#[Ignore]
#[PublicAccess]
class IgnoredQuery {
	public function execute(): string {
		return 'should never be reachable';
	}
}

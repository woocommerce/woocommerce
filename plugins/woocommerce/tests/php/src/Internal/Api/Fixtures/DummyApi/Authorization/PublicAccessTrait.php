<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Authorization;

use Automattic\WooCommerce\Api\Attributes\PublicAccess;

/**
 * Trait carrying #[PublicAccess]; queries that `use` it inherit public
 * access without having to declare the attribute themselves.
 */
#[PublicAccess]
trait PublicAccessTrait {
}

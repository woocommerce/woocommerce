<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Authorization;

use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * PHP interface that carries a #[RequiredCapability] attribute. Used to
 * verify that ApiBuilder honours capability inheritance via implements clauses
 * (in addition to parent classes and traits).
 *
 * Lives in the non-classified Authorization/ directory so the builder skips
 * it during discovery — it's a helper, not a code-API concept itself.
 */
#[RequiredCapability( 'manage_options' )]
interface RequiresManageOptions {
}

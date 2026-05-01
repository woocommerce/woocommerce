<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * Carries a direct #[RequiredCapability] that should *override* the cap
 * inherited from its parent — only `manage_categories` should be enforced.
 */
#[Name( 'overriddenCap' )]
#[Description( 'Overrides the inherited manage_options with manage_categories' )]
#[RequiredCapability( 'manage_categories' )]
class OverriddenCapQuery extends BaseManageOptionsQuery {
	public function execute(): string {
		return 'overridden cap';
	}
}

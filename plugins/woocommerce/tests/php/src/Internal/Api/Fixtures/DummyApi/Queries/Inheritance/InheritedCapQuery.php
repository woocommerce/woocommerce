<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;

/**
 * Inherits #[RequiredCapability('manage_options')] from its abstract parent
 * with no direct attribute of its own.
 */
#[Name( 'inheritedCap' )]
#[Description( 'Inherits manage_options from its abstract parent' )]
class InheritedCapQuery extends BaseManageOptionsQuery {
	public function execute(): string {
		return 'inherited cap';
	}
}

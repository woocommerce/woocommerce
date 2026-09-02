<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Authorization\RequiresManageOptions;

/**
 * Inherits #[RequiredCapability('manage_options')] from a PHP interface.
 */
#[Name( 'inheritedFromInterface' )]
#[Description( 'Inherits manage_options from a PHP interface' )]
class InheritedFromInterfaceQuery implements RequiresManageOptions {
	public function execute(): string {
		return 'inherited from interface';
	}
}

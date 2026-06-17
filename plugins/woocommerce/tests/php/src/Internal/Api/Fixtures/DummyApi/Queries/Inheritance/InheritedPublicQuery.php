<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Authorization\PublicAccessTrait;

/**
 * Inherits #[PublicAccess] via a trait. No direct authorization attribute.
 */
#[Name( 'inheritedPublic' )]
#[Description( 'Inherits PublicAccess via a trait' )]
class InheritedPublicQuery {
	use PublicAccessTrait;

	public function execute(): string {
		return 'inherited public';
	}
}

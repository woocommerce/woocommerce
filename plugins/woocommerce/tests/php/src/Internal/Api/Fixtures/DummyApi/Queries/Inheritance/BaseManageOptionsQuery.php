<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance;

use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * Abstract parent class carrying #[RequiredCapability('manage_options')].
 *
 * Auto-ignored by the builder because it is abstract, but its attribute is
 * still discoverable via reflection on derived classes — which is the whole
 * point of testing inheritance.
 */
#[RequiredCapability( 'manage_options' )]
abstract class BaseManageOptionsQuery {
	/**
	 * Implemented by each concrete derived query.
	 */
	abstract public function execute(): string;
}

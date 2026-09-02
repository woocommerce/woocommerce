<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Mutations;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;

/**
 * Mutation that returns a scalar (int) — exercises the generator's "wrap a
 * scalar return in a result object" path on the mutation side.
 */
#[Name( 'increment' )]
#[Description( 'Increment a value by an optional amount' )]
#[PublicAccess]
class Increment {
	public function execute(
		#[Description( 'The starting value' )]
		int $value,
		#[Description( 'How much to add' )]
		int $by = 1,
	): int {
		return $value + $by;
	}
}

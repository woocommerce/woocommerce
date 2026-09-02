<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'The result of a generic operation' )]
class OperationResult {
	#[Description( 'Whether the operation succeeded' )]
	public bool $success;

	#[Description( 'A human-readable status message' )]
	public string $message;
}

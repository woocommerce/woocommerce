<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'A simple color palette' )]
enum Color: string {
	#[Description( 'Red' )]
	case Red = 'red';

	#[Description( 'Green' )]
	case Green = 'green';

	case Blue = 'blue';
}

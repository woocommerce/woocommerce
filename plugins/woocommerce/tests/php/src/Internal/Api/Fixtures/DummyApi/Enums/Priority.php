<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums;

use Automattic\WooCommerce\Api\Attributes\Deprecated;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;

/**
 * Exercises class-level #[Name] (renames the GraphQL type) and case-level
 * #[Name] / #[Deprecated] / #[Description].
 */
#[Name( 'TaskPriority' )]
#[Description( 'Priority level for a task' )]
enum Priority: string {
	#[Description( 'Low priority' )]
	case Low = 'low';

	#[Name( 'NORMAL_PRIORITY' )]
	case Normal = 'normal';

	#[Description( 'High priority' )]
	#[Deprecated( 'Use NORMAL_PRIORITY instead.' )]
	case High = 'high';
}

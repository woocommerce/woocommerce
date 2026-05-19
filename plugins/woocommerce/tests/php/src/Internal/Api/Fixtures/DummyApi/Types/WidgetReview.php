<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Interfaces\Identifiable;

/**
 * A review of a widget.
 */
#[Description( 'A review left for a widget' )]
class WidgetReview {
	use Identifiable;

	#[Description( 'The body of the review' )]
	public string $body;

	#[Description( 'A score between 0 and 5' )]
	public int $score;
}

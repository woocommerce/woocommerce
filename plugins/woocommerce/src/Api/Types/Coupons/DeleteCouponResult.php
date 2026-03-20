<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Coupons;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Result of a coupon deletion operation.
 */
#[Description( 'The result of deleting a coupon.' )]
class DeleteCouponResult {
	#[Description( 'The ID of the deleted coupon.' )]
	public int $id;

	#[Description( 'Whether the coupon was permanently deleted.' )]
	public bool $deleted;
}

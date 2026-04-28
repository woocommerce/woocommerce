<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Enums\Coupons;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'The publication status of a coupon.' )]
enum CouponStatus: string {
	#[Description( 'The coupon is published and active.' )]
	case Published = 'publish';

	#[Description( 'The coupon is a draft.' )]
	case Draft = 'draft';

	#[Description( 'The coupon is pending review.' )]
	case Pending = 'pending';

	#[Description( 'The coupon is privately published.' )]
	case Private = 'private';

	#[Description( 'The coupon is scheduled to be published in the future.' )]
	case Future = 'future';

	#[Description( 'The coupon is in the trash.' )]
	case Trash = 'trash';

	#[Description( 'The coupon status is not one of the standard WordPress values (e.g. added by a plugin). Inspect raw_status for the underlying value.' )]
	case Other = 'other';
}

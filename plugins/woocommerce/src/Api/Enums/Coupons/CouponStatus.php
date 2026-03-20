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

	#[Description( 'The coupon is in the trash.' )]
	case Trash = 'trash';
}

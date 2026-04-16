<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Enums\Coupons;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'The type of discount for a coupon.' )]
enum DiscountType: string {
	#[Description( 'A percentage discount.' )]
	case Percent = 'percent';

	#[Description( 'A fixed amount discount applied to the cart.' )]
	case FixedCart = 'fixed_cart';

	#[Description( 'A fixed amount discount applied to each eligible product.' )]
	case FixedProduct = 'fixed_product';

	#[Description( 'The discount type is not one of the standard WooCommerce values (e.g. added by a plugin). Inspect raw_discount_type for the underlying value.' )]
	case Other = 'other';
}

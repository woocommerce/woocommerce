<?php

namespace Automattic\WooCommerce\Api\OutputObjects;

use Automattic\WooCommerce\Api\ObjectWithId;

#[Description('Information about a coupon applied to an order.')]
class AppliedCoupon {

	#[Description('Coupon amount.')]
	#[Argument('decimals', 'int', 'Amount of decimals to round the amount to. Default is 2.', 2)]
	public float $amount;

	#[DataType(CouponType::class)]
	#[Description('Coupon type.')]
	public int $type;
}

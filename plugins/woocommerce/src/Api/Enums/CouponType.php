<?php

namespace Automattic\WooCommerce\Api\Enums;

#[Description('The coupon type.')]
class CouponType {

	#[Description('Fixed amount discounted from the total cart amount.')]
	public const FIXED_CART = 1;

	#[Description('Percent of the total cart amount discounted.')]
	public const PERCENT = 2;
}

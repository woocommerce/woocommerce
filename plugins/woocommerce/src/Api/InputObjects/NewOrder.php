<?php

use Automattic\WooCommerce\Api\OutputObjects\AppliedCoupon;
use Automattic\WooCommerce\Api\OutputObjects\OrderLine;

#[Description('Input object for the CreateOrder mutation.')]
class NewOrder {
	#[Description('Total amount of the order.')]
	public float $total_amount;

	#[Description('Coupons applied to the order.')]
	#[ArrayOf(AppliedCoupon::class)]
	#[NoAutoPaginationArguments]
	public array $applied_coupons;

	#[Description('Line items of the order.')]
	#[ArrayOf(OrderLine::class)]
	public array $lines;

	#[Description('IP address of the customer.')]
	public ?string $customer_ip_address;
}

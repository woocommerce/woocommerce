<?php

namespace Automattic\WooCommerce\Api\OutputObjects;

use Automattic\WooCommerce\Api\ObjectWithId;

#[Description('Information about an existing order.')]
class Order extends ObjectWithId {

	#[Description('Total amount of the order.')]
	#[Argument('decimals', 'int', 'Amount of decimals to round the amount to. Default is 2.', 2)]
	public float $total_amount;

	#[Description('The creation date of the order.')]
	#[DataType('datetime')]
	public string $date_created;

	#[Description('Coupons applied to the order.')]
	#[ArrayOf(AppliedCoupon::class)]
	#[NoAutoPaginationArguments]
	public array $applied_coupons;

	#[Description('Line items of the order.')]
	#[ConnectionOf(OrderLine::class)]
	public Connection $lines;

	#[Description('IP address of the customer.')]
	public ?string $customer_ip_address;
}

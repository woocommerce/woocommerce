<?php

namespace Automattic\WooCommerce\Api\OutputObjects;

use Automattic\WooCommerce\Api\ObjectWithId;

#[Description('Information about a line order.')]
class OrderLine extends ObjectWithId {

	#[Description('Name of the item.')]
	public string $name;

	#[Description('Quantity of the item.')]
	public int $quantity;

	#[Description('Total line amount.')]
	#[Argument('decimals', 'int', 'Amount of decimals to round the amount to. Default is 2.', 2)]
	public float $line_amount;
}

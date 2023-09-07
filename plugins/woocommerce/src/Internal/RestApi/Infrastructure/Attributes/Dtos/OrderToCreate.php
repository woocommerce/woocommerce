<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\ArrayTypeAttribute as ArrayType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;

/**
 * DTO to represent a new order to be created.
 */
#[Dto(true, 'WooCommerce order creation parameters')]
#[Description('Holds the data for an order to be created.')]
class OrderToCreate {
	#[Description("The order items.")]
	#[ArrayType('OrderItem')]
	public array $items;
}

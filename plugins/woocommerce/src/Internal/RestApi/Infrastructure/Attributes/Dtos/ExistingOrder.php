<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\ArrayTypeAttribute as ArrayType;

/**
 * DTO to represent an already existing order.
 */
#[Dto(true, 'WooCommerce order')]
#[Description('Holds the data for an existing order.')]
class ExistingOrder {
	#[Description("The order id.")]
	public int $id;

	#[Description("The order items.")]
	#[ArrayType('OrderItem')]
	public array $items;
}

<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\ArrayTypeAttribute as ArrayType;

#[Dto(true, 'WooCommerce order')]
class ExistingOrder {
	#[Description("The order id")]
	public int $id;

	public string $description_1;

	public ?string $description_2 = 'foo';

	#[ArrayType('int')]
	public array $items;
}

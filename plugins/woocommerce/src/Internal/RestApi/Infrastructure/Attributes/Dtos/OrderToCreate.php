<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;

#[Dto(true, 'WooCommerce order creation parameters')]
#[Description('Holds the arguments for an order to be created.')]
class OrderToCreate {
	public ?string $description_1;

	public ?string $description_2 = 'foo';
}

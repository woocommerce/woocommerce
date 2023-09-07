<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;

#[Dto(true, 'Input parameter location')]
#[Description( 'Indicates where an input parameter for an endpoint is supposed to be located.' )]
enum InputParameterLocation: string
{
	case Path = 'path';
	case Query = 'query';
	case Body = 'body';
}

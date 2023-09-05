<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;

#[Dto(true, 'Input parameter location')]
enum InputParameterLocation: string
{
	case Url = 'url';
	case Query = 'query';

	#[Description('In the body')]
	case Body = 'body';
}

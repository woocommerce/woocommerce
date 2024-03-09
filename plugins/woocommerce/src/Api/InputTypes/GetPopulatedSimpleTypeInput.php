<?php

namespace Automattic\WooCommerce\Api\InputTypes;

use Automattic\WooCommerce\Api\Enums\MsxGeneration;

#[Description( 'Input type for the \'get populated simple type\' operation.' )]
class GetPopulatedSimpleTypeInput {
	public int $the_input_int;

	public ?string $the_input_string;

	public float $the_input_float;

	#[ArrayType( 'string' )]
	#[EnumType( MsxGeneration::class )]
	public array $the_input_msx_generations;
}

<?php

namespace Automattic\WooCommerce\Api\InputTypes;

#[Description( 'Input type for a hypothetical \'create simple type\' operation.' )]
class CreateSimpleTypeInput {
	public int $the_input_int;

	public ?string $the_input_string;

	public float $the_input_float;
}

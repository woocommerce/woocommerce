<?php

namespace Automattic\WooCommerce\Api\ObjectTypes;

use Automattic\WooCommerce\Api\Interfaces\IdentifiableObject;

class ComplexType {
	use IdentifiableObject;

	public int $complexity_level = 9001;

	public SimpleType $the_simple_type;

	public ?SimpleType $the_nullable_simple_type;

	#[ArrayType( SimpleType::class )]
	public array $the_array_of_simple_types;
}

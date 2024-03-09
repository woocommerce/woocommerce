<?php

namespace Automattic\WooCommerce\Api\ObjectTypes;

use Automattic\WooCommerce\Api\Enums\MsxGeneration;
use Automattic\WooCommerce\Api\Enums\OrdinalNumber;
use Automattic\WooCommerce\Api\Interfaces\IdentifiableObject;

#[Description( 'An object type that has scalars, enums or arrays of scalars as properties.' )]
class SimpleType {
	use IdentifiableObject;

	public string $the_string;

	public ?string $the_nullable_string;

	public int $the_int;

	public ?int $the_nullable_int;

	public float $the_float;

	public ?float $the_nullable_float;

	public bool $the_bool;

	public ?bool $the_nullable_bool;

	#[ArrayType( 'int' )]
	public array $the_array_of_ints;

	#[ArrayType( 'int', true )]
	public array $the_array_of_nullable_ints;

	#[ArrayType( 'int' )]
	public ?array $the_nullable_array_of_ints;

	#[ArrayType( 'int', true )]
	public ?array $the_nullable_array_of_nullable_ints;

	#[EnumType( OrdinalNumber::class )]
	public ?int $the_ordinal_number;

	#[EnumType( OrdinalNumber::class )]
	public ?int $the_nullable_ordinal_type;

	#[ArrayType( 'string' )]
	#[EnumType( MsxGeneration::class )]
	public array $the_msx_generations;
}

<?php

namespace Automattic\WooCommerce\Api;

use Automattic\WooCommerce\Api\Enums\MsxGeneration;
use Automattic\WooCommerce\Api\Enums\OrdinalNumber;
use Automattic\WooCommerce\Api\InputTypes\CreateSimpleTypeInput;
use Automattic\WooCommerce\Api\InputTypes\GetPopulatedSimpleTypeInput;
use Automattic\WooCommerce\Api\Interfaces\IdentifiableObject;
use Automattic\WooCommerce\Api\ObjectTypes\ComplexType;
use Automattic\WooCommerce\Api\ObjectTypes\IdentifiableObjectX;
use Automattic\WooCommerce\Api\ObjectTypes\RecursiveType;
use Automattic\WooCommerce\Api\ObjectTypes\SimpleType;

#[Description( 'Sandbox API class with misc methods.' )]
class Sandbox {
	#[WebQuery( 'RandomSimpleType' )]
	#[Description( 'Get an instance of SimpleType with random(ish)values.' )]
	public function get_random_simple_type(): SimpleType {
		$t                             = new SimpleType();
		$t->id                         = 1000;
		$t->name                       = 'Simple type ' . $t->id;
		$t->the_string                 = 'Some string';
		$t->the_int                    = 34;
		$t->the_float                  = 12.34;
		$t->the_bool                   = true;
		$t->the_array_of_ints          = array( 1, 2, 3, 4 );
		$t->the_array_of_nullable_ints = array( 1, null, 2, null, 3 );
		$t->the_ordinal_number         = OrdinalNumber::SECOND;
		$t->the_msx_generations        = array( MsxGeneration::MSX2, MsxGeneration::MSX2PLUS );
		return $t;
	}

	#[WebQuery( 'RandomSimpleTypes' )]
	#[ArrayType( SimpleType::class )]
	#[Description( 'Get a few instances of SimpleType with random(ish)values.' )]
	public function get_random_simple_types(
		#[Description( 'How many instances to get?' )] ?int $how_many = 3 ): array {
		if ( $how_many < 1 || $how_many > 100 ) {
			throw new \InvalidArgumentException( '$how_many must be at least 1 and at most 100.' );
		}
		$result = array();
		for ( $i = 1; $i <= $how_many; $i++ ) {
			$instance       = $this->get_random_simple_type();
			$instance->id   = $i;
			$instance->name = 'Simple type ' . $i;
			$result[]       = $instance;
		}
		return $result;
	}

	#[WebQuery( 'PopulatedSimpleType' )]
	#[Description( 'Get an instance of SimpleType with values populated from the input.' )]
	public function get_populated_simple_type( GetPopulatedSimpleTypeInput $input ): SimpleType {
		$value                      = $this->get_random_simple_type();
		$value->the_int             = $input->the_input_int;
		$value->the_float           = $input->the_input_float;
		$value->the_msx_generations = $input->the_input_msx_generations;
		if ( ! is_null( $input->the_input_string ) ) {
			$value->the_string = $input->the_input_string;
		}

		return $value;
	}

	#[WebQuery( 'ComplexType' )]
	#[Description( 'Get an instance of ComplexType with random(ish)values.' )]
	public function get_random_complex_type( ?array $_fields_info = null ): ComplexType {
		$t = new ComplexType();

		$t->id   = 2000;
		$t->name = 'Complex type ' . $t->id;
		if ( is_null( $_fields_info ) || isset( $_fields_info['the_simple_type'] ) ) {
			$t->the_simple_type = $this->get_random_simple_type();
		}
		if ( is_null( $_fields_info ) || isset( $_fields_info['the_array_of_simple_types'] ) ) {
			$t->the_array_of_simple_types = $this->get_random_simple_types();
		}

		return $t;
	}

	#[WebQuery( 'IdentifiableObjects' )]
	#[ArrayType( IdentifiableObject::class )]
	#[Description( 'Example of query whose return type is an interface.' )]
	public function get_identifiable_objects(): array {
		return array(
			$this->get_random_simple_type(),
			$this->get_random_complex_type(),
		);
	}

	#[WebQuery( 'RecursiveType' )]
	#[Description( 'Demonstration of the support for recursive types.' )]
	public function get_recursive_type(): RecursiveType {
		$t                              = new RecursiveType();
		$t->the_int                     = 1;
		$t->the_recursive_type          = new RecursiveType();
		$t->the_recursive_type->the_int = 2;
		return $t;
	}

	#[WebMutation( 'CreateSimpleType' )]
	#[Description( '(Pseudo)mutation example, it\'s supposed to perform a change in the server.' )]
	public function create_simple_type( CreateSimpleTypeInput $input ): SimpleType {
		$t             = $this->get_random_simple_type();
		$t->the_int    = $input->the_input_int;
		$t->the_string = $input->the_input_string;
		$t->the_float  = $input->the_input_float;
		return $t;
	}

	#[WebQuery( 'ThrowArgumentError' )]
	public function throw_argument_error(): SimpleType {
		throw new \InvalidArgumentException( "I don't like that argument of yours." );
	}

	#[WebQuery( 'ThrowClientAwareError' )]
	public function throw_client_aware_error(): SimpleType {
		$e = new class() extends \Exception {
			public function __construct( string $message = '', int $code = 0, ?Throwable $previous = null ) {
				parent::__construct( 'This is an error. As the client, you should be seeing this.', $code, $previous );
			}

			public function is_client_aware(): bool {
				return true;
			}

			public function get_status_code() {
				return 402;
			}
		};

		throw $e;
	}

	#[WebQuery( 'ThrowInternalError' )]
	public function throw_internal_error(): SimpleType {
		throw new \Exception( "Well that's an unexpected error. The client shouldn't see this." );
	}
}


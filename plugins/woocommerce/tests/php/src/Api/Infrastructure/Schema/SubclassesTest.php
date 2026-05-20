<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Infrastructure\Schema;

use Automattic\WooCommerce\Api\Infrastructure\Schema\CustomScalarType;
use Automattic\WooCommerce\Api\Infrastructure\Schema\EnumType;
use Automattic\WooCommerce\Api\Infrastructure\Schema\Error;
use Automattic\WooCommerce\Api\Infrastructure\Schema\InputObjectType;
use Automattic\WooCommerce\Api\Infrastructure\Schema\InterfaceType;
use Automattic\WooCommerce\Api\Infrastructure\Schema\ObjectType;
use Automattic\WooCommerce\Api\Infrastructure\Schema\Schema;
use Automattic\WooCommerce\Api\Infrastructure\Schema\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Error\ClientAware;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Error as WebonyxError;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CustomScalarType as WebonyxCustomScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType as WebonyxEnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType as WebonyxInputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType as WebonyxInterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType as WebonyxObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema as WebonyxSchema;
use WC_Unit_Test_Case;

/**
 * Smoke tests for the no-op subclasses in the Schema/ surface. Each subclass
 * must extend its webonyx counterpart and accept the same configuration
 * payload, since generated code constructs them with webonyx-shaped configs.
 */
class SubclassesTest extends WC_Unit_Test_Case {
	/**
	 * @testdox ObjectType extends the webonyx ObjectType.
	 */
	public function test_object_type_extends_webonyx_object_type(): void {
		$type = new ObjectType(
			array(
				'name'   => 'Foo',
				'fields' => array(
					'bar' => array( 'type' => Type::string() ),
				),
			)
		);

		$this->assertInstanceOf( WebonyxObjectType::class, $type );
		$this->assertSame( 'Foo', $type->name );
	}

	/**
	 * @testdox InputObjectType extends the webonyx InputObjectType.
	 */
	public function test_input_object_type_extends_webonyx_input_object_type(): void {
		$type = new InputObjectType(
			array(
				'name'   => 'FooInput',
				'fields' => array(
					'bar' => array( 'type' => Type::string() ),
				),
			)
		);

		$this->assertInstanceOf( WebonyxInputObjectType::class, $type );
		$this->assertSame( 'FooInput', $type->name );
	}

	/**
	 * @testdox EnumType extends the webonyx EnumType.
	 */
	public function test_enum_type_extends_webonyx_enum_type(): void {
		$type = new EnumType(
			array(
				'name'   => 'Status',
				'values' => array(
					'ACTIVE'   => array( 'value' => 'active' ),
					'INACTIVE' => array( 'value' => 'inactive' ),
				),
			)
		);

		$this->assertInstanceOf( WebonyxEnumType::class, $type );
		$this->assertSame( 'Status', $type->name );
	}

	/**
	 * @testdox InterfaceType extends the webonyx InterfaceType.
	 */
	public function test_interface_type_extends_webonyx_interface_type(): void {
		$type = new InterfaceType(
			array(
				'name'   => 'Node',
				'fields' => array(
					'id' => array( 'type' => Type::nonNull( Type::int() ) ),
				),
			)
		);

		$this->assertInstanceOf( WebonyxInterfaceType::class, $type );
		$this->assertSame( 'Node', $type->name );
	}

	/**
	 * @testdox CustomScalarType extends the webonyx CustomScalarType.
	 */
	public function test_custom_scalar_type_extends_webonyx_custom_scalar_type(): void {
		$type = new CustomScalarType(
			array(
				'name'      => 'MyDate',
				'serialize' => static fn( $v ) => (string) $v,
			)
		);

		$this->assertInstanceOf( WebonyxCustomScalarType::class, $type );
		$this->assertSame( 'MyDate', $type->name );
	}

	/**
	 * @testdox Schema extends the webonyx Schema and returns its query type.
	 */
	public function test_schema_extends_webonyx_schema(): void {
		$query = new ObjectType(
			array(
				'name'   => 'Query',
				'fields' => array(
					'hello' => array(
						'type'    => Type::string(),
						'resolve' => static fn() => 'world',
					),
				),
			)
		);

		$schema = new Schema( array( 'query' => $query ) );

		$this->assertInstanceOf( WebonyxSchema::class, $schema );
		$this->assertSame( $query, $schema->getQueryType() );
	}

	/**
	 * @testdox Error extends the webonyx Error and is ClientAware.
	 */
	public function test_error_extends_webonyx_error_and_is_client_safe(): void {
		$error = new Error( 'visible to clients' );

		$this->assertInstanceOf( WebonyxError::class, $error );
		$this->assertInstanceOf( ClientAware::class, $error );
		$this->assertSame( 'visible to clients', $error->getMessage() );
	}
}

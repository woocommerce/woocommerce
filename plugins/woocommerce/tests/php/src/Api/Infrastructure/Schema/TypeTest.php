<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Infrastructure\Schema;

use Automattic\WooCommerce\Api\Infrastructure\Schema\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\BooleanType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FloatType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\IDType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\IntType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type as WebonyxType;
use WC_Unit_Test_Case;

/**
 * Tests for the {@see Type} static facade. Each scalar accessor must return
 * the matching webonyx singleton, and the modifiers must produce wrappers
 * around the supplied inner type.
 */
class TypeTest extends WC_Unit_Test_Case {
	/**
	 * @testdox int() returns the webonyx Int singleton.
	 */
	public function test_int_returns_webonyx_int_singleton(): void {
		$this->assertInstanceOf( IntType::class, Type::int() );
		$this->assertSame( WebonyxType::int(), Type::int() );
	}

	/**
	 * @testdox string() returns the webonyx String singleton.
	 */
	public function test_string_returns_webonyx_string_singleton(): void {
		$this->assertInstanceOf( StringType::class, Type::string() );
		$this->assertSame( WebonyxType::string(), Type::string() );
	}

	/**
	 * @testdox boolean() returns the webonyx Boolean singleton.
	 */
	public function test_boolean_returns_webonyx_boolean_singleton(): void {
		$this->assertInstanceOf( BooleanType::class, Type::boolean() );
		$this->assertSame( WebonyxType::boolean(), Type::boolean() );
	}

	/**
	 * @testdox float() returns the webonyx Float singleton.
	 */
	public function test_float_returns_webonyx_float_singleton(): void {
		$this->assertInstanceOf( FloatType::class, Type::float() );
		$this->assertSame( WebonyxType::float(), Type::float() );
	}

	/**
	 * @testdox id() returns the webonyx ID singleton.
	 */
	public function test_id_returns_webonyx_id_singleton(): void {
		$this->assertInstanceOf( IDType::class, Type::id() );
		$this->assertSame( WebonyxType::id(), Type::id() );
	}

	/**
	 * @testdox nonNull() wraps an inner type.
	 */
	public function test_non_null_wraps_an_inner_type(): void {
		$wrapped = Type::nonNull( Type::string() );

		$this->assertInstanceOf( NonNull::class, $wrapped );
		$this->assertSame( Type::string(), $wrapped->getWrappedType() );
	}

	/**
	 * @testdox listOf() wraps an inner type.
	 */
	public function test_list_of_wraps_an_inner_type(): void {
		$wrapped = Type::listOf( Type::int() );

		$this->assertInstanceOf( ListOfType::class, $wrapped );
		$this->assertSame( Type::int(), $wrapped->getWrappedType() );
	}

	/**
	 * @testdox modifiers compose into nested wrappers.
	 */
	public function test_modifiers_compose(): void {
		$type = Type::nonNull( Type::listOf( Type::nonNull( Type::int() ) ) );

		$this->assertInstanceOf( NonNull::class, $type );
		$inner = $type->getWrappedType();
		$this->assertInstanceOf( ListOfType::class, $inner );
		$this->assertInstanceOf( NonNull::class, $inner->getWrappedType() );
		$this->assertSame( Type::int(), $inner->getWrappedType()->getWrappedType() );
	}
}

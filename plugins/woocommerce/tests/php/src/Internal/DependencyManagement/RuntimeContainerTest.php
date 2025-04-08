<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Blocks\Assets\Api as BlocksAssetsApi;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Internal\DependencyManagement\RuntimeContainer;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassInterface;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassThatHasReferenceArgumentsInInit;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithNestedDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithPrivateInjectionMethod;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithRecursiveDependencies1;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithRecursiveDependencies2;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithRecursiveDependencies3;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithScalarInjectionMethodArgument;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithStaticInjectionMethod;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithUntypedInjectionMethodArgument;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClassWithInnerDependency;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\InnerDependencyClass;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassThatThrowsOnInit;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithStoreApiDependency;

/**
 * Tests for RuntimeContainer.
 */
class RuntimeContainerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var ExtendedContainer
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->sut = new RuntimeContainer(
			array( 'Foo\Bar' => $this )
		);
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class outside the root WooCommerce namespace.
	 */
	public function test_exception_when_trying_to_resolve_class_outside_root_namespace() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Attempt to get an instance of class 'Fizz\Buzz', which is not in the Automattic\WooCommerce\ namespace. Did you forget to add a namespace import?" );

		$this->sut->get( 'Fizz\Buzz' );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class that doesn't exist.
	 */
	public function test_exception_when_trying_to_resolve_non_existing_class() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Attempt to get an instance of class 'Automattic\WooCommerce\Fizz\Buzz', which doesn't exist." );

		$this->sut->get( 'Automattic\WooCommerce\Fizz\Buzz' );
	}

	/**
	 * @testdox 'get' can resolve classes passed to the constructor in the initial resolve cache.
	 */
	public function test_can_get_instance_included_in_initial_resolved_cache() {
		$this->assertSame( $this, $this->sut->get( 'Foo\Bar' ) );
	}

	/**
	 * @testdox 'get' properly resolves and caches classes, and its dependencies, if they are in the root WooCommerce namespace.
	 */
	public function test_resolves_and_caches_classes_and_dependencies() {
		ClassWithNestedDependencies::$instances_count        = 0;
		DependencyClassWithInnerDependency::$instances_count = 0;
		InnerDependencyClass::$instances_count               = 0;

		$instance_1 = $this->sut->get( ClassWithNestedDependencies::class );
		$instance_2 = $this->sut->get( ClassWithNestedDependencies::class );

		$this->assertInstanceOf( ClassWithNestedDependencies::class, $instance_1 );
		$this->assertSame( $instance_2, $instance_1 );
		$this->assertEquals( 1, ClassWithNestedDependencies::$instances_count );

		$this->assertInstanceOf( DependencyClassWithInnerDependency::class, $instance_1->dependency_class );
		$this->assertEquals( 1, DependencyClassWithInnerDependency::$instances_count );

		$this->assertInstanceOf( InnerDependencyClass::class, $instance_1->dependency_class->inner_dependency );
		$this->assertEquals( 1, InnerDependencyClass::$instances_count );
	}

	/**
	 * @testdox 'get' doesn't invoke the 'init' method of the resolved classes if the method is private.
	 */
	public function test_private_init_method_is_not_invoked() {
		$instance = $this->sut->get( ClassWithPrivateInjectionMethod::class );

		$this->assertFalse( $instance->init_executed );
	}

	/**
	 * @testdox 'get' doesn't invoke the 'init' method of the resolved classes if the method is static.
	 */
	public function test_static_init_method_is_not_invoked() {
		$this->sut->get( ClassWithStaticInjectionMethod::class );

		$this->assertFalse( ClassWithStaticInjectionMethod::$init_executed );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class that has a scalar argument in the 'init' method.
	 */
	public function test_cant_use_scalar_init_arguments() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Error resolving '" . ClassWithScalarInjectionMethodArgument::class . "': argument '\$scalar_argument_without_default_value' is not of a class type." );

		$this->sut->get( ClassWithScalarInjectionMethodArgument::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class that has an unnamed argument in the 'init' method.
	 */
	public function test_cant_use_untyped_init_arguments() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Error resolving '" . ClassWithUntypedInjectionMethodArgument::class . "': argument '\$some_argument' doesn't have a type declaration." );

		$this->sut->get( ClassWithUntypedInjectionMethodArgument::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve an interface.
	 */
	public function test_cant_resolve_interfaces() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Attempt to get an instance of class '" . ClassInterface::class . "', which doesn't exist." );

		$this->sut->get( ClassInterface::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a trait.
	 */
	public function test_cant_resolve_traits() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Attempt to get an instance of class '" . SomeTrait::class . "', which doesn't exist." );

		$this->sut->get( SomeTrait::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class that has recursive dependencies.
	 */
	public function test_recursive_dependencies_throws_error() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Recursive resolution of class '" . ClassWithRecursiveDependencies1::class . "'. Resolution chain: " . ClassWithRecursiveDependencies1::class . ', ' . ClassWithRecursiveDependencies2::class . ', ' . ClassWithRecursiveDependencies3::class );

		$this->sut->get( ClassWithRecursiveDependencies1::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when trying to resolve a class that has an argument passed by reference in the 'init' method.
	 */
	public function test_init_cant_contain_methods_by_reference() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Error resolving '" . ClassThatHasReferenceArgumentsInInit::class . "': argument '\$dependency_by_reference' is passed by reference." );

		$this->sut->get( ClassThatHasReferenceArgumentsInInit::class );
	}

	/**
	 * @testdox 'get' throws 'ContainerException' when 'ReflectionException' is thrown while trying to instantiate the class.
	 */
	public function test_reflection_exceptions_are_thrown_as_container_exception() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Reflection error when resolving '" . ClassThatThrowsOnInit::class . "': (ReflectionException) This doesn't reflect well." );

		ClassThatThrowsOnInit::$exception = new \ReflectionException( "This doesn't reflect well." );
		$this->sut->get( ClassThatThrowsOnInit::class );
	}

	/**
	 * @testdox 'get' doesn't catch exceptions thrown inside the resolved class constructor or 'init' method.
	 */
	public function test_init_exceptions_are_thrown_as_is() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "The argument that isn't here is invalid." );

		ClassThatThrowsOnInit::$exception = new \InvalidArgumentException( "The argument that isn't here is invalid." );
		$this->sut->get( ClassThatThrowsOnInit::class );
	}

	/**
	 * @testdox 'get' resolves Store API classes using the Store API container.
	 */
	public function test_store_api_dependencies_are_resolved_using_the_store_api_container() {
		$resolved = $this->sut->get( ClassWithStoreApiDependency::class );

		$this->assertSame( $resolved->dependency_class, StoreApi::container()->get( ExtendSchema::class ) );
	}

	/**
	 * @testdox 'get' resolves Blocks classes using the Store API container.
	 */
	public function test_blocks_classes_are_resolved_using_the_blocks_container() {
		$resolved = $this->sut->get( BlocksAssetsApi::class );

		$this->assertSame( $resolved, BlocksPackage::container()->get( BlocksAssetsApi::class ) );
	}

	/**
	 * @testdox 'has' returns true for classes in the root WooCommerce namespace, or passed to the constructor in the initial resolve cache.
	 */
	public function test_has_returns_true_for_classes_in_the_root_namespace_or_in_the_initial_resolve_list() {
		$this->assertTrue( $this->sut->has( ClassWithNestedDependencies::class ) );
		$this->assertTrue( $this->sut->has( 'Foo\Bar' ) );
	}

	/**
	 * @testdox 'has' properly handles '\' characters at the beginning or end of the class name.
	 */
	public function test_has_handles_backslash_at_the_beginning_or_end_of_class_names() {
		$this->assertTrue( $this->sut->has( '\\' . ClassWithNestedDependencies::class . '\\' ) );
		$this->assertTrue( $this->sut->has( '\Foo\Bar\\' ) );
	}

	/**
	 * @testdox 'has' returns false for classes not in the root WooCommerce namespace.
	 */
	public function test_has_returns_false_for_classes_not_in_the_root_namespace() {
		$this->assertFalse( $this->sut->has( 'Fizz\Buzz' ) );
	}
}

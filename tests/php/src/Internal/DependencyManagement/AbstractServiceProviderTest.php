<?php
/**
 * AbstractServiceProviderTests class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\Definition;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithInjectionMethodArgumentWithoutTypeHint;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithNonFinalInjectionMethod;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithPrivateInjectionMethod;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithScalarInjectionMethodArgument;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;
use Automattic\WooCommerce\Vendor\League\Container\Definition\DefinitionInterface;

/**
 * Tests for AbstractServiceProvider.
 */
class AbstractServiceProviderTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var AbstractServiceProvider
	 */
	private $sut;

	/**
	 * The container used for tests.
	 *
	 * @var ExtendedContainer
	 */
	private $container;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->container = new ExtendedContainer();

		$this->sut = new class() extends AbstractServiceProvider {
			// phpcs:disable

			/**
			 * Public version of add_with_auto_arguments, which is usually protected.
			 */
			public function add_with_auto_arguments( string $class_name, $concrete = null, bool $shared = false ) : DefinitionInterface {
				return parent::add_with_auto_arguments( $class_name, $concrete, $shared );
			}

			/**
			 * The mandatory 'register' method (defined in the base class as abstract).
			 * Not implemented because this class is tested on its own, not as a service provider actually registered on a container.
			 */
			public function register() {}

			// phpcs:enable
		};

		$this->sut->setContainer( $this->container );
	}

	/**
	 * Runs before all the tests of the class.
	 */
	public static function setUpBeforeClass() {
		/**
		 * Return a new instance of ClassWithDependencies.
		 *
		 * @param DependencyClass $dependency The dependency to inject.
		 * @return ClassWithDependencies The new instance.
		 */
		function get_new_dependency_class( DependencyClass $dependency ) {
			return new ClassWithDependencies( $dependency );
		};
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if an invalid class name is passed as class name.
	 */
	public function test_add_with_auto_arguments_throws_on_non_class_passed_as_class_name() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add 'foobar', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add_with_auto_arguments( 'foobar' );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a private injection method.
	 */
	public function test_add_with_auto_arguments_throws_on_class_private_method_injection() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Method '" . Definition::INJECTION_METHOD . "' of class '" . ClassWithPrivateInjectionMethod::class . "' isn't 'final public', instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithPrivateInjectionMethod::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a non-final injection method.
	 */
	public function test_add_with_auto_arguments_throws_on_class_non_final_method_injection() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Method '" . Definition::INJECTION_METHOD . "' of class '" . ClassWithNonFinalInjectionMethod::class . "' isn't 'final', instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithNonFinalInjectionMethod::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed concrete is a class with a private injection method.
	 */
	public function test_add_with_auto_arguments_throws_on_concrete_private_method_injection() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Method '" . Definition::INJECTION_METHOD . "' of class '" . ClassWithPrivateInjectionMethod::class . "' isn't 'final public', instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, ClassWithPrivateInjectionMethod::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed concrete is a class with a non-final injection method.
	 */
	public function test_add_with_auto_arguments_throws_on_concrete_non_final_method_injection() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Method '" . Definition::INJECTION_METHOD . "' of class '" . ClassWithNonFinalInjectionMethod::class . "' isn't 'final', instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, ClassWithNonFinalInjectionMethod::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a method argument without type hint.
	 */
	public function test_add_with_auto_arguments_throws_on_method_argument_without_type_hint() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Argument 'argument_without_type_hint' of class '" . ClassWithInjectionMethodArgumentWithoutTypeHint::class . "' doesn't have a type hint or has one that doesn't specify a class." );

		$this->sut->add_with_auto_arguments( ClassWithInjectionMethodArgumentWithoutTypeHint::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a method argument with a scalar type hint.
	 */
	public function test_add_with_auto_arguments_throws_on_method_argument_with_scalar_type_hint() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "Argument 'scalar_argument_without_default_value' of class '" . ClassWithScalarInjectionMethodArgument::class . "' doesn't have a type hint or has one that doesn't specify a class." );

		$this->sut->add_with_auto_arguments( ClassWithScalarInjectionMethodArgument::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when no concrete is passed.
	 *
	 * @testWith [true, 1]
	 *           [false, 2]
	 *
	 * @param bool $shared Whether to register the test class as shared or not.
	 * @param int  $expected_constructions_count Expected number of times that the test class will have been instantiated.
	 */
	public function test_add_with_auto_arguments_works_as_expected_with_no_concrete( bool $shared, int $expected_constructions_count ) {
		ClassWithDependencies::$instances_count = 0;

		$this->container->share( DependencyClass::class );
		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, null, $shared );

		$this->container->get( ClassWithDependencies::class );
		$resolved = $this->container->get( ClassWithDependencies::class );

		// A new instance is created for each resolution or not, depending on $shared.
		$this->assertEquals( $expected_constructions_count, ClassWithDependencies::$instances_count );

		// Arguments with default values are honored.
		$this->assertEquals( ClassWithDependencies::SOME_NUMBER, $resolved->some_number );

		// Method arguments are filled as expected.
		$this->assertSame( $this->container->get( DependencyClass::class ), $resolved->dependency_class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete representing a class name is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_class_name() {
		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, DependencyClass::class );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( DependencyClass::class, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is an object is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_object() {
		$object = new DependencyClass();

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, $object );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertSame( $object, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is a closure is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_a_closure() {
		$this->container->share( DependencyClass::class );
		$callable = function( DependencyClass $dependency ) {
			return new ClassWithDependencies( $dependency );
		};

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, $callable );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( ClassWithDependencies::class, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is a function name is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_a_function_name() {
		$this->container->share( DependencyClass::class );

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, __NAMESPACE__ . '\get_new_dependency_class' );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( ClassWithDependencies::class, $resolved );
	}
}

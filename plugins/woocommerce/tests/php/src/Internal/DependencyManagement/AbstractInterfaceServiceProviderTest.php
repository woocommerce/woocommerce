<?php
/**
 * AbstractInterfaceServiceProviderTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders\AbstractInterfaceServiceProvider;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\AnotherClassInterface;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassInterface;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithInterface;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithMultipleInterfaces;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithNoInterface;
use Automattic\WooCommerce\Vendor\League\Container\Definition\DefinitionInterface;
use stdClass;

/**
 * Tests for AbstractInterfaceServiceProvider.
 */
class AbstractInterfaceServiceProviderTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var AbstractInterfaceServiceProvider
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
	public function setUp(): void {
		$this->container = new ExtendedContainer();

		$this->sut = new class() extends AbstractInterfaceServiceProvider {
			// phpcs:disable

			public $provides = [
				ClassWithInterface::class,
				ClassWithMultipleInterfaces::class,
				ClassWithNoInterface::class,
			];

			/**
			 * Register a class in the container and add tags for all the interfaces it implements.
			 *
			 * @param string    $id         Entry ID (typically a class or interface name).
			 * @param mixed     $concrete   Concrete entity to register under that ID, null for automatic creation.
			 * @param bool|null $shared     Whether to register the class as shared (`get` always returns the same instance).
			 *
			 * @return DefinitionInterface
			 */
			public function add_with_implements_tags( string $id, $concrete = null, ?bool $shared = null ): DefinitionInterface {
				return parent::add_with_implements_tags( $id, $concrete, $shared );
			}

			/**
			 * Register a class in the container and add tags for all the interfaces it implements.
			 *
			 * @param string    $id         Entry ID (typically a class or interface name).
			 * @param mixed     $concrete   Concrete entity to register under that ID, null for automatic creation.
			 *
			 * @return DefinitionInterface
			 */
			public function share_with_implements_tags( string $id, $concrete = null ): DefinitionInterface {
				return parent::share_with_implements_tags( $id, $concrete );
			}

			/**
			 * The mandatory 'register' method (defined in the base class as abstract).
			 * Not implemented because this class is tested on its own, not as a service
			 * provider actually registered on a container.
			 */
			public function register() {}

			// phpcs:enable
		};

		$this->sut->setContainer( $this->container );
	}

	/**
	 * Tests adding a scalar value provides an exception.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_throws_on_a_scalar_passed() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add '123', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add_with_implements_tags( 123 );
	}

	/**
	 * Tests adding a wrong namespace value provides an exception.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_throws_on_not_a_proper_namespace_class_passed() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add 'stdClass', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add_with_implements_tags( stdClass::class );
	}

	/**
	 * Tests adding a class implementing an interface adds the interface as a tag.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_for_a_class_with_a_single_interface() {
		$definition = $this->sut->add_with_implements_tags( ClassWithInterface::class );

		$this->assertTrue( $definition->hasTag( ClassInterface::class ) );
	}

	/**
	 * Tests adding a class implementing multiple interfaces adds all the interfaces as tags.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_for_a_class_with_multiple_interfaces() {
		$definition = $this->sut->add_with_implements_tags( ClassWithMultipleInterfaces::class );

		$this->assertTrue( $definition->hasTag( ClassInterface::class ) );
		$this->assertTrue( $definition->hasTag( AnotherClassInterface::class ) );
	}

	/**
	 * Tests adding a class with no interfaces doesn't add any tags.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_for_a_class_with_no_interfaces() {
		$definition = $this->sut->add_with_implements_tags( ClassWithNoInterface::class );

		$this->assertFalse( $definition->hasTag( ClassInterface::class ) );
		$this->assertFalse( $definition->hasTag( AnotherClassInterface::class ) );
	}

	/**
	 * Tests adding a class with multiple interfaces adds interfaces to aliases the service provides.
	 *
	 * @return void
	 */
	public function test_add_with_implements_tags_for_a_class_with_a_single_interface_and_a_single_tag() {
		$this->sut->add_with_implements_tags( ClassWithMultipleInterfaces::class, null, false );

		$this->assertTrue( $this->sut->provides( ClassInterface::class ) );
		$this->assertTrue( $this->sut->provides( AnotherClassInterface::class ) );
	}

	/**
	 * Tests adding a class under alias returns the proper object.
	 *
	 * @return void
	 * @throws ContainerException Dependency injection error.
	 */
	public function test_add_with_implements_tags_with_concrete() {
		$this->sut->add_with_implements_tags( ClassWithNoInterface::class, ClassWithMultipleInterfaces::class );

		$resolved = $this->container->get( ClassWithNoInterface::class );

		$this->assertInstanceOf( ClassWithMultipleInterfaces::class, $resolved );
	}

	/**
	 * Tests sharing a class with multiple interfaces adds the interfaces as tags.
	 *
	 * @return void
	 */
	public function test_share_with_implements_tags_adds_proper_interface_tags() {
		$definition = $this->sut->share_with_implements_tags( ClassWithMultipleInterfaces::class, null );

		$this->assertTrue( $definition->hasTag( ClassInterface::class ) );
		$this->assertTrue( $definition->hasTag( AnotherClassInterface::class ) );
	}

	/**
	 * Tests sharing a class with multiple interfaces adds interfaces to aliases the service provides.
	 *
	 * @return void
	 */
	public function test_share_with_implements_tags_does_proper_provides() {
		$this->sut->share_with_implements_tags( ClassWithMultipleInterfaces::class, null );

		$this->assertTrue( $this->sut->provides( ClassInterface::class ) );
		$this->assertTrue( $this->sut->provides( AnotherClassInterface::class ) );
	}

	/**
	 * Tests sharing a class with multiple interfaces returns the same instance.
	 *
	 * @return void
	 * @throws ContainerException Dependency injection error.
	 */
	public function test_share_with_implements_tags_returns_the_same_instance() {
		$definition = $this->sut->share_with_implements_tags( ClassWithMultipleInterfaces::class, null );

		$this->assertTrue( $definition->isShared() );

		$object = $this->container->get( ClassWithMultipleInterfaces::class );

		$this->assertEquals( $definition->resolve(), $object );
	}
}

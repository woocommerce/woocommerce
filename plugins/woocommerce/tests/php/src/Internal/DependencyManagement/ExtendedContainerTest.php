<?php
/**
 * ExtendedContainerTests class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;

/**
 * Tests for ExtendedContainer.
 */
class ExtendedContainerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var ExtendedContainer
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->sut = new ExtendedContainer();
	}

	/**
	 * @testdox 'add' should throw an exception when trying to register a class not in the WooCommerce root namespace.
	 */
	public function test_add_throws_when_trying_to_register_class_in_forbidden_namespace() {
		$external_class = \WooCommerce::class;

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add '$external_class', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add( $external_class );
	}

	/**
	 * @testdox 'add' should throw an exception when trying to register a concrete class not in the WooCommerce root namespace.
	 */
	public function test_add_throws_when_trying_to_register_concrete_class_in_forbidden_namespace() {
		$external_class = \WooCommerce::class;

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add concrete '$external_class', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add( DependencyClass::class, $external_class );
	}

	/**
	 * @testdox 'add' should allow registering classes in the WooCommerce root namespace.
	 */
	public function test_add_allows_registering_classes_in_woocommerce_root_namespace() {
		$instance = new DependencyClass();
		$this->sut->add( DependencyClass::class, $instance, true );
		$resolved = $this->sut->get( DependencyClass::class );

		$this->assertSame( $instance, $resolved );
	}

	/**
	 * @testdox 'replace' should throw an exception when trying to replace a class that has not been previously registered.
	 */
	public function test_replace_throws_if_class_has_not_been_registered() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "The container doesn't have '" . DependencyClass::class . "' registered, please use 'add' instead of 'replace'." );

		$this->sut->replace( DependencyClass::class, null );
	}

	/**
	 * @testdox 'replace'
	 */
	public function test_replace_throws_if_concrete_not_in_woocommerce_root_namespace() {
		$instance = new DependencyClass();
		$this->sut->add( DependencyClass::class, $instance, true );

		$external_class = \WooCommerce::class;

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot use concrete '$external_class', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->replace( DependencyClass::class, $external_class );
	}

	/**
	 * @testdox 'replace' should allow to replace existing registrations.
	 */
	public function test_replace_allows_replacing_existing_registrations() {
		$instance_1 = new DependencyClass();
		$instance_2 = new DependencyClass();

		$this->sut->add( DependencyClass::class, $instance_1, true );
		$this->assertSame( $instance_1, $this->sut->get( DependencyClass::class ) );

		$this->sut->replace( DependencyClass::class, $instance_2, true );
		$this->assertSame( $instance_2, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testdox 'replace' should allow to replace existing registrations with anonymous classes.
	 */
	public function test_replace_allows_replacing_existing_registrations_with_anonymous_classes() {
		$instance_1 = new DependencyClass();
		$instance_2 = new class() extends DependencyClass {};

		$this->sut->add( DependencyClass::class, $instance_1, true );
		$this->assertSame( $instance_1, $this->sut->get( DependencyClass::class ) );

		$this->sut->replace( DependencyClass::class, $instance_2, true );
		$this->assertSame( $instance_2, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testdox 'reset_all_resolved' should discard cached resolutions for classes registered as 'shared'.
	 */
	public function test_reset_all_resolved_discards_cached_shared_resolutions() {
		$this->sut->add( DependencyClass::class );
		$this->sut->add( ClassWithDependencies::class, null, true )->addArgument( DependencyClass::class );
		ClassWithDependencies::$instances_count = 0;

		$this->sut->get( ClassWithDependencies::class );
		$this->assertEquals( 1, ClassWithDependencies::$instances_count );
		$this->sut->get( ClassWithDependencies::class );
		$this->assertEquals( 1, ClassWithDependencies::$instances_count );

		$this->sut->reset_all_resolved();

		$this->sut->get( ClassWithDependencies::class );
		$this->assertEquals( 2, ClassWithDependencies::$instances_count );
		$this->sut->get( ClassWithDependencies::class );
		$this->assertEquals( 2, ClassWithDependencies::$instances_count );
	}
}

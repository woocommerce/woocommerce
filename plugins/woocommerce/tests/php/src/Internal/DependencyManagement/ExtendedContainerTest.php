<?php
/**
 * ExtendedContainerTests class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\StoreApi\Legacy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DerivedDependencyClass;

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
	public function setUp(): void {
		$this->sut = new ExtendedContainer();
	}

	/**
	 * @testDox 'add' should throw an exception when trying to register a class not in the WooCommerce root namespace.
	 */
	public function test_add_throws_when_trying_to_register_class_in_forbidden_namespace() {
		$external_class = \WooCommerce::class;

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add '$external_class', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add( $external_class );
	}

	/**
	 * @testDox 'add' should throw an exception when trying to register a concrete class not in the WooCommerce root namespace.
	 */
	public function test_add_throws_when_trying_to_register_concrete_class_in_forbidden_namespace() {
		$external_class = \WooCommerce::class;

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "You cannot add concrete '$external_class', only classes in the Automattic\WooCommerce\ namespace are allowed." );

		$this->sut->add( DependencyClass::class, $external_class );
	}

	/**
	 * @testDox 'add' should allow registering classes in the WooCommerce root namespace.
	 */
	public function test_add_allows_registering_classes_in_woocommerce_root_namespace() {
		$instance = new DependencyClass();
		$this->sut->add( DependencyClass::class, $instance, true );
		$resolved = $this->sut->get( DependencyClass::class );

		$this->assertSame( $instance, $resolved );
	}

	/**
	 * @testDox 'replace' should throw an exception when trying to replace a class that has not been previously registered.
	 */
	public function test_replace_throws_if_class_has_not_been_registered() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "The container doesn't have '" . DependencyClass::class . "' registered, please use 'add' instead of 'replace'." );

		$this->sut->replace( DependencyClass::class, null );
	}

	/**
	 * @testDox 'replace' should throw an exception when trying to use a class outside the Automattic\WooCommerce\ namespace as  the replacement.
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
	 * @testDox 'replace' should allow to replace existing registrations with object instances.
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
	 * @testDox 'replace' should allow to replace existing registrations with anonymous classes.
	 */
	public function test_replace_allows_replacing_existing_registrations_with_anonymous_classes() {
		$instance_1 = new DependencyClass();
		$instance_2 = new class() extends DependencyClass {};

		$this->sut->add( DependencyClass::class, $instance_1, true );
		$this->assertSame( $instance_1, $this->sut->get( DependencyClass::class ) );

		$this->sut->replace( DependencyClass::class, $instance_2 );
		$this->assertSame( $instance_2, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testDox 'replace' should allow replacing existing registrations with other class names.
	 */
	public function test_replace_allows_replacing_existing_registrations_with_class_names() {
		$this->sut->add( DependencyClass::class, new DependencyClass(), true );
		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( DependencyClass::class ) );

		$this->sut->replace( DependencyClass::class, DerivedDependencyClass::class );
		$this->assertInstanceOf( DerivedDependencyClass::class, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testDox 'init' should_be executed when resolving the class in the instance passed to 'replace'
	 */
	public function test_init_is_executed_when_resolving_the_class_in_the_instance_passed_to_replace() {
		$this->sut->add( DependencyClass::class );
		$this->sut->add( ClassWithDependencies::class )->addArgument( DependencyClass::class );

		$this->sut->get( ClassWithDependencies::class );
		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( ClassWithDependencies::class )->dependency_class );

		$derived_class = new class() extends ClassWithDependencies {};
		$this->sut->replace( ClassWithDependencies::class, $derived_class );
		$this->sut->replace( DependencyClass::class, DerivedDependencyClass::class );

		$replaced_instance = $this->sut->get( ClassWithDependencies::class );
		$this->assertEquals( $derived_class, $replaced_instance );
		$this->assertInstanceOf( DerivedDependencyClass::class, $replaced_instance->dependency_class );
	}

	/**
	 * @testDox 'reset_all_resolved' should discard cached resolutions for classes registered as 'shared'.
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

	/**
	 * @testDox 'reset_replacement' should revert a replaced definition back to its original concrete.
	 */
	public function test_reset_replacement_returns_a_replaced_definition_back_to_its_original_concrete() {
		$this->sut->add( DependencyClass::class, new DependencyClass(), false );
		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( DependencyClass::class ) );

		$this->sut->replace( DependencyClass::class, DerivedDependencyClass::class );
		$this->assertInstanceOf( DerivedDependencyClass::class, $this->sut->get( DependencyClass::class ) );

		$rederived_instance = new class() extends DerivedDependencyClass {};
		$this->sut->replace( DependencyClass::class, $rederived_instance );
		$this->assertSame( $rederived_instance, $this->sut->get( DependencyClass::class ) );

		$was_reset = $this->sut->reset_replacement( DependencyClass::class );
		$this->assertTrue( $was_reset );
		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testDox 'reset_replacement' returns false if the given class hadn't got a replacement.
	 */
	public function test_reset_replacement_returns_false_if_the_given_class_hadnt_got_a_replacement() {
		$this->assertFalse( $this->sut->reset_replacement( DependencyClass::class ) );
	}

	/**
	 * @testDox 'reset_all_replacements' should revert all the replaced definitions back to their original concretes.
	 */
	public function test_reset_all_replacements_reverts_all_the_replaced_definitions_back_to_their_original_concretes() {
		$this->sut->add( DependencyClass::class );
		$this->sut->add( ClassWithDependencies::class )->addArgument( DependencyClass::class );

		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( ClassWithDependencies::class )->dependency_class );
		$this->assertInstanceOf( ClassWithDependencies::class, $this->sut->get( ClassWithDependencies::class ) );

		$this->sut->replace( DependencyClass::class, DerivedDependencyClass::class );
		$derived_class = new class() extends ClassWithDependencies {};
		$this->sut->replace( ClassWithDependencies::class, $derived_class );

		$this->assertInstanceOf( DerivedDependencyClass::class, $this->sut->get( ClassWithDependencies::class )->dependency_class );
		$this->assertSame( $derived_class, $this->sut->get( ClassWithDependencies::class ) );

		$this->sut->reset_all_replacements();

		$this->assertInstanceOf( DependencyClass::class, $this->sut->get( ClassWithDependencies::class )->dependency_class );
		$this->assertInstanceOf( ClassWithDependencies::class, $this->sut->get( ClassWithDependencies::class ) );
	}

	/**
	 * @testDox 'reset_replacement' treats LegacyProxy as an exception: it reverts is to MockableLegacyProxy.
	 */
	public function test_reset_replacement_resets_legacy_proxy_to_mockable_legacy_proxy() {
		// For this test we need the original container that gets initialized in tests/legacy/bootstrap.php
		// (where LegacyProxy is replaced with MockableLegacyProxy).
		$sut = wc_get_container();

		$some_other_proxy = new class() extends LegacyProxy {};
		$sut->replace( LegacyProxy::class, $some_other_proxy );

		$this->assertSame( $some_other_proxy, $sut->get( LegacyProxy::class ) );

		$sut->reset_replacement( LegacyProxy::class );

		$this->assertInstanceOf( MockableLegacyProxy::class, $sut->get( LegacyProxy::class ) );
	}

	/**
	 * @testDox 'reset_all_replacements' treats LegacyProxy as an exception: it reverts is to MockableLegacyProxy.
	 */
	public function test_reset_all_replacements_resets_legacy_proxy_to_mockable_legacy_proxy() {
		// For this test we need the original container that gets initialized in tests/legacy/bootstrap.php
		// (where LegacyProxy is replaced with MockableLegacyProxy).
		$sut = wc_get_container();

		$some_other_proxy = new class() extends LegacyProxy {};
		$sut->replace( LegacyProxy::class, $some_other_proxy );

		$this->assertSame( $some_other_proxy, $sut->get( LegacyProxy::class ) );

		$sut->reset_all_replacements();

		$this->assertInstanceOf( MockableLegacyProxy::class, $sut->get( LegacyProxy::class ) );
	}
}

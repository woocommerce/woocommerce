<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\RuntimeContainer;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithNestedDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithNoInterface;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClassWithInnerDependency;

/**
 * Tests for TestingContainer.
 */
class TestingContainerTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var TestingContainer
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$base_container = new RuntimeContainer(
			array( 'Foo\Bar' => $this )
		);

		$this->sut = new TestingContainer( $base_container );
	}

	/**
	 * @testdox 'get' can resolve classes passed to the constructor of the base container.
	 */
	public function test_can_get_instance_included_in_initial_resolved_cache() {
		$this->assertSame( $this, $this->sut->get( 'Foo\Bar' ) );
	}

	/**
	 * @testdox 'get' returns an instance of MockableLegacyProxy when LegacyProxy is requested.
	 */
	public function test_get_retrieves_legacy_proxy_as_the_mockable_version() {
		$this->assertInstanceOf( MockableLegacyProxy::class, $this->sut->get( LegacyProxy::class ) );
	}

	/**
	 * @testdox 'replace' allows supplying an object to be returned when a given class is requested with 'get'.
	 */
	public function test_replace_allows_replacing_direct_classes() {
		$mock = new \stdClass();
		$this->sut->replace( DependencyClass::class, $mock );

		$this->assertSame( $mock, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testdox 'replace' continues replacing the appropriate class even after it has been resolved already.
	 */
	public function test_replace_works_with_already_resolved_direct_classes() {
		$this->sut->get( DependencyClass::class );

		$mock = new \stdClass();
		$this->sut->replace( DependencyClass::class, $mock );

		$this->assertSame( $mock, $this->sut->get( DependencyClass::class ) );
	}

	/**
	 * @testdox 'replace' allows supplying an object to be used in place of a dependency in an 'init' method.
	 */
	public function test_replace_allows_replacing_dependencies() {
		$mock = new class() extends DependencyClassWithInnerDependency {};
		$this->sut->replace( DependencyClassWithInnerDependency::class, $mock );

		$this->assertEquals( $mock, $this->sut->get( ClassWithNestedDependencies::class )->dependency_class );
	}

	/**
	 * @testdox 'replace' for dependencies in 'init' methods works as expected for dependencies already resolved only after 'reset_all_resolved'.
	 */
	public function test_replace_works_with_already_resolved_dependencies_if_resolutions_are_reset() {
		$this->sut->get( ClassWithNestedDependencies::class );

		$mock = new class() extends DependencyClassWithInnerDependency {};
		$this->sut->replace( DependencyClassWithInnerDependency::class, $mock );

		$this->assertInstanceOf( DependencyClassWithInnerDependency::class, $this->sut->get( ClassWithNestedDependencies::class )->dependency_class );

		$this->sut->reset_all_resolved();

		$this->assertEquals( $mock, $this->sut->get( ClassWithNestedDependencies::class )->dependency_class );
	}

	/**
	 * @testdox 'reset_replacement' undoes one single replacement made with 'replace'.
	 */
	public function test_reset_replacement() {
		$mock = new \stdClass();
		$this->sut->replace( ClassWithNestedDependencies::class, $mock );
		$this->sut->replace( ClassWithNoInterface::class, $mock );

		$this->assertSame( $mock, $this->sut->get( ClassWithNestedDependencies::class ) );
		$this->assertSame( $mock, $this->sut->get( ClassWithNoInterface::class ) );

		$this->sut->reset_replacement( ClassWithNestedDependencies::class );

		$this->assertInstanceOf( ClassWithNestedDependencies::class, $this->sut->get( ClassWithNestedDependencies::class ) );
		$this->assertSame( $mock, $this->sut->get( ClassWithNoInterface::class ) );
	}

	/**
	 * @testdox 'reset_all_replacements' undoes all the replacements made with 'replace'.
	 */
	public function test_reset_all_replacement() {
		$mock = new \stdClass();
		$this->sut->replace( ClassWithNestedDependencies::class, $mock );
		$this->sut->replace( ClassWithNoInterface::class, $mock );

		$this->assertSame( $mock, $this->sut->get( ClassWithNestedDependencies::class ) );
		$this->assertSame( $mock, $this->sut->get( ClassWithNoInterface::class ) );

		$this->sut->reset_all_replacements();

		$this->assertInstanceOf( ClassWithNestedDependencies::class, $this->sut->get( ClassWithNestedDependencies::class ) );
		$this->assertInstanceOf( ClassWithNoInterface::class, $this->sut->get( ClassWithNoInterface::class ) );
	}

	/**
	 * @testdox 'get' still returns an instance of MockableLegacyProxy when LegacyProxy is requested after resetting all replacements.
	 */
	public function test_get_retrieves_legacy_proxy_as_the_mockable_version_even_after_resetting_replacements() {
		$this->sut->reset_all_replacements();
		$this->assertInstanceOf( MockableLegacyProxy::class, $this->sut->get( LegacyProxy::class ) );
	}

	/**
	 * @testdox 'reset_all_resolved' undoes all class resolutions, effectively reverting the container to its initial state.
	 */
	public function test_reset_all_resolved() {
		ClassWithNestedDependencies::$instances_count        = 0;
		DependencyClassWithInnerDependency::$instances_count = 0;

		$this->sut->get( ClassWithNestedDependencies::class );
		$this->sut->get( ClassWithNestedDependencies::class );
		$this->sut->get( ClassWithNestedDependencies::class );

		$this->assertEquals( 1, ClassWithNestedDependencies::$instances_count );
		$this->assertEquals( 1, DependencyClassWithInnerDependency::$instances_count );

		$this->sut->reset_all_resolved();

		$this->sut->get( ClassWithNestedDependencies::class );
		$this->sut->get( ClassWithNestedDependencies::class );
		$this->sut->get( ClassWithNestedDependencies::class );

		$this->assertEquals( 2, ClassWithNestedDependencies::$instances_count );
		$this->assertEquals( 2, DependencyClassWithInnerDependency::$instances_count );
	}

	/**
	 * @testdox 'reset_all_resolved' handles special cases (gives MockableLegacyProxy instead of LegacyProxy, and keeps the initial resolutions list passed).
	 */
	public function test_reset_all_resolved_handles_special_Cases() {
		$this->sut->reset_all_resolved();
		$this->assertInstanceOf( MockableLegacyProxy::class, $this->sut->get( LegacyProxy::class ) );
		$this->assertSame( $this, $this->sut->get( 'Foo\Bar' ) );
	}
}

<?php

namespace Automattic\WooCommerce\Blocks\Tests\Registry;

use Automattic\WooCommerce\Blocks\Registry\Container as ContainerTest;
use Automattic\WooCommerce\Blocks\Registry\FactoryType;
use Automattic\WooCommerce\Blocks\Tests\Mocks\MockTestDependency;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests the Container functionality
 *
 * This also implicitly tests the FactoryType and SharedType classes.
 *
 * @since $VID:$
 * @group testing
 */
class Container extends TestCase {
	private $container;

	public function setUp() {
		$this->container = new ContainerTest;
	}

	public function test_factory() {
		$factory = $this->container->factory( function () { return 'foo'; } );
		$this->assertInstanceOf( FactoryType::class, $factory );
	}

	public function test_registering_factory_type() {
		$this->container->register(
			MockTestDependency::class,
			$this->container->factory(
				function () { return new MockTestDependency; }
			)
		);
		$instanceA = $this->container->get( MockTestDependency::class );
		$instanceB = $this->container->get( MockTestDependency::class );

		// should not be the same instance;
		$this->assertNotSame( $instanceA, $instanceB );
	}

	public function test_registering_shared_type() {
		$this->container->register(
			MockTestDependency::class,
			function () { return new MockTestDependency; }
		);
		$instanceA = $this->container->get( MockTestDependency::class );
		$instanceB = $this->container->get( MockTestDependency::class );

		// should not be the same instance;
		$this->assertSame( $instanceA, $instanceB );
	}

	public function test_registering_shared_type_dependent_on_another_shared_type() {
		$this->container->register(
			MockTestDependency::class . 'A',
			function() { return new MockTestDependency; }
		);
		$this->container->register(
			MockTestDependency::class . 'B',
			function( $container ) {
				return new MockTestDependency(
					$container->get( MockTestDependency::class . 'A' )
				);
			}
		);
		$instanceA = $this->container->get( MockTestDependency::class . 'A' );
		$instanceB = $this->container->get( MockTestDependency::class . 'B' );

		// should not be the same instance
		$this->assertNotSame( $instanceA, $instanceB );

		// dependency on B should be the same as A
		$this->assertSame( $instanceA, $instanceB->dependency );
	}
}

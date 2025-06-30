<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor;

use Exception;
use PHPUnit\Framework\TestCase;

class Simple_Service {} // phpcs:ignore -- Ignore Only one object structure is allowed in a file.

class Singleton_Service {} // phpcs:ignore -- Ignore Only one object structure is allowed in a file.

/**
 * Unit test for Container class.
 * Ignoring Only one object structure is allowed in a file.
 */
class Container_Test extends TestCase { // phpcs:ignore
	/**
	 * Test if sets and gets service.
	 */
	public function testSetAndGetService(): void {
		$container = new Container();

		$container->set(
			Simple_Service::class,
			function () {
				return new Simple_Service();
			}
		);

		$service = $container->get( Simple_Service::class );

		$this->assertInstanceOf( Simple_Service::class, $service );
	}

	/**
	 * Test if sets and gets service with dependencies.
	 */
	public function testGetReturnsSameInstance(): void {
		$container = new Container();

		$container->set(
			Singleton_Service::class,
			function () {
				return new Singleton_Service();
			}
		);

		// Retrieve the service twice.
		$service1 = $container->get( Singleton_Service::class );
		$service2 = $container->get( Singleton_Service::class );

		// Check that both instances are the same.
		$this->assertSame( $service1, $service2 );
	}

	/**
	 * Test if it throws exception for non-existing service.
	 */
	public function testExceptionForNonExistingService(): void {
		// Create the container instance.
		$container = new Container();

		// Attempt to get a non-existing service should throw an exception.
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Service not found: Automattic\WooCommerce\EmailEditor\Simple_Service' );

		$container->get( Simple_Service::class );
	}
}

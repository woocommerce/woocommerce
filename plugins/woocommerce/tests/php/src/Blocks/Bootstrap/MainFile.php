<?php
/**
 *  Contains Tests for the main file (woocommerce-gutenberg-products-blocks.php)
 *  bootstrap.
 */

namespace Automattic\WooCommerce\Tests\Blocks\Bootstrap;

use \WP_UnitTestCase;
use Automattic\WooCommerce\Blocks\Domain\Bootstrap;
use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Test class for the bootstrap in the plugin main file
 *
 * @since $VID:$
 */
class MainFile extends WP_UnitTestCase {
	/**
	 * Holds an instance of the dependency injection container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Ensure that container is reset between tests.
	 */
	protected function setUp(): void {
		// reset container.
		$this->container = Package::container( true );
	}

	/**
	 * Test that the container is returned from the main file.
	 */
	public function test_container_returns_same_instance() {
		$container = Package::container();
		$this->assertSame( $container, $this->container );
	}

	/**
	 * Test that the container is reset when the reset flag is passed.
	 */
	public function test_container_reset() {
		$container = Package::container( true );
		$this->assertNotSame( $container, $this->container );
	}

	/**
	 *  Asserts that the bootstrap class is returned from the container.
	 */
	public function wc_blocks_bootstrap() {
		$this->assertInstanceOf( Bootstrap::class, wc_blocks_bootstrap() );
	}

	/**
	 * Ensure that the init method is called on the bootstrap class. This is a workaround since we're using an anti-pattern for DI.
	 */
	protected function tearDown(): void {
		Package::init();
	}
}

<?php
/**
 *  Contains Tests for the main file (woocommerce-gutenberg-products-blocks.php)
 *  bootstrap.
 */

namespace Automattic\WooCommerce\Blocks\Tests\Bootstrap;

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
	public function setUp() {
		// reset container
		$this->container = Package::container( true );
	}

	public function test_container_returns_same_instance() {
		$container = Package::container();
		$this->assertSame( $container, $this->container );
	}

	public function test_container_reset() {
		$container = Package::container( true );
		$this->assertNotSame( $container, $this->container );
	}

	public function wc_blocks_bootstrap() {
		$this->assertInstanceOf( Bootstrap::class, wc_blocks_bootstrap() );
	}
}

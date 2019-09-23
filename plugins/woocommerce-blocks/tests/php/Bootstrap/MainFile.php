<?php
/**
 *  Contains Tests for the main file (woocommerce-gutenberg-products-blocks.php)
 *  bootstrap.
 *
 *  @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\Bootstrap;

use \WP_UnitTestCase;
use Automattic\WooCommerce\Blocks\Domain\Bootstrap;
use Automattic\WooCommerce\Blocks\Registry\Container;

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
		$this->container = wc_blocks_container( true );
	}

	public function test_wc_blocks_container_returns_same_instance() {
		$container = wc_blocks_container();
		$this->assertSame( $container, $this->container );
	}

	public function test_wc_blocks_container_reset() {
		$container = wc_blocks_container( true );
		$this->assertNotSame( $container, $this->container );
	}

	public function wc_blocks_bootstrap() {
		$this->assertInstanceOf( Bootstrap::class, wc_blocks_bootstrap() );
	}
}

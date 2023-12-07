<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor;

use WC_Unit_Test_Case;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;

/**
 * Tests for the BlockRegistry class.
 */
class BlockRegistryTest extends WC_Unit_Test_Case {
	/**
	 * Test registering a block type.
	 */
	public function test_register_block_type_from_metadata() {
		$core_registry = \WP_Block_Type_Registry::get_instance();

		$block_registry = BlockRegistry::get_instance();

		$block_registry->register_block_type_from_metadata( trailingslashit( __DIR__ ) . 'test-block' );

		$this->assertTrue( $core_registry->is_registered( 'woocommerce-test/test-block' ), 'Block type not registered.' );
	}
}

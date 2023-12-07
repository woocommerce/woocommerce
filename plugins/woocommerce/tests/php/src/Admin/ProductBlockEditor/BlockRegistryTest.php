<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor;

use WC_Unit_Test_Case;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;

/**
 * Tests for the BlockRegistry class.
 */
class BlockRegistryTest extends WC_Unit_Test_Case {
	/**
	 * Test that generic blocks are registered.
	 */
	public function test_generic_blocks_registered() {
		$block_registry = BlockRegistry::get_instance();

		$this->assertTrue( $block_registry->is_registered( 'woocommerce/conditional' ), 'Conditional component not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-checkbox-field' ), 'Checkbox field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-collapsible' ), 'Collapsible component not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-radio-field' ), 'Radio field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-pricing-field' ), 'Pricing field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-section' ), 'Section component not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-tab' ), 'Tab component not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-toggle-field' ), 'Toggle field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-taxonomy-field' ), 'Taxonomy field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-text-field' ), 'Text field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-number-field' ), 'Number field not registered.' );
	}

	/**
	 * Test that product fields blocks are registered.
	 */
	public function test_product_fields_blocks_registered() {
		$block_registry = BlockRegistry::get_instance();

		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-catalog-visibility-field' ), 'Catalog visibility field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-description-field' ), 'Description field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-downloads-field' ), 'Downloads field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-images-field' ), 'Images field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-inventory-email-field' ), 'Inventory email field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-sku-field' ), 'SKU field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-name-field' ), 'Name field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-regular-price-field' ), 'Regular price not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-schedule-sale-fields' ), 'Schedule sale fields not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-shipping-class-field' ), 'Shipping class field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-shipping-dimensions-fields' ), 'Shipping dimensions not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-summary-field' ), 'Summary field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-tag-field' ), 'Tag field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-inventory-quantity-field' ), 'Inventory quantity field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-variation-items-field' ), 'Variation items field not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-variations-fields' ), 'Variation fields not registered.' );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-password-field', 'Password field not registered.' ) );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-list-field', 'List field not registered.' ) );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-has-variations-notice', 'Has variation notice not registered.' ) );
		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-single-variation-notice', 'Single variation notice not registered.' ) );
	}

	/**
	 * Test registering a block type.
	 */
	public function test_register_block_type_from_metadata() {
		$block_registry = BlockRegistry::get_instance();

		$this->assertFalse( $block_registry->is_registered( 'woocommerce-test/test-block' ), 'Block type already registered.' );

		$block_registry->register_block_type_from_metadata( trailingslashit( __DIR__ ) . 'test-block' );

		$this->assertTrue( $block_registry->is_registered( 'woocommerce-test/test-block' ), 'Block type not registered.' );
	}

	/**
	 * Test unregistering a block type.
	 */
	public function test_unregister() {
		$block_registry = BlockRegistry::get_instance();

		$this->assertTrue( $block_registry->is_registered( 'woocommerce/product-checkbox-field' ), 'Checkbox field not registered.' );

		$block_registry->unregister( 'woocommerce/product-checkbox-field' );

		$this->assertFalse( $block_registry->is_registered( 'woocommerce/product-checkbox-field' ), 'Checkbox field still registered.' );
	}
}

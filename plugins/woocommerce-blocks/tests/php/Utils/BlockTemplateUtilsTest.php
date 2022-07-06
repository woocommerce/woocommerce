<?php

namespace Automattic\WooCommerce\Blocks\Tests\Utils;

use Automattic\WooCommerce\Blocks\Migration;
use Automattic\WooCommerce\Blocks\Options;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

class BlockTemplateUtilsTest extends \WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		delete_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );
		delete_option( Options::WC_BLOCK_VERSION );
	}

	public function test_new_installation_with_a_classic_theme_should_not_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	public function test_new_installation_with_a_block_theme_should_use_blockified_templates() {
		switch_theme( 'twentytwentytwo' );

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	public function test_new_installation_with_a_classic_theme_switching_to_a_block_should_use_blockified_templates() {
		switch_theme( 'storefront' );

		switch_theme( 'twentytwentytwo' );
		check_theme_switched();

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	public function test_plugin_update_with_a_classic_theme_should_not_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->update_plugin();

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	public function test_plugin_update_with_a_block_theme_should_not_use_blockified_templates() {
		switch_theme( 'twentytwentytwo' );

		$this->update_plugin();

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	public function test_plugin_update_with_a_classic_theme_switching_to_a_block_should_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->update_plugin();

		switch_theme( 'twentytwentytwo' );
		check_theme_switched();

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Runs the migration that happen after a plugin update
	 *
	 * @return void
	 */
	public function update_plugin(): void {
		update_option( Options::WC_BLOCK_VERSION, 1 );
		Migration::wc_blocks_update_710_blockified_product_grid_block();
	}
}

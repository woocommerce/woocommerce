<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the CatalogSorting block type
 */
class CatalogSorting extends \WP_UnitTestCase {
	/**
	 * Tests that the Catalog Sorting block has the correct font size based on the default style attribute.
	 */
	public function test_catalog_sorting_has_small_font_size() {
		$temp_product = \WC_Helper_Product::create_simple_product();
		$temp_product->set_name( 'Test Product' );
		$temp_product->save();

		wc_setup_loop();
		wc_set_loop_prop( 'is_paginated', true );
		wc_set_loop_prop( 'total', 1 );
		wc_set_loop_prop( 'per_page', 1 );
		wc_set_loop_prop( 'current_page', 1 );

		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertStringContainsString( 'has-small-font-size', $markup, 'The Catalog Sorting block has the correct font size.' );
	}
}

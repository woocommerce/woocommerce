<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the CatalogSorting block type
 */
class CatalogSorting extends \WP_UnitTestCase {
	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test product and set up loop.
		$temp_product = \WC_Helper_Product::create_simple_product();
		$temp_product->set_name( 'Test Product' );
		$temp_product->save();

		wc_setup_loop();
		wc_set_loop_prop( 'is_paginated', true );
		wc_set_loop_prop( 'total', 1 );
		wc_set_loop_prop( 'per_page', 1 );
		wc_set_loop_prop( 'current_page', 1 );
	}

	/**
	 * Tests that the Catalog Sorting block has the correct font size based on the default style attribute.
	 */
	public function test_catalog_sorting_has_small_font_size() {
		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertStringContainsString( 'has-small-font-size', $markup, 'The Catalog Sorting block has the correct font size.' );
	}

	/**
	 * Tests that Interactivity API directive is added to the form element.
	 */
	public function test_form_has_interactive_directive() {
		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertStringContainsString( 'data-wp-interactive="woocommerce/catalog-sorting"', $markup, 'Form should have data-wp-interactive directive.' );
	}

	/**
	 * Tests that form submit prevention directive is added to the form element.
	 */
	public function test_form_has_submit_prevention_directive() {
		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertStringContainsString( 'data-wp-on--submit="actions.preventSubmit"', $markup, 'Form should have submit prevention directive.' );
	}

	/**
	 * Tests that change handler directive is added to the select element.
	 */
	public function test_select_has_change_handler_directive() {
		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertStringContainsString( 'data-wp-on--change="actions.handleSortChange"', $markup, 'Select should have change handler directive.' );
	}

	/**
	 * Tests that the block renders without errors when no products exist.
	 */
	public function test_renders_empty_when_no_pagination() {
		wc_set_loop_prop( 'is_paginated', false );
		$markup = do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' );
		$this->assertEmpty( trim( $markup ), 'Block should not render when pagination is disabled.' );
	}
}

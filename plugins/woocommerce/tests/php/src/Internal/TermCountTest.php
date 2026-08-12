<?php
/**
 * TermCount tests.
 *
 * @package WooCommerce\Tests\Internal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for TermCount.
 */
class TermCountTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Removing an out-of-stock relationship refreshes the product category count.
	 */
	public function test_removing_out_of_stock_relationship_recounts_product_category(): void {
		$original_setting = get_option( 'woocommerce_hide_out_of_stock_items', false );
		$category         = wp_insert_term( 'TermCount category', 'product_cat' );
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array( 'category_ids' => array( $category['term_id'] ) )
		);

		try {
			update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
			wp_set_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
			wc_recount_all_terms( false );

			$this->assertSame( '0', get_term_meta( $category['term_id'], 'product_count_product_cat', true ) );

			wp_remove_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );

			$this->assertSame( '1', get_term_meta( $category['term_id'], 'product_count_product_cat', true ) );
		} finally {
			$product->delete( true );
			wp_delete_term( $category['term_id'], 'product_cat' );

			if ( false === $original_setting ) {
				delete_option( 'woocommerce_hide_out_of_stock_items' );
			} else {
				update_option( 'woocommerce_hide_out_of_stock_items', $original_setting );
			}
		}
	}
}

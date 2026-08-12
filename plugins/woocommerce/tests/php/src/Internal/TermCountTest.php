<?php
/**
 * TermCount tests.
 *
 * @package WooCommerce\Tests\Internal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;
use WC_Helper_Product;
use WC_Product_External;
use WC_Product_Factory;
use WC_Unit_Test_Case;

/**
 * Tests for TermCount.
 */
class TermCountTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Converting an out-of-stock simple product to an external product refreshes its category count.
	 */
	public function test_converting_out_of_stock_simple_product_to_external_recounts_product_category(): void {
		$original_setting = get_option( 'woocommerce_hide_out_of_stock_items', false );
		$category         = wp_insert_term( 'TermCount category', 'product_cat' );
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $category['term_id'] ),
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		try {
			update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
			wc_recount_all_terms( false );

			$this->assertSame( '0', get_term_meta( $category['term_id'], 'product_count_product_cat', true ) );

			$external_product = new WC_Product_External( $product->get_id() );
			$external_product->save();

			$this->assertSame( ProductType::EXTERNAL, WC_Product_Factory::get_product_type( $product->get_id() ) );
			$this->assertTrue( has_term( ProductStockStatus::OUT_OF_STOCK, 'product_visibility', $product->get_id() ) );

			wp_remove_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );

			$this->assertFalse( has_term( ProductStockStatus::OUT_OF_STOCK, 'product_visibility', $product->get_id() ) );
			$this->assertSame( '1', get_term_meta( $category['term_id'], 'product_count_product_cat', true ) );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $category['term_id'], 'product_cat' );

			if ( false === $original_setting ) {
				delete_option( 'woocommerce_hide_out_of_stock_items' );
			} else {
				update_option( 'woocommerce_hide_out_of_stock_items', $original_setting );
			}
		}
	}
}

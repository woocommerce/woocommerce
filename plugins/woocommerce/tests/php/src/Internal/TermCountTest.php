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
final class TermCountTest extends WC_Unit_Test_Case {
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

			$recount_attempts = $this->count_recount_attempts(
				static function () use ( $product ): void {
					wp_remove_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
				}
			);

			$this->assertSame( 1, $recount_attempts, 'Direct relationship deletion should recount once.' );
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

	/**
	 * @testdox Product visibility changes made through wp_set_object_terms recount product terms once per operation.
	 */
	public function test_set_object_terms_recounts_product_terms_once_per_operation(): void {
		$original_setting = get_option( 'woocommerce_hide_out_of_stock_items', false );
		$category         = wp_insert_term( 'TermCount set terms category', 'product_cat' );
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array( 'category_ids' => array( $category['term_id'] ) )
		);

		try {
			update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
			wc_recount_all_terms( false );

			$recount_attempts = $this->count_recount_attempts(
				static function () use ( $product ): void {
					wp_set_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
				}
			);
			$this->assertSame( 1, $recount_attempts, 'Adding a count-affecting relationship should recount once.' );

			$recount_attempts = $this->count_recount_attempts(
				static function () use ( $product ): void {
					wp_set_object_terms( $product->get_id(), array(), 'product_visibility' );
				}
			);
			$this->assertSame( 1, $recount_attempts, 'Removing a count-affecting relationship should recount once.' );

			$recount_attempts = $this->count_recount_attempts(
				static function () use ( $product ): void {
					wp_add_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
				}
			);
			$this->assertSame( 1, $recount_attempts, 'Appending a count-affecting relationship should recount once.' );

			wp_set_object_terms( $product->get_id(), 'exclude-from-catalog', 'product_visibility' );
			$recount_attempts = $this->count_recount_attempts(
				static function () use ( $product ): void {
					wp_set_object_terms( $product->get_id(), ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
				}
			);
			$this->assertSame( 1, $recount_attempts, 'Replacing count-affecting relationships should recount once.' );
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

	/**
	 * Counts WooCommerce product term recounts performed by an operation.
	 *
	 * @param callable(): void $operation Operation to run.
	 * @return int
	 */
	private function count_recount_attempts( callable $operation ): int {
		$recount_attempts = 0;
		$track_recounts   = static function ( $should_recount ) use ( &$recount_attempts ) {
			++$recount_attempts;

			return $should_recount;
		};
		add_filter( 'woocommerce_product_recount_terms', $track_recounts );

		try {
			$operation();
		} finally {
			remove_filter( 'woocommerce_product_recount_terms', $track_recounts );
		}

		return $recount_attempts;
	}
}

<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\DownloadsWrapper as DownloadsWrapperClass;

/**
 * Test DownloadsWrapper class.
 */
final class DownloadsWrapper extends \WP_UnitTestCase {
	/**
	 * Test `store_has_downloadable_products`: query product meta lookup table.
	 */
	public function test_store_has_downloadable_products_via_product_meta_lookup_table(): void {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};

		$this->assertFalse( $proxy->store_has_downloadable_products_proxy() );
		$product = \WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		$this->assertTrue( $proxy->store_has_downloadable_products_proxy() );

		$product->delete();
	}

	/**
	 * Test `store_has_downloadable_products`: query post meta table.
	 */
	public function test_store_has_downloadable_products_via_posts_meta_table(): void {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};
		add_option( 'woocommerce_product_lookup_table_is_generating', 'yes' );

		$product = \WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		$this->assertTrue( $proxy->store_has_downloadable_products_proxy() );
		$this->assertSame( 'yes', wp_cache_get( 'woocommerce_has_downloadable_products', 'woocommerce' ) );

		delete_option( 'woocommerce_product_lookup_table_is_generating' );
		wp_cache_delete( 'woocommerce_has_downloadable_products', 'woocommerce' );
		$product->delete();
	}

	/**
	 * Test `store_has_downloadable_products`: picking up the cached value.
	 */
	public function test_store_has_downloadable_products_via_cache(): void {
		$proxy = new class() extends DownloadsWrapperClass {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function store_has_downloadable_products_proxy(): bool {
				return $this->store_has_downloadable_products();
			}
		};
		add_option( 'woocommerce_product_lookup_table_is_generating', 'yes' );
		wp_cache_set( 'woocommerce_has_downloadable_products', 'no', 'woocommerce' );

		$product = \WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		$this->assertFalse( $proxy->store_has_downloadable_products_proxy() );

		delete_option( 'woocommerce_product_lookup_table_is_generating' );
		wp_cache_delete( 'woocommerce_has_downloadable_products', 'woocommerce' );
		$product->delete();
	}
}

<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\API\ProductVariations;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_Query;

/**
 * Tests the variation search lookup join.
 */
class ProductVariationsTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Variation search keeps its lookup alias when another filter joins the same table under a different alias.
	 */
	public function test_search_with_differently_aliased_lookup_join(): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		$join    = static function ( $sql ) use ( $wpdb ) {
			return $sql . " LEFT JOIN {$wpdb->wc_product_meta_lookup} ext ON {$wpdb->posts}.ID = ext.product_id ";
		};
		$where   = static function ( $sql ) use ( $wpdb, $product ) {
			return $sql . $wpdb->prepare( ' AND wc_product_meta_lookup.product_id = %d', $product->get_id() );
		};

		add_filter( 'posts_join', $join, 5 );
		add_filter( 'posts_join', array( ProductVariations::class, 'add_wp_query_join' ), 10, 2 );
		add_filter( 'posts_where', $where );

		$query = new WP_Query(
			array(
				'post_type' => 'product',
				'fields'    => 'ids',
				'search'    => 'product',
			)
		);

		$this->assertSame( '', $wpdb->last_error, 'The query should retain a valid lookup alias.' );
		$this->assertSame( array( $product->get_id() ), $query->posts, 'The query should find the product through that alias.' );
		$this->assertStringContainsString( "{$wpdb->wc_product_meta_lookup} wc_product_meta_lookup", $query->request, 'The query needs its own lookup alias.' );
	}
}

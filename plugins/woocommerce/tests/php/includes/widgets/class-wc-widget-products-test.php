<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Widget_Products.
 *
 * @package WooCommerce\Tests\Widgets
 */

/**
 * WC_Widget_Products_Test class.
 */
class WC_Widget_Products_Test extends \WC_Unit_Test_Case {

	/**
	 * Sales ordering is taken from wc_product_meta_lookup rather than from the total_sales post meta.
	 */
	public function test_sales_ordering_reads_total_sales_from_the_lookup_table(): void {
		global $wpdb;

		$ids = array();
		foreach ( array( 30, 20, 10 ) as $total_sales ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_total_sales( $total_sales );
			$product->save();
			$ids[] = $product->get_id();
		}

		// Make the lookup table rank the products the other way round from the post meta.
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 10 ), array( 'product_id' => $ids[0] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 30 ), array( 'product_id' => $ids[2] ) );

		$widget = new WC_Widget_Products();
		$query  = $widget->get_products(
			array(),
			array(
				'number'  => 3,
				'orderby' => 'sales',
				'order'   => 'desc',
			)
		);

		$this->assertSame( array( $ids[2], $ids[1], $ids[0] ), wp_list_pluck( $query->posts, 'ID' ) );
	}
}

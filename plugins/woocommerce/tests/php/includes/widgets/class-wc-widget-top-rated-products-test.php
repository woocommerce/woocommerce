<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Widget_Top_Rated_Products.
 *
 * @package WooCommerce\Tests\Widgets
 */

/**
 * WC_Widget_Top_Rated_Products_Test class.
 */
class WC_Widget_Top_Rated_Products_Test extends \WC_Unit_Test_Case {

	/**
	 * Rating ordering is taken from wc_product_meta_lookup rather than from the _wc_average_rating post meta.
	 */
	public function test_rating_ordering_reads_average_rating_from_the_lookup_table(): void {
		global $wpdb;

		$ids = array();
		foreach ( array( '5.00', '3.00', '1.00' ) as $average_rating ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_average_rating( $average_rating );
			$product->save();
			$ids[] = $product->get_id();
		}

		// Make the lookup table rank the products the other way round from the post meta.
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'average_rating' => '1.00' ), array( 'product_id' => $ids[0] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'average_rating' => '5.00' ), array( 'product_id' => $ids[2] ) );

		$ordered = array();
		$capture = function ( $posts ) use ( &$ordered ) {
			$ordered = wp_list_pluck( $posts, 'ID' );

			// Nothing needs rendering once the ordering has been captured.
			return array();
		};

		add_filter( 'the_posts', $capture );

		ob_start();
		( new WC_Widget_Top_Rated_Products() )->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			),
			array( 'number' => 3 )
		);
		ob_get_clean();

		remove_filter( 'the_posts', $capture );

		$this->assertSame( array( $ids[2], $ids[1], $ids[0] ), $ordered );
	}
}

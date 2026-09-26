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
	 * Query the widget for its products, ordered by sales.
	 *
	 * Every call flushes the object cache first: the lookup table is not part of the post cache, so
	 * WP_Query would otherwise hand back the result ids it cached for an identical earlier query.
	 *
	 * @param string $order 'asc' or 'desc'.
	 *
	 * @return int[] Product ids, in the order the widget returned them.
	 */
	private function sales_ordered_ids( string $order ): array {
		wp_cache_flush();

		$query = ( new WC_Widget_Products() )->get_products(
			array(),
			array(
				'number'  => 3,
				'orderby' => 'sales',
				'order'   => $order,
			)
		);

		return wp_list_pluck( $query->posts, 'ID' );
	}

	/**
	 * Sales ordering comes from wc_product_meta_lookup, breaks ties deterministically, keeps products
	 * that have no lookup row last, and steps aside for a filter that changes the ordering.
	 */
	public function test_sales_ordering_reads_total_sales_from_the_lookup_table(): void {
		global $wpdb;

		$ids = array();
		foreach ( array( 20, 30, 10 ) as $total_sales ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_total_sales( $total_sales );
			$product->save();
			$ids[] = $product->get_id();
		}

		// Rank the products differently in the lookup table from the post meta, so the two sources
		// can be told apart. Post meta ranks them p1, p0, p2; the lookup table ranks them p2, p1, p0.
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 10 ), array( 'product_id' => $ids[0] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 20 ), array( 'product_id' => $ids[1] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 30 ), array( 'product_id' => $ids[2] ) );

		$this->assertSame(
			array( $ids[2], $ids[1], $ids[0] ),
			$this->sales_ordered_ids( 'desc' ),
			'Sales ordering should follow the lookup table, not the post meta.'
		);

		// A filter that suppresses query filters would stop WP_Query running posts_clauses, so the
		// widget has to fall back to the post meta ordering rather than emit an unordered query.
		$suppress_filters = static function ( $args ) {
			$args['suppress_filters'] = true;
			return $args;
		};

		add_filter( 'woocommerce_products_widget_query_args', $suppress_filters );

		try {
			$this->assertSame(
				array( $ids[1], $ids[0], $ids[2] ),
				$this->sales_ordered_ids( 'desc' ),
				'Suppressed filters should fall back to the post meta ordering.'
			);
		} finally {
			remove_filter( 'woocommerce_products_widget_query_args', $suppress_filters );
		}

		// Equal sales are broken by product id, in whichever direction the widget is ordered.
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 50 ), array( 'product_id' => $ids[0] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 50 ), array( 'product_id' => $ids[1] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'total_sales' => 5 ), array( 'product_id' => $ids[2] ) );

		$this->assertSame(
			array( $ids[1], $ids[0], $ids[2] ),
			$this->sales_ordered_ids( 'desc' ),
			'Products on equal sales should be ordered by descending product id.'
		);
		$this->assertSame(
			array( $ids[2], $ids[0], $ids[1] ),
			$this->sales_ordered_ids( 'asc' ),
			'Products on equal sales should be ordered by ascending product id.'
		);

		// A filter that changes the ordering wins; the lookup ordering is not applied on top of it.
		$order_by_id = static function ( $args ) {
			$args['orderby'] = 'ID';
			return $args;
		};

		add_filter( 'woocommerce_products_widget_query_args', $order_by_id );

		try {
			$this->assertSame(
				array( $ids[2], $ids[1], $ids[0] ),
				$this->sales_ordered_ids( 'desc' ),
				'A filter that changes orderby should decide the ordering.'
			);
		} finally {
			remove_filter( 'woocommerce_products_widget_query_args', $order_by_id );
		}

		// A product with no lookup row at all, as happens while the lookup table is being regenerated,
		// sorts last whichever way round the widget is ordered.
		$wpdb->delete( $wpdb->wc_product_meta_lookup, array( 'product_id' => $ids[2] ) );

		$this->assertSame(
			array( $ids[1], $ids[0], $ids[2] ),
			$this->sales_ordered_ids( 'desc' ),
			'A product with no lookup row should sort last descending.'
		);
		$this->assertSame(
			array( $ids[0], $ids[1], $ids[2] ),
			$this->sales_ordered_ids( 'asc' ),
			'A product with no lookup row should sort last ascending.'
		);
	}
}

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
	 * Render the widget and return the ids of the products it listed, in order.
	 *
	 * The ordering is read from the `the_post` action rather than the `the_posts` filter, because a
	 * query that asks for suppressed filters never runs the latter.
	 *
	 * Every call flushes the object cache first: the lookup table is not part of the post cache, so
	 * WP_Query would otherwise hand back the result ids it cached for an identical earlier query.
	 *
	 * @return int[] Product ids, in the order the widget listed them.
	 */
	private function rating_ordered_ids(): array {
		wp_cache_flush();

		$ordered = array();
		$capture = function ( $post ) use ( &$ordered ) {
			$ordered[] = (int) $post->ID;
		};

		add_action( 'the_post', $capture );
		ob_start();

		try {
			( new WC_Widget_Top_Rated_Products() )->widget(
				array(
					'before_widget' => '',
					'after_widget'  => '',
					'before_title'  => '',
					'after_title'   => '',
				),
				array( 'number' => 3 )
			);
		} finally {
			ob_end_clean();
			remove_action( 'the_post', $capture );
		}

		return $ordered;
	}

	/**
	 * Rating ordering comes from wc_product_meta_lookup, breaks ties on rating count then product id,
	 * and steps aside for a filter that changes the ordering.
	 */
	public function test_rating_ordering_reads_average_rating_from_the_lookup_table(): void {
		global $wpdb;

		$ids = array();
		foreach ( array( '3.00', '5.00', '1.00' ) as $average_rating ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_average_rating( $average_rating );
			$product->save();
			$ids[] = $product->get_id();
		}

		// Rank the products differently in the lookup table from the post meta, so the two sources
		// can be told apart. Post meta ranks them p1, p0, p2; the lookup table ranks them p2, p1, p0.
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'average_rating' => '1.00' ), array( 'product_id' => $ids[0] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'average_rating' => '3.00' ), array( 'product_id' => $ids[1] ) );
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'average_rating' => '5.00' ), array( 'product_id' => $ids[2] ) );

		$this->assertSame(
			array( $ids[2], $ids[1], $ids[0] ),
			$this->rating_ordered_ids(),
			'Rating ordering should follow the lookup table, not the post meta.'
		);

		// A filter that suppresses query filters would stop WP_Query running posts_clauses, so the
		// widget has to fall back to the post meta ordering rather than emit an unordered query.
		$suppress_filters = static function ( $args ) {
			$args['suppress_filters'] = true;
			return $args;
		};

		add_filter( 'woocommerce_top_rated_products_widget_args', $suppress_filters );

		try {
			$this->assertSame(
				array( $ids[1], $ids[0], $ids[2] ),
				$this->rating_ordered_ids(),
				'Suppressed filters should fall back to the post meta ordering.'
			);
		} finally {
			remove_filter( 'woocommerce_top_rated_products_widget_args', $suppress_filters );
		}

		// Equally rated products fall back to the rating count, and then to the product id.
		$rating_counts = array(
			$ids[0] => 2,
			$ids[1] => 9,
			$ids[2] => 2,
		);

		foreach ( $rating_counts as $id => $rating_count ) {
			$wpdb->update(
				$wpdb->wc_product_meta_lookup,
				array(
					'average_rating' => '5.00',
					'rating_count'   => $rating_count,
				),
				array( 'product_id' => $id )
			);
		}

		$this->assertSame(
			array( $ids[1], $ids[2], $ids[0] ),
			$this->rating_ordered_ids(),
			'Equally rated products should be ordered by rating count, then by descending product id.'
		);

		// A filter that changes the ordering wins; the lookup ordering is not applied on top of it.
		$order_by_id = static function ( $args ) {
			$args['orderby'] = 'ID';
			return $args;
		};

		add_filter( 'woocommerce_top_rated_products_widget_args', $order_by_id );

		try {
			$this->assertSame(
				array( $ids[2], $ids[1], $ids[0] ),
				$this->rating_ordered_ids(),
				'A filter that changes orderby should decide the ordering.'
			);
		} finally {
			remove_filter( 'woocommerce_top_rated_products_widget_args', $order_by_id );
		}
	}
}

<?php

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Helper class for querying low in stock product counts.
 *
 * Contains the counting and query-building logic extracted from ProductsLowInStock
 * so it can be shared between the REST API endpoint and the admin settings preloader.
 *
 * @since 10.6.0
 */
class LowStockCounter {

	/**
	 * Get the count of low in stock products.
	 *
	 * @param string $status Post status to filter by.
	 *
	 * @return int
	 */
	public static function get_low_stock_count( $status = 'publish' ) {
		$low_stock_threshold           = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
		$sidewide_stock_threshold_only = self::is_using_sitewide_stock_threshold_only( $low_stock_threshold );

		return self::get_count( $sidewide_stock_threshold_only, $status, $low_stock_threshold );
	}

	/**
	 * Check to see if store is using sitewide threshold only. Meaning that it does not have any custom
	 * stock threshold for a product.
	 *
	 * @param int|null $low_stock_threshold Low stock threshold.
	 * @return bool
	 */
	public static function is_using_sitewide_stock_threshold_only( $low_stock_threshold = null ) {
		global $wpdb;
		$query_string = "
			select count(*) as total
			from {$wpdb->postmeta}
			where
			  meta_key='_low_stock_amount'
			  AND meta_value > ''
		";
		$args         = array();
		if ( $low_stock_threshold ) {
			$query_string .= ' AND meta_value != %d';
			$args[]        = $low_stock_threshold;
		}
		// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
		$count = $wpdb->get_var( $wpdb->prepare( $query_string, $args ) );
		return 0 === (int) $count;
	}

	/**
	 * Get the count of low in stock products.
	 *
	 * @param bool   $sidewide_stock_threshold_only Boolean to check if the store is using sitewide stock threshold only.
	 * @param string $status Post status.
	 * @param int    $low_stock_threshold Low stock threshold.
	 *
	 * @return int
	 */
	public static function get_count( $sidewide_stock_threshold_only, $status, $low_stock_threshold ) {
		global $wpdb;
		if ( $sidewide_stock_threshold_only ) {
			$count_query_string  = self::get_count_query( $sidewide_stock_threshold_only );
			$count_query_results = $wpdb->get_results(
				// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
				$wpdb->prepare( $count_query_string, $status, $low_stock_threshold ),
			);

			return (int) $count_query_results[0]->total;
		}

		// Split the query into two queries, one for products with a custom stock threshold and one for products without a custom stock threshold.
		// Splitting the queries also speeds up the query.
		$count_query_with_custom_stock_threshold_string    = self::get_products_with_custom_stock_threshold_count_query_str();
		$count_query_without_custom_stock_threshold_string = self::get_products_without_custom_stock_threshold_count_query_str();
		$count_query_with_custom_stock_threshold_results   = $wpdb->get_results(
			// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
			$wpdb->prepare( $count_query_with_custom_stock_threshold_string, $status ),
		);
		$count_query_without_custom_stock_threshold_results = $wpdb->get_results(
			// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
			$wpdb->prepare( $count_query_without_custom_stock_threshold_string, $status, $low_stock_threshold ),
		);

		return (int) $count_query_with_custom_stock_threshold_results[0]->total + (int) $count_query_without_custom_stock_threshold_results[0]->total;
	}

	/**
	 * Return a query string for low in stock products.
	 *
	 * @param array $replacements  of replacement strings.
	 *
	 * @return string
	 */
	public static function get_base_query( $replacements = array() ) {
		global $wpdb;
		$query = "
			SELECT
				:selects
			FROM
			  {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup
			  LEFT JOIN {$wpdb->posts} wp_posts ON wp_posts.ID = wc_product_meta_lookup.product_id
			  :postmeta_join
			WHERE
			  wp_posts.post_type IN ('product', 'product_variation')
			  AND wp_posts.post_status = %s
			  AND wc_product_meta_lookup.stock_quantity IS NOT NULL
			  AND wc_product_meta_lookup.stock_status IN('instock', 'outofstock')
			  :postmeta_wheres
			  :orderAndLimit
		";

		return strtr( $query, $replacements );
	}

	/**
	 * Add sitewide stock query string to base query string.
	 *
	 * @param string $query Base query string.
	 *
	 * @return string
	 */
	public static function add_sitewide_stock_query_str( $query ) {
		global $wpdb;
		$postmeta = array(
			'select' => 'meta.meta_value AS low_stock_amount,',
			'join'   => "LEFT JOIN {$wpdb->postmeta} AS meta ON wp_posts.ID = meta.post_id
			  AND meta.meta_key = '_low_stock_amount'",
			'wheres' => "AND (
			    (
			      meta.meta_value > ''
			      AND wc_product_meta_lookup.stock_quantity <= CAST(
			        meta.meta_value AS SIGNED
			      )
			    )
			    OR (
			      (
			        meta.meta_value IS NULL
			        OR meta.meta_value <= ''
			      )
			      AND wc_product_meta_lookup.stock_quantity <= %d
			    )
		    )",
		);

		return strtr(
			$query,
			array(
				':postmeta_select' => $postmeta['select'],
				':postmeta_join'   => $postmeta['join'],
				':postmeta_wheres' => $postmeta['wheres'],
			)
		);
	}

	/**
	 * Generate a count query.
	 *
	 * @param bool $sitewide_only generates a query for sitewide low stock threshold only query.
	 *
	 * @return string
	 */
	private static function get_count_query( $sitewide_only = false ) {
		$query = self::get_base_query(
			array(
				':selects'       => 'count(*) as total',
				':orderAndLimit' => '',
			)
		);

		if ( ! $sitewide_only ) {
			return self::add_sitewide_stock_query_str( $query );
		}

		return strtr(
			$query,
			array(
				':postmeta_select' => '',
				':postmeta_join'   => '',
				':postmeta_wheres' => 'AND wc_product_meta_lookup.stock_quantity <= %d',
			)
		);
	}

	/**
	 * Get a query string for products with a custom stock threshold.
	 *
	 * @return string
	 */
	private static function get_products_with_custom_stock_threshold_count_query_str() {
		global $wpdb;
		$query    = self::get_base_query(
			array(
				':selects'       => 'count(*) as total',
				':orderAndLimit' => '',
			)
		);
		$postmeta = array(
			'select' => 'meta.meta_value AS low_stock_amount,',
			'join'   => "JOIN {$wpdb->postmeta} AS meta ON wp_posts.ID = meta.post_id AND meta.meta_key = '_low_stock_amount' AND meta.meta_value > ''",
			'wheres' => 'AND wc_product_meta_lookup.stock_quantity <= CAST(meta.meta_value AS SIGNED)',
		);

		return strtr(
			$query,
			array(
				':postmeta_select' => $postmeta['select'],
				':postmeta_join'   => $postmeta['join'],
				':postmeta_wheres' => $postmeta['wheres'],
			)
		);
	}

	/**
	 * Get a query string for products without a custom stock threshold.
	 *
	 * @return string
	 */
	private static function get_products_without_custom_stock_threshold_count_query_str() {
		global $wpdb;
		$query    = self::get_base_query(
			array(
				':selects'       => 'count(*) as total',
				':orderAndLimit' => '',
			)
		);
		$postmeta = array(
			'select' => 'meta.meta_value AS low_stock_amount,',
			'join'   => "LEFT JOIN {$wpdb->postmeta} AS meta ON wp_posts.ID = meta.post_id AND meta.meta_key = '_low_stock_amount' AND meta.meta_value > ''",
			'wheres' => 'AND meta.post_id IS NULL AND wc_product_meta_lookup.stock_quantity <= %d',
		);

		return strtr(
			$query,
			array(
				':postmeta_select' => $postmeta['select'],
				':postmeta_join'   => $postmeta['join'],
				':postmeta_wheres' => $postmeta['wheres'],
			)
		);
	}
}

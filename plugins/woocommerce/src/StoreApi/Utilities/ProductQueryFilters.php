<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterData;
use Automattic\WooCommerce\StoreApi\Utilities\ProductQuery;
use WP_REST_Request;


/**
 * Product Query filters class.
 */
class ProductQueryFilters {
	private $filter_data;
	private $product_query;

	public function __construct() {
		$this->filter_data   = wc_get_container()->get( FilterData::class );
		$this->product_query = new ProductQuery();

		$this->filter_data->set_filter_clauses_provider( $this->product_query );
	}

	/**
	 * Get filtered min price for current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return object
	 */
	public function get_filtered_price( $request ) {
		// Regenerate the products query without min/max price request params.
		unset( $request['min_price'], $request['max_price'] );

		$query_args = $this->product_query->prepare_objects_query( $request );

		return $this->filter_data->get_filtered_price( $query_args );
	}

	/**
	 * Get stock status counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( $request ) {
		$query_args          = $this->product_query->prepare_objects_query( $request );
		$stock_status_counts = $this->filter_data->get_stock_status_counts( $query_args );

		$hide_outofstock_items = get_option( 'woocommerce_hide_out_of_stock_items' );
		if ( 'yes' === $hide_outofstock_items ) {
			unset( $stock_status_counts['outofstock'] );
		}

		return $stock_status_counts;
	}

	// Below methods aren't used anymore but they're public so we can't just
	// remove them.

	/**
	 * Get rating counts for the current products.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( $request ) {
		// Regenerate the products query without rating request params.
		unset( $request['rating'] );

		$query_args = $this->product_query->prepare_objects_query( $request );

		return $this->filter_data->get_rating_counts( $query_args );
	}

	/**
	 * Get attribute and meta counts.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @param string          $filtered_attribute The attribute to count.
	 *
	 * @return array
	 */
	public function get_attribute_counts( $request, $filtered_attribute ) {
		if ( is_array( $filtered_attribute ) ) {
			wc_deprecated_argument( 'attributes', 'TBD', 'get_attribute_counts does not require an array of attributes as the second parameter anymore. Provide the filtered attribute as a string instead.' );

			$filtered_attribute = ! empty( $filtered_attribute[0] ) ? $filtered_attribute[0] : '';

			if ( empty( $filtered_attribute ) ) {
				return array();
			}
		}

		$query_args = $this->product_query->prepare_objects_query( $request );

		return $this->filter_data->get_attribute_counts( $query_args, $filtered_attribute );
	}

	/**
	 * Get terms list for a given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array
	 */
	public function get_terms_list( string $taxonomy ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id as term_count_id,
            count(DISTINCT product_or_parent_id) as term_count
			FROM {$wpdb->prefix}wc_product_attributes_lookup
			WHERE taxonomy = %s
			GROUP BY term_id",
				$taxonomy
			)
		);
	}

	/**
	 * Get the empty terms list for a given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array
	 */
	public function get_empty_terms_list( string $taxonomy ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT term_id as term_count_id,
            0 as term_count
			FROM {$wpdb->prefix}wc_product_attributes_lookup
			WHERE taxonomy = %s",
				$taxonomy
			)
		);
	}

	/**
	 * Gets product by metas.
	 *
	 * @since TBD
	 * @param array $metas Array of metas to query.
	 * @return array $results
	 */
	public function get_product_by_metas( $metas = array() ) {
		global $wpdb;

		if ( empty( $metas ) ) {
			return array();
		}

		$where   = array();
		$results = array();
		$params  = array();
		foreach ( $metas as $column => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( 'stock_status' === $column ) {
				$stock_product_ids = array();
				foreach ( $value as $stock_status ) {
					$stock_product_ids[] = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT product_id FROM {$wpdb->prefix}wc_product_meta_lookup WHERE stock_status = %s",
							$stock_status
						)
					);
				}

				$where[] = 'product_id IN (' . implode( ',', array_merge( ...$stock_product_ids ) ) . ')';
				continue;
			}

			if ( 'min_price' === $column ) {
				$where[]  = "{$column} >= %d";
				$params[] = intval( $value ) / 100;
				continue;
			}

			if ( 'max_price' === $column ) {
				$where[]  = "{$column} <= %d";
				$params[] = intval( $value ) / 100;
				continue;
			}

			if ( 'average_rating' === $column ) {
				$where_rating = array();
				foreach ( $value as $rating ) {
					$where_rating[] = sprintf( '(average_rating >= %f - 0.5 AND average_rating < %f + 0.5)', $rating, $rating );
				}
				$where[] = '(' . implode( ' OR ', $where_rating ) . ')';
				continue;
			}

			$where[]  = sprintf( "%1s = '%s'", $column, $value );
			$params[] = $value;
		}

		if ( ! empty( $where ) ) {
			$where_clause = implode( ' AND ', $where );
			$where_clause = sprintf( $where_clause, ...$params );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT product_id FROM {$wpdb->prefix}wc_product_meta_lookup WHERE %1s",
					$where_clause
				)
			);
		}
		// phpcs:enable

		return $results;
	}

	/**
	 * Gets product by filtered terms.
	 *
	 * @since TBD
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $term_ids Term IDs.
	 * @param string $query_type or | and.
	 * @return array Product IDs.
	 */
	public function get_product_by_filtered_terms( $taxonomy = '', $term_ids = array(), $query_type = 'or' ) {
		global $wpdb;

		$term_count = count( $term_ids );
		$results    = array();
		$term_ids   = implode( ',', array_map( 'intval', $term_ids ) );

		if ( 'or' === $query_type ) {
			$results = $wpdb->get_col(
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
				$wpdb->prepare(
					"
					SELECT DISTINCT `product_or_parent_id`
					FROM {$wpdb->prefix}wc_product_attributes_lookup
					WHERE `taxonomy` = %s
					AND `term_id` IN (%1s)
					",
					$taxonomy,
					$term_ids
				)
			// phpcs:enable
			);
		}

		if ( 'and' === $query_type ) {
			$results = $wpdb->get_col(
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
				$wpdb->prepare(
					"
					SELECT DISTINCT `product_or_parent_id`
					FROM {$wpdb->prefix}wc_product_attributes_lookup
					WHERE `taxonomy` = %s
					AND `term_id` IN (%1s)
					GROUP BY `product_or_parent_id`
					HAVING COUNT( DISTINCT `term_id` ) >= %d
					",
					$taxonomy,
					$term_ids,
					$term_count
				)
			// phpcs:enable
			);
		}

		return $results;
	}
}

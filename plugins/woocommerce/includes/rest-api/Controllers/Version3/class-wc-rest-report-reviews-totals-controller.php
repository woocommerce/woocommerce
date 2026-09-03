<?php
/**
 * REST API Reports Reviews Totals controller
 *
 * Handles requests to the /reports/reviews/count endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports Reviews Totals controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Reports_Controller
 */
class WC_REST_Report_Reviews_Totals_Controller extends WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/reviews/totals';

	/**
	 * Get reports list.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	protected function get_reports() {
		global $wpdb;

		$counts = array_fill_keys( range( 1, 5 ), 0 );

		// Same cache group and invalidation signal get_comments() used, so ratings written outside WooCommerce still refresh the totals.
		$cache_key = 'wc_report_reviews_totals_' . wp_cache_get_last_changed( 'comment' );
		$cached    = wp_cache_get( $cache_key, 'comment-queries' );

		if ( is_array( $cached ) ) {
			$counts = $cached;
		} else {
			/*
			 * A single grouped aggregate in place of one COUNT query per rating. The clauses below are the
			 * ones WP_Comment_Query::get_comment_ids() would build for the arguments this report used, and
			 * they are passed through comments_clauses so WooCommerce's own callbacks -- the ones hiding
			 * order notes, webhook deliveries and action logs -- and any extension's keep applying.
			 *
			 * Two other comment query hooks cannot apply here. comments_pre_query substitutes a list of
			 * comments or a single count, neither of which describes a per-rating breakdown, and
			 * pre_get_comments runs before any clause exists and lets callbacks set query vars that a
			 * grouped aggregate has no way to honour.
			 */
			$comment_query = new WP_Comment_Query();
			$comment_query->parse_query(
				array(
					'count'     => true,
					'post_type' => 'product',
					'meta_key'  => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Not a query argument here; it tells comments_clauses callbacks which meta the aggregate below reads.
					'status'    => 'all',
				)
			);

			$clauses = array(
				'fields'  => "{$wpdb->commentmeta}.meta_value AS rating, COUNT(*) AS total",
				'join'    => "INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID"
					. " INNER JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id",

				/*
				 * Comments awaiting moderation count, as the 'all' status did; only spam and trashed ones drop
				 * out. The 'note' type is excluded by WP_Comment_Query itself rather than by any filter, so it
				 * is mirrored here. Ratings are compared as strings, as the meta query did, so '0' and unrated
				 * comments fall out.
				 */
				'where'   => "{$wpdb->comments}.comment_approved IN ( '0', '1' )"
					. " AND {$wpdb->comments}.comment_type NOT IN ( 'note' )"
					. " AND {$wpdb->posts}.post_type = 'product'"
					. " AND {$wpdb->commentmeta}.meta_key = 'rating'"
					. " AND {$wpdb->commentmeta}.meta_value IN ( '1', '2', '3', '4', '5' )",
				'orderby' => '',
				'limits'  => '',
				'groupby' => "{$wpdb->commentmeta}.meta_value",
			);

			/** This filter is documented in wp-includes/class-wp-comment-query.php */
			$clauses = apply_filters_ref_array( 'comments_clauses', array( $clauses, &$comment_query ) );

			$fields  = isset( $clauses['fields'] ) ? trim( $clauses['fields'] ) : '';
			$join    = isset( $clauses['join'] ) ? trim( $clauses['join'] ) : '';
			$where   = isset( $clauses['where'] ) ? trim( $clauses['where'] ) : '';
			$groupby = isset( $clauses['groupby'] ) ? trim( $clauses['groupby'] ) : '';

			// A callback that empties any of these would leave either invalid SQL or a total with no rating buckets.
			if ( '' !== $fields && '' !== $join && '' !== $where && '' !== $groupby ) {
				$rows = $wpdb->get_results( "SELECT {$fields} FROM {$wpdb->comments} {$join} WHERE {$where} GROUP BY {$groupby}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Every clause is either built above from literals and $wpdb table names or supplied by a comments_clauses callback, exactly as WP_Comment_Query assembles them.

				foreach ( $rows as $row ) {
					if ( isset( $row->rating, $row->total ) && isset( $counts[ (int) $row->rating ] ) ) {
						$counts[ (int) $row->rating ] = (int) $row->total;
					}
				}

				// Never cache a failed aggregate; a zeroed report would stick until the next comment changed.
				if ( '' === $wpdb->last_error ) {
					wp_cache_set( $cache_key, $counts, 'comment-queries' );
				}
			}
		}

		$data = array();

		for ( $i = 1; $i <= 5; $i++ ) {
			$data[] = array(
				'slug'  => 'rated_' . $i . '_out_of_5',
				/* translators: %s: average rating */
				'name'  => sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $i ),
				'total' => (int) $counts[ $i ],
			);
		}

		return $data;
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param  stdClass        $report Report data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'slug'  => $report->slug,
			'name'  => $report->name,
			'total' => $report->total,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_reviews_count', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_review_total',
			'type'       => 'object',
			'properties' => array(
				'slug'  => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'  => array(
					'description' => __( 'Review type name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total' => array(
					'description' => __( 'Amount of reviews.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}

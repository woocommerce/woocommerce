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

		// Same cache group and invalidation signal get_comments() used, so ratings written outside WooCommerce still refresh the totals.
		$cache_key = 'wc_report_reviews_totals_' . wp_cache_get_last_changed( 'comment' );
		$counts    = wp_cache_get( $cache_key, 'comment-queries' );

		if ( false === $counts ) {
			$counts = array_fill_keys( range( 1, 5 ), 0 );

			/*
			 * A single grouped aggregate in place of one COUNT query per rating. The predicates
			 * repeat what get_comments() built for these reports: comments awaiting moderation are
			 * included and only spam and trashed ones are dropped, the post has to be a product,
			 * and the comment types WooCommerce keeps out of comment queries are excluded. Ratings
			 * are compared as strings, as the meta query did, so '0' and unrated comments fall out.
			 */
			$rows = $wpdb->get_results(
				"SELECT {$wpdb->commentmeta}.meta_value AS rating, COUNT(*) AS total
				FROM {$wpdb->comments}
				INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID
				INNER JOIN {$wpdb->commentmeta} ON {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id
				WHERE {$wpdb->comments}.comment_approved IN ( '0', '1' )
				AND {$wpdb->comments}.comment_type NOT IN ( 'note', 'order_note', 'webhook_delivery', 'action_log' )
				AND {$wpdb->posts}.post_type = 'product'
				AND {$wpdb->commentmeta}.meta_key = 'rating'
				AND {$wpdb->commentmeta}.meta_value IN ( '1', '2', '3', '4', '5' )
				GROUP BY {$wpdb->commentmeta}.meta_value"
			);

			foreach ( $rows as $row ) {
				$counts[ (int) $row->rating ] = (int) $row->total;
			}

			wp_cache_set( $cache_key, $counts, 'comment-queries' );
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

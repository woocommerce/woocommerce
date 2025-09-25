<?php
/**
 * CollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OrderNotes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractCollectionQuery;
use WP_REST_Request;
use WC_Order;

/**
 * CollectionQuery class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
final class CollectionQuery extends AbstractCollectionQuery {
	/**
	 * Get query schema.
	 *
	 * @return array
	 */
	public function get_query_schema(): array {
		return array(
			'note_type' => array(
				'default'           => 'all',
				'description'       => __( 'Limit result to customer notes or private notes.', 'woocommerce' ),
				'type'              => 'string',
				'enum'              => array( 'all', 'customer', 'private' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Prepares query args.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	public function get_query_args( WP_REST_Request $request ): array {
		$args = array(
			'post_id' => $request['order_id'] ?? 0,
			'status'  => 'approve',
			'type'    => 'order_note',
		);

		// Allow filter by order note type.
		if ( 'customer' === $request['note_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'is_customer_note',
					'value'   => 1,
					'compare' => '=',
				),
			);
		} elseif ( 'private' === $request['note_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'is_customer_note',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		return $args;
	}

	/**
	 * Get results of the query.
	 *
	 * @param array           $query_args The query arguments.
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	public function get_query_results( array $query_args, WP_REST_Request $request ): array {
		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );
		$results = get_comments( $query_args );
		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		return (array) $results;
	}
}

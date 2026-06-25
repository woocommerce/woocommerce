<?php
/**
 * CollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractCollectionQuery;
use WC_Order_Query;

/**
 * CollectionQuery class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
class CollectionQuery extends AbstractCollectionQuery {
	/**
	 * Get query schema.
	 *
	 * @return array
	 */
	public function get_query_schema(): array {
		return array(
			'order_id'      => array(
				'description'       => __( 'Filter refunds by order ID.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'          => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page'      => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'         => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'       => array(
				'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'date',
				'enum'              => array(
					'date',
					'id',
					'include',
					'title',
					'slug',
					'modified',
					'total',
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'after'         => array(
				'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before'        => array(
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'dates_are_gmt' => array(
				'description'       => __( 'Whether to consider GMT post dates when limiting response by published or modified date.', 'woocommerce' ),
				'type'              => 'boolean',
				'default'           => false,
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
			'order'          => $request['order'],
			'orderby'        => $request['orderby'],
			'page'           => $request['page'],
			'posts_per_page' => $request['per_page'],
		);

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}

		$date_query = array();
		$use_gmt    = $request['dates_are_gmt'];

		if ( isset( $request['before'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_date_gmt' : 'post_date',
				'before' => $request['before'],
			);
		}

		if ( isset( $request['after'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_date_gmt' : 'post_date',
				'after'  => $request['after'],
			);
		}

		if ( ! empty( $date_query ) ) {
			$date_query['relation'] = 'AND';
			$args['date_query']     = $date_query;
		}

		$order_id = absint( $request['order_id'] ?? 0 );

		if ( $order_id ) {
			$args['post_parent__in'] = array( $order_id );
		}

		return $args;
	}

	/**
	 * Get results of the query.
	 *
	 * @param array           $query_args The query arguments from prepare_query().
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	public function get_query_results( array $query_args, WP_REST_Request $request ): array {
		$query   = new WC_Order_Query(
			array_merge(
				$query_args,
				array(
					'paginate' => true,
				)
			)
		);
		$results = $query->get_orders();

		return array(
			'results' => $results->orders,
			'total'   => $results->total,
			'pages'   => $results->max_num_pages,
		);
	}
}

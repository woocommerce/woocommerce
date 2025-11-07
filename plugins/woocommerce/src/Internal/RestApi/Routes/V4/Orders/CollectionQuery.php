<?php
/**
 * CollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_Http;
use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractCollectionQuery;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order_Query;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

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
			'page'               => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page'           => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'              => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'            => array(
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
			'created_via'        => array(
				'description'       => __( 'Limit result set to orders created via specific sources (e.g. checkout, admin).', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
				),
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'wp_parse_list',
			),
			'customer'           => array(
				'description'       => __( 'Limit result set to orders assigned a specific customer.', 'woocommerce' ),
				'type'              => array( 'string', 'integer' ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'product'            => array(
				'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'             => array(
				'default'           => 'any',
				'description'       => __( 'Limit result set to orders which have specific statuses.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
					'enum' => array_map( OrderUtil::class . '::remove_status_prefix', array_merge( array( 'any', OrderStatus::TRASH ), array_keys( wc_get_order_statuses() ) ) ),
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'             => array(
				'description'       => __( 'Limit results to those matching a string.', 'woocommerce' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'after'              => array(
				'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before'             => array(
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'modified_after'     => array(
				'description'       => __( 'Limit response to resources modified after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'modified_before'    => array(
				'description'       => __( 'Limit response to resources modified before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'dates_are_gmt'      => array(
				'description'       => __( 'Whether to consider GMT post dates when limiting response by published or modified date.', 'woocommerce' ),
				'type'              => 'boolean',
				'default'           => false,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'total'              => array(
				'description'       => __( 'Limit result set to orders with specific total amounts. For between operators, list two values.', 'woocommerce' ),
				'type'              => array( 'string', 'array' ),
				'items'             => array(
					'type' => 'string',
				),
				'sanitize_callback' => 'wp_parse_list',
			),
			'total_operator'     => array(
				'description'       => __( 'The comparison operator to use for total filtering.', 'woocommerce' ),
				'type'              => 'string',
				'enum'              => self::OPERATORS,
				'default'           => self::OPERATOR_IS,
				'validate_callback' => function ( $param, $request, $key ) {
					$valid = rest_validate_request_arg( $param, $request, $key );

					if ( true === $valid && self::OPERATOR_BETWEEN === $param ) {
						$total_field = wp_parse_list( $request->get_param( 'total' ) );

						if ( ! is_array( $total_field ) || count( $total_field ) !== 2 ) {
							return new WP_Error( 'rest_invalid_param', __( 'Total value must be an array with exactly 2 numbers for between operators.', 'woocommerce' ), array( 'status' => WP_Http::BAD_REQUEST ) );
						}
					}

					return $valid;
				},
			),
			'fulfillment_status' => array(
				'description'       => __( 'Limit result set to orders with specific fulfillment statuses.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
					'enum' => array_keys( FulfillmentUtils::get_order_fulfillment_statuses() ),
				),
				'sanitize_callback' => 'wp_parse_list',
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
			's'              => $request['search'],
			'created_via'    => $request['created_via'],
			'status'         => $request['status'],
			'customer'       => $request['customer'],
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

		if ( isset( $request['modified_before'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_modified_gmt' : 'post_modified',
				'before' => $request['modified_before'],
			);
		}

		if ( isset( $request['modified_after'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_modified_gmt' : 'post_modified',
				'after'  => $request['modified_after'],
			);
		}

		if ( ! empty( $date_query ) ) {
			$date_query['relation'] = 'AND';
			$args['date_query']     = $date_query;
		}

		// Search by product.
		if ( ! empty( $request['product'] ) ) {
			global $wpdb;

			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT order_id FROM %i WHERE order_item_id IN ( SELECT order_item_id FROM %i WHERE meta_key = '_product_id' AND meta_value = %d ) AND order_item_type = 'line_item'",
					$wpdb->prefix . 'woocommerce_order_items',
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					$request['product']
				)
			);

			// Force WP_Query to return an empty array of IDs (0) if no matches are found. This forces no results.
			if ( empty( $order_ids ) ) {
				$order_ids = array( 0 );
			} else {
				$include_ids      = $args['post__in'] ?? array();
				$order_ids        = ! empty( $include_ids ) ? array_intersect( $order_ids, $include_ids ) : $order_ids;
				$args['post__in'] = array_merge( $order_ids, array( 0 ) );
			}
		}

		// Search.
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() && ! empty( $args['s'] ) ) {
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );

				$include_ids      = $args['post__in'] ?? array();
				$order_ids        = ! empty( $include_ids ) ? array_intersect( $order_ids, $include_ids ) : $order_ids;
				$args['post__in'] = array_merge( $order_ids, array( 0 ) );
			}
		}

		// Total filtering.
		if ( isset( $request['total'] ) ) {
			// WC_Order-Query uses `total` as the key. DataStores handle the operators.
			$total_param    = (array) $request['total']; // List of total values supports single and between.
			$total_value    = $total_param[0] ?? 0;
			$total_operator = '=';

			// Map rest api operators to the operators `WC_Order_Query` expects. These are the ones defined in the enum.
			switch ( $request['total_operator'] ?? self::OPERATOR_IS ) {
				case self::OPERATOR_IS_NOT:
					$total_operator = '!=';
					break;
				case self::OPERATOR_LESS_THAN:
					$total_operator = '<';
					break;
				case self::OPERATOR_GREATER_THAN:
					$total_operator = '>';
					break;
				case self::OPERATOR_LESS_THAN_OR_EQUAL:
					$total_operator = '<=';
					break;
				case self::OPERATOR_GREATER_THAN_OR_EQUAL:
					$total_operator = '>=';
					break;
				case self::OPERATOR_BETWEEN:
					$total_operator = 'BETWEEN';
					$total_value    = array( $total_param[0] ?? 0, $total_param[1] ?? 0 );
					break;
			}

			$args['total'] = array(
				'value'    => $total_value,
				'operator' => $total_operator,
			);
		}

		// Order fulfillment status filtering.
		if ( isset( $request['fulfillment_status'] ) ) {
			$request['fulfillment_status'] = is_array( $request['fulfillment_status'] ) ? $request['fulfillment_status'] : array( $request['fulfillment_status'] );
			$fulfillment_status            = array();

			foreach ( $request['fulfillment_status'] as $status ) {
				if ( FulfillmentUtils::is_valid_order_fulfillment_status( $status ) ) {
					$fulfillment_status[] = $status;
				}
			}

			$args['fulfillment_status'] = $fulfillment_status;
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

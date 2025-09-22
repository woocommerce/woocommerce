<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * QueryUtils class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order_Query;

/**
 * QueryUtils class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
class QueryUtils {
	/**
	 * Get query schema.
	 *
	 * @return array
	 */
	public function get_query_schema() {
		return array(
			'num_decimals'            => array(
				'default'           => wc_get_price_decimals(),
				'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude_meta'            => array(
				'default'           => array(),
				'description'       => __( 'Ensure meta_data excludes specific keys.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
				),
				'sanitize_callback' => 'wp_parse_list',
			),
			'include_meta'            => array(
				'default'           => array(),
				'description'       => __( 'Limit meta_data to specific keys.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
				),
				'sanitize_callback' => 'wp_parse_list',
			),
			'order_item_display_meta' => array(
				'default'           => false,
				'description'       => __( 'Only show meta which is meant to be displayed for an order.', 'woocommerce' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'page'                    => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page'                => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'offset'                  => array(
				'description'       => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'created_via'             => array(
				'description'       => __( 'Limit result set to orders created via specific sources (e.g. checkout, admin).', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
				),
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'wp_parse_list',
			),
			'customer'                => array(
				'description'       => __( 'Limit result set to orders assigned a specific customer.', 'woocommerce' ),
				'type'              => array( 'string', 'integer' ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'product'                 => array(
				'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'                  => array(
				'default'           => 'any',
				'description'       => __( 'Limit result set to orders which have specific statuses.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
					'enum' => array_map( OrderUtil::class . '::remove_status_prefix', array_merge( array( 'any', OrderStatus::TRASH ), array_keys( wc_get_order_statuses() ) ) ),
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'                   => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'                 => array(
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
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'                  => array(
				'description'       => __( 'Limit results to those matching a string.', 'woocommerce' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude'                 => array(
				'description'       => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'include'                 => array(
				'description'       => __( 'Limit result set to specific ids.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'after'                   => array(
				'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before'                  => array(
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'modified_after'          => array(
				'description'       => __( 'Limit response to resources modified after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'modified_before'         => array(
				'description'       => __( 'Limit response to resources modified before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'dates_are_gmt'           => array(
				'description'       => __( 'Whether to consider GMT post dates when limiting response by published or modified date.', 'woocommerce' ),
				'type'              => 'boolean',
				'default'           => false,
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Prepare the query.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	public function prepare_query( WP_REST_Request $request ): array {
		$args                   = array();
		$args['offset']         = $request['offset'];
		$args['order']          = $request['order'];
		$args['orderby']        = $request['orderby'];
		$args['paged']          = $request['page'];
		$args['post__in']       = $request['include'];
		$args['post__not_in']   = $request['exclude'];
		$args['posts_per_page'] = $request['per_page'];
		$args['name']           = $request['slug'];
		$args['s']              = $request['search'];
		$args['created_via']    = $request['created_via'];
		$args['status']         = $request['status'];
		$args['customer']       = $request['customer'];

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

		return $args;
	}

	/**
	 * Get results of the query.
	 *
	 * @param array  $query_args The query arguments from prepare_query().
	 * @param string $post_type The post type to query.
	 * @return array
	 */
	public function get_query_results( $query_args, $post_type = 'shop_order' ): array {
		$query   = new WC_Order_Query(
			array_merge(
				$query_args,
				array(
					'post_type' => $post_type,
					'paginate'  => true,
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

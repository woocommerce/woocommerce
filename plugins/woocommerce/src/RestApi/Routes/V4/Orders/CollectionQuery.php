<?php
/**
 * CollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_Error;
use Automattic\WooCommerce\RestApi\Routes\V4\AbstractCollectionQuery;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;
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
			'total'                   => array(
				'description'       => __( 'Limit result set to orders with specific total amounts.', 'woocommerce' ),
				'type'              => 'object',
				'properties'        => array(
					'value'    => array(
						'description' => __( 'The value(s) to compare against the order total. Use a single number for most operators, or an array of two numbers for between/not between.', 'woocommerce' ),
						'type'        => array( 'number', 'array' ),
						'required'    => true,
					),
					'operator' => array(
						'description' => __( 'The comparison operator to use.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => self::OPERATORS,
						'default'     => self::OPERATOR_IS,
					),
				),
				'validate_callback' => function ( $param ) {
					return $this->validate_total_field( $param );
				},
				'sanitize_callback' => function ( $param ) {
					return $this->sanitize_total_field( $param );
				},
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
			'offset'         => $request['offset'],
			'order'          => $request['order'],
			'orderby'        => $request['orderby'],
			'paged'          => $request['page'],
			'post__in'       => $request['include'],
			'post__not_in'   => $request['exclude'],
			'posts_per_page' => $request['per_page'],
			'name'           => $request['slug'],
			's'              => $request['search'],
			'created_via'    => $request['created_via'],
			'status'         => $request['status'],
			'customer'       => $request['customer'],
			'total'          => $request['total'],
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

		// Total query.
		if ( isset( $request['total'] ) ) {
			// WC_Order-Query uses `total` as the key. DataStores handle the operators.
			$total_param = $request['total'];

			// Map rest api operators to the operators `WC_Order_Query` expects. These are the ones defined in the enum.
			switch ( $total_param['operator'] ) {
				case self::OPERATOR_IS:
					$total_param['operator'] = '=';
					break;
				case self::OPERATOR_IS_NOT:
					$total_param['operator'] = '!=';
					break;
				case self::OPERATOR_LESS_THAN:
					$total_param['operator'] = '<';
					break;
				case self::OPERATOR_GREATER_THAN:
					$total_param['operator'] = '>';
					break;
				case self::OPERATOR_LESS_THAN_OR_EQUAL:
					$total_param['operator'] = '<=';
					break;
				case self::OPERATOR_GREATER_THAN_OR_EQUAL:
					$total_param['operator'] = '>=';
					break;
				case self::OPERATOR_BETWEEN:
					$total_param['operator'] = 'BETWEEN';
					break;
			}

			$args['total'] = $total_param;
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

	/**
	 * Validate the total value parameter.
	 *
	 * @param mixed $total_param   The value to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_total_field( $total_param ) {
		if ( empty( $total_param ) ) {
			return true; // Optional parameter.
		}

		$value    = $total_param['value'] ?? null;
		$operator = $total_param['operator'] ?? self::OPERATOR_IS;

		if ( is_null( $value ) ) {
			return true;
		}

		if ( ! in_array( $operator, self::OPERATORS, true ) ) {
			// translators: %s is a list of valid operators.
			return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( 'Invalid operator. Valid operators are: %s.', 'woocommerce' ), implode( ', ', self::OPERATORS ) ), array( 'status' => 400 ) );
		}

		// Validate value based on operator.
		if ( in_array( $operator, array( self::OPERATOR_BETWEEN ), true ) ) {
			// For between operators, value must be an array with exactly 2 numbers.
			if ( ! is_array( $value ) || count( $value ) !== 2 ) {
				return new WP_Error( 'rest_invalid_param', __( 'Total value must be an array with exactly 2 numbers for between operators.', 'woocommerce' ), array( 'status' => 400 ) );
			}
			if ( ! is_numeric( $value[0] ) || ! is_numeric( $value[1] ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'Total value array must contain only numbers.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		} elseif ( ! is_numeric( $value ) ) {
			// For other operators, value must be a single number.
			return new WP_Error( 'rest_invalid_param', __( 'Total value must be a number for this operator.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Sanitize the total value parameter.
	 *
	 * @param mixed $total_param The value to sanitize.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_total_field( $total_param ) {
		if ( empty( $total_param ) ) {
			return true; // Optional parameter.
		}

		if ( is_array( $total_param['value'] ) ) {
			// For between operators, sanitize each value in the array.
			foreach ( $total_param['value'] as $key => $val ) {
				$total_param['value'][ $key ] = wc_format_decimal( $val, wc_get_price_decimals() );
			}
		} else {
			// For other operators, sanitize as a single number.
			$total_param['value'] = wc_format_decimal( $total_param['value'], wc_get_price_decimals() );
		}

		return $total_param;
	}
}

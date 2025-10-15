<?php
/**
 * CollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractCollectionQuery;
use WP_REST_Request;
use WP_User_Query;

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
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'woocommerce' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude'  => array(
				'description'       => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'include'  => array(
				'description'       => __( 'Limit result set to specific IDs.', 'woocommerce' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'offset'   => array(
				'description'       => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'    => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'asc',
				'enum'              => array( 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'  => array(
				'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'name',
				'enum'              => array(
					'id',
					'include',
					'name',
					'registered_date',
				),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'email'    => array(
				'description'       => __( 'Limit result set to resources with a specific email.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => function ( $value ) {
					return is_email( $value );
				},
			),
			'role'     => array(
				'description'       => __( 'Limit result set to resources with a specific role.', 'woocommerce' ),
				'type'              => 'string',
				'default'           => 'customer',
				'enum'              => array( 'customer', 'all' ),
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
		$prepared_args            = array();
		$prepared_args['exclude'] = $request['exclude'];
		$prepared_args['include'] = $request['include'];
		$prepared_args['order']   = $request['order'];
		$prepared_args['number']  = $request['per_page'];

		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
		}

		$orderby_possibles        = array(
			'id'              => 'ID',
			'include'         => 'include',
			'name'            => 'display_name',
			'registered_date' => 'registered',
		);
		$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
		$prepared_args['search']  = $request['search'];

		if ( ! empty( $prepared_args['search'] ) ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}

		// Filter by email.
		if ( ! empty( $request['email'] ) ) {
			$prepared_args['search']         = $request['email'];
			$prepared_args['search_columns'] = array( 'user_email' );
		}

		// Filter by role.
		if ( 'all' !== $request['role'] ) {
			$prepared_args['role'] = $request['role'];
		}

		/**
		 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @param array           $prepared_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request       The current request.
		 * @since 10.2.0
		 */
		$prepared_args = apply_filters( 'woocommerce_rest_customer_query', $prepared_args, $request );

		return $prepared_args;
	}

	/**
	 * Get results of the query.
	 *
	 * @param array           $query_args The query arguments.
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	public function get_query_results( array $query_args, WP_REST_Request $request ): array {
		$query = new WP_User_Query( $query_args );

		$users = array();
		foreach ( $query->results as $user ) {
			$users[] = new \WC_Customer( $user->ID );
		}

		// Store pagination values for headers then unset for count query.
		$per_page = (int) $query_args['number'];
		$page     = ceil( ( ( (int) $query_args['offset'] ) / $per_page ) + 1 );

		$query_args['fields'] = 'ID';

		$total_users = $query->get_total();
		if ( $total_users < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['number'] );
			unset( $query_args['offset'] );
			$count_query = new WP_User_Query( $query_args );
			$total_users = $count_query->get_total();
		}

		$max_pages = ceil( $total_users / $per_page );

		return array(
			'results' => $users,
			'total'   => $total_users,
			'pages'   => $max_pages,
		);
	}
}

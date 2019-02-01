<?php
/**
 * REST API Customers Controller
 *
 * Handles requests to /customers/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customers controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Customers_Controller
 */
class WC_Admin_REST_Customers_Controller extends WC_REST_Customers_Controller {

	// @todo Add support for guests here. See https://wp.me/p7bje6-1dM.

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Searches emails by partial search instead of a strict match.
	 * See "search parameters" under https://codex.wordpress.org/Class_Reference/WP_User_Query.
	 *
	 * @param array $prepared_args Prepared search filter args from the customer endpoint.
	 * @param array $request Request/query arguments.
	 * @return array
	 */
	public static function update_search_filters( $prepared_args, $request ) {
		if ( ! empty( $request['email'] ) ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}
		return $prepared_args;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		// Allow partial email matches. Previously, this was of format 'email' which required a strict "test@example.com" format.
		// This, in combination with `update_search_filters` allows us to do partial searches.
		$params['email']['format'] = '';
		return $params;
	}
}

add_filter( 'woocommerce_rest_customer_query', array( 'WC_Admin_REST_Customers_Controller', 'update_search_filters' ), 10, 2 );

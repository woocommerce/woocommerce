<?php
/**
 * WooCommerce API Base class
 *
 * Provides shared functionality for resource-specific API classes
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Base {

	/** @var WC_API_Server the API server */
	protected $server;

	/** @var string sub-classes override this to set a resource-specific base route */
	protected $base;

	/**
	 * Setup class
	 *
	 * @since 2.1
	 * @param WC_API_Server $server
	 * @return WC_API_Base
	 */
	public function __construct( WC_API_Server $server ) {

		$this->server = $server;

		// automatically register routes for sub-classes
		add_filter( 'woocommerce_api_endpoints', array( $this, 'registerRoutes' ) );

		// remove fields from responses when requests specify certain fields
		// note these are hooked at a later priority so data added via filters (e.g. customer data to the order response)
		// still has the fields filtered properly
		add_filter( 'woocommerce_api_order_response',    array( $this, 'filterFields' ), 20, 3 );
		add_filter( 'woocommerce_api_coupon_response',   array( $this, 'filterFields' ), 20, 3 );
		add_filter( 'woocommerce_api_customer_response', array( $this, 'filterFields' ), 20, 3 );
		add_filter( 'woocommerce_api_product_response',  array( $this, 'filterFields' ), 20, 3 );
	}


	/**
	 * Add common request arguments to argument list before WP_Query is run
	 *
	 * @since 2.1
	 * @param array $base_args required arguments for the query (e.g. `post_type`, etc)
	 * @param array $request_args arguments provided in the request
	 * @return array
	 */
	protected function mergeQueryArgs( $base_args, $request_args ) {

		$args = array();

		// TODO: updated_at_min, updated_at_max,s date formatting
		// TODO: WP 3.7 is required to support date args
		if ( ! empty( $request_args['created_at_min'] ) || ! empty( $request_args['created_at_max'] ) ) {

			$args['date_query'] = array(
				array(
					'inclusive' => true,
				)
			);

			if ( ! empty( $request_args['created_at_min'] ) )
				$args['date_query'] = array_merge( $args['date_query'], array( 'after' => $request_args['created_at_min'] ) );

			if ( ! empty( $request_args['created_at_max'] ) )
				$args['date_query'] = array_merge( $args['date_query'], array( 'before' => $request_args['created_at_min'] ) );
		}

		// search
		if ( ! empty( $request_args['q'] ) )
			$args['s'] = $request_args['q'];

		// resources per response
		if ( ! empty( $request_args['limit'] ) )
			$args['posts_per_page'] = $request_args['limit'];

		// resource offset
		if ( ! empty( $request_args['offset'] ) )
			$args['offset'] = $request_args['offset'];

		return array_merge( $base_args, $args );
	}

	/**
	 * Restrict the fields included in the response if the request specified certain only certain fields should be returned
	 *
	 * @TODO this should also work with sub-fields, like billing_address.country
	 *
	 * @since 2.1
	 * @param array $data the response data
	 * @param object $resource the object that provided the response data, e.g. WC_Coupon or WC_Order
	 * @param array|string the requested list of fields to include in the response
	 * @return mixed
	 */
	public function filterFields( $data, $resource, $fields ) {

		if ( empty( $fields ) )
			return $data;

		$fields = explode( ',', $fields );

		foreach ( $data as $data_field => $data_value ) {

			if ( ! in_array( $data_field, $fields ) )
				unset( $data[ $data_field ] );
		}

		return $data;
	}

	/**
	 * Delete a given resource
	 *
	 * @see WP_JSON_Posts::deletePost
	 *
	 * @since 2.1
	 * @param int $id the resource ID
	 * @param string $type the type of resource, either `order`,`coupon`, `product`, or `customer`
	 * @param bool $force true to permanently delete resource, false to move to trash (not supported for `customer`)
	 * @return array|WP_Error
	 */
	protected function deleteResource( $id, $type, $force = false ) {

		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', sprintf( __( 'Invalid %s ID', 'woocommerce' ), $type ), array( 'status' => 404 ) );

		if ( 'customer' === $type ) {

			$result = wp_delete_user( $id );

			if ( $result )
				return array( 'message' => __( 'Permanently deleted customer', 'woocommerce' ) );
			else
				return new WP_Error( 'woocommerce_api_cannot_delete_customer', __( 'The customer cannot be deleted', 'woocommerce' ), array( 'status' => 500 ) );

		} else {

			// delete order/coupon/product

			$post = get_post( $id, ARRAY_A );

			// TODO: check if provided $type is the same as $post['post_type']

			if ( empty( $post['ID'] ) )
				return new WP_Error( 'woocommerce_api_invalid_id', sprintf( __( 'Invalid % ID', 'woocommerce' ), $type ), array( 'status' => 404 ) );

			$post_type = get_post_type_object( $post['post_type'] );

			if ( ! current_user_can( $post_type->cap->delete_post, $id ) )
				return new WP_Error( "woocommerce_api_user_cannot_delete_{$type}", sprintf( __( 'You do not have permission to delete this %s', 'woocommerce' ), $type ), array( 'status' => 401 ) );

			$result = ( $force ) ? wp_delete_post( $id, true ) : wp_trash_post( $id );

			if ( ! $result )
				return new WP_Error( "woocommerce_api_cannot_delete_{$type}", sprintf( __( 'The %s cannot be deleted', 'woocommerce' ), $type ), array( 'status' => 500 ) );

			if ( $force ) {
				return array( 'message' => sprintf( __( 'Permanently deleted %s', 'woocommerce' ), $type ) );

			} else {

				$this->server->send_status( '202' );

				return array( 'message' => sprintf( __( 'Deleted %s', 'woocommerce' ), $type ) );
			}
		}
	}

}

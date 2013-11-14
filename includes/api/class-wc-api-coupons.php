<?php
/**
 * WooCommerce API Coupons Class
 *
 * Handles requests to the /coupons endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WC_API_Coupons extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/coupons';

	/**
	 * Register the routes for this class
	 *
	 * GET|POST /coupons
	 * GET /coupons/count
	 * GET|PUT|DELETE /coupons/<id>
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET|POST /coupons
		$routes[ $this->base ] = array(
			array( array( $this, 'get_coupons' ),     WC_API_Server::READABLE ),
			array( array( $this, 'create_coupon' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /coupons/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_coupons_count' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /coupons/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_coupon' ),  WC_API_Server::READABLE ),
			array( array( $this, 'edit_coupon' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_coupon' ), WC_API_Server::DELETABLE ),
		);

		# GET /coupons/<code> TODO: should looking up coupon codes containing spaces or dashes be supported? OR all-digit coupon codes
		$routes[ $this->base . '/(?P<code>\w+)' ] = array(
			array( array( $this, 'get_coupon_by_code' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all coupons
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param array $filter
	 * @return array
	 */
	public function get_coupons( $fields = null, $filter = array() ) {

		$query = $this->query_coupons( $filter );

		$coupons = array();

		foreach( $query->posts as $coupon_id ) {

			if ( ! $this->is_readable( $coupon_id ) )
				continue;

			$coupons[] = $this->get_coupon( $coupon_id, $fields );
		}

		$this->server->query_navigation_headers( $query );

		return array( 'coupons' => $coupons );
	}

	/**
	 * Get the coupon for the given ID
	 *
	 * @since 2.1
	 * @param int $id the coupon ID
	 * @param string $fields fields to include in response
	 * @return array|WP_Error
	 */
	public function get_coupon( $id, $fields = null ) {
		global $wpdb;

		$id = $this->validate_request( $id, 'shop_coupon', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		// get the coupon code
		$code = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE id = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $id ) );

		if ( is_null( $code ) )
			return new WP_Error( 'woocommerce_api_invalid_coupon_id', __( 'Invalid coupon ID', 'woocommerce' ), array( 'status' => 404 ) );

		$coupon = new WC_Coupon( $code );

		$coupon_data = array(
			'id'                         => $coupon->id,
			'code'                       => $coupon->code,
			'type'                       => $coupon->type,
			'amount'                     => (string) number_format( $coupon->amount, 2 ),
			'individual_use'             => $coupon->individual_use,
			'product_ids'                => $coupon->product_ids,
			'exclude_product_ids'        => $coupon->exclude_product_ids,
			'usage_limit'                => $coupon->usage_limit,
			'usage_limit_per_user'       => $coupon->usage_limit_per_user,
			'limit_usage_to_x_items'     => $coupon->limit_usage_to_x_items,
			'usage_count'                => $coupon->usage_count,
			'expiry_date'                => $coupon->expiry_date,
			'apply_before_tax'           => $coupon->apply_before_tax(),
			'enable_free_shipping'       => $coupon->enable_free_shipping(),
			'product_categories'         => $coupon->product_categories,
			'exclude_product_categories' => $coupon->exclude_product_categories,
			'exclude_sale_items'         => $coupon->exclude_sale_items(),
			'minimum_amount'             => $coupon->minimum_amount,
			'customer_email'             => $coupon->customer_email,
		);

		return apply_filters( 'woocommerce_api_coupon_response', $coupon_data, $coupon, $fields, $this->server );
	}

	/**
	 * Get the total number of coupons
	 *
	 * @since 2.1
	 * @param array $filter
	 * @return array
	 */
	public function get_coupons_count( $filter = array() ) {

		$query = $this->query_coupons( $filter );

		// TODO: permissions?

		return array( 'count' => $query->found_posts );
	}

	/**
	 * Get the coupon for the given code
	 *
	 * @param string $code the coupon code
	 * @param string $fields fields to include in response
	 * @return int|WP_Error
	 */
	public function get_coupon_by_code( $code, $fields = null ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $code ) );

		if ( is_null( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_coupon_code', __( 'Invalid coupon code', 'woocommerce' ), array( 'status' => 404 ) );

		return $this->get_coupon( $id, $fields );
	}

	/**
	 * Create a coupon
	 *
	 * @since 2.1
	 * @param array $data
	 * @return array
	 */
	public function create_coupon( $data ) {

		// TODO: permissions check

		// TODO: implement - what's the minimum set of data required?

		return array();
	}

	/**
	 * Edit a coupon
	 *
	 * @since 2.1
	 * @param int $id the coupon ID
	 * @param array $data
	 * @return array
	 */
	public function edit_coupon( $id, $data ) {

		$id = $this->validate_request( $id, 'shop_coupon', 'edit' );

		if ( is_wp_error( $id ) )
			return $id;

		// TODO: implement
		return $this->get_coupon( $id );
	}

	/**
	 * Delete a coupon
	 *
	 * @since 2.1
	 * @param int $id the coupon ID
	 * @param bool $force true to permanently delete coupon, false to move to trash
	 * @return array
	 */
	public function delete_coupon( $id, $force = false ) {

		$id = $this->validate_request( $id, 'shop_coupon', 'delete' );

		if ( is_wp_error( $id ) )
			return $id;

		return $this->delete( $id, 'shop_coupon', ( 'true' === $force ) );
	}

	/**
	 * Helper method to get coupon post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_coupons( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_coupon',
			'post_status' => 'publish',
		);

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

}

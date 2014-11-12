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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_API_Coupons extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/coupons';

	/**
	 * Register the routes for this class
	 *
	 * GET /coupons
	 * GET /coupons/count
	 * GET /coupons/<id>
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET/POST /coupons
		$routes[ $this->base ] = array(
			array( array( $this, 'get_coupons' ),     WC_API_Server::READABLE ),
			array( array( $this, 'create_coupon' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /coupons/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_coupons_count' ), WC_API_Server::READABLE ),
		);

		# GET/PUT/DELETE /coupons/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_coupon' ),    WC_API_Server::READABLE ),
			array( array( $this, 'edit_coupon' ),   WC_API_SERVER::EDITABLE | WC_API_SERVER::ACCEPT_DATA ),
			array( array( $this, 'delete_coupon' ), WC_API_SERVER::DELETABLE ),
		);

		# GET /coupons/code/<code>, note that coupon codes can contain spaces, dashes and underscores
		$routes[ $this->base . '/code/(?P<code>\w[\w\s\-]*)' ] = array(
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
	 * @param int $page
	 * @return array
	 */
	public function get_coupons( $fields = null, $filter = array(), $page = 1 ) {

		$filter['page'] = $page;

		$query = $this->query_coupons( $filter );

		$coupons = array();

		foreach( $query->posts as $coupon_id ) {

			if ( ! $this->is_readable( $coupon_id ) ) {
				continue;
			}

			$coupons[] = current( $this->get_coupon( $coupon_id, $fields ) );
		}

		$this->server->add_pagination_headers( $query );

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

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		// get the coupon code
		$code = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE id = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $id ) );

		if ( is_null( $code ) ) {
			return new WP_Error( 'woocommerce_api_invalid_coupon_id', __( 'Invalid coupon ID', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$coupon = new WC_Coupon( $code );

		$coupon_post = get_post( $coupon->id );

		$coupon_data = array(
			'id'                           => $coupon->id,
			'code'                         => $coupon->code,
			'type'                         => $coupon->type,
			'created_at'                   => $this->server->format_datetime( $coupon_post->post_date_gmt ),
			'updated_at'                   => $this->server->format_datetime( $coupon_post->post_modified_gmt ),
			'amount'                       => wc_format_decimal( $coupon->amount, 2 ),
			'individual_use'               => ( 'yes' === $coupon->individual_use ),
			'product_ids'                  => array_map( 'absint', (array) $coupon->product_ids ),
			'exclude_product_ids'          => array_map( 'absint', (array) $coupon->exclude_product_ids ),
			'usage_limit'                  => ( ! empty( $coupon->usage_limit ) ) ? $coupon->usage_limit : null,
			'usage_limit_per_user'         => ( ! empty( $coupon->usage_limit_per_user ) ) ? $coupon->usage_limit_per_user : null,
			'limit_usage_to_x_items'       => (int) $coupon->limit_usage_to_x_items,
			'usage_count'                  => (int) $coupon->usage_count,
			'expiry_date'                  => ( ! empty( $coupon->expiry_date ) ) ? $this->server->format_datetime( $coupon->expiry_date ) : null,
			'apply_before_tax'             => $coupon->apply_before_tax(),
			'enable_free_shipping'         => $coupon->enable_free_shipping(),
			'product_category_ids'         => array_map( 'absint', (array) $coupon->product_categories ),
			'exclude_product_category_ids' => array_map( 'absint', (array) $coupon->exclude_product_categories ),
			'exclude_sale_items'           => $coupon->exclude_sale_items(),
			'minimum_amount'               => wc_format_decimal( $coupon->minimum_amount, 2 ),
			'maximum_amount'               => wc_format_decimal( $coupon->maximum_amount, 2 ),
			'customer_emails'              => $coupon->customer_email,
			'description'                  => $coupon_post->post_excerpt,
		);

		return array( 'coupon' => apply_filters( 'woocommerce_api_coupon_response', $coupon_data, $coupon, $fields, $this->server ) );
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

		if ( ! current_user_can( 'read_private_shop_coupons' ) ) {
			return new WP_Error( 'woocommerce_api_user_cannot_read_coupons_count', __( 'You do not have permission to read the coupons count', 'woocommerce' ), array( 'status' => 401 ) );
		}

		return array( 'count' => (int) $query->found_posts );
	}

	/**
	 * Get the coupon for the given code
	 *
	 * @since 2.1
	 * @param string $code the coupon code
	 * @param string $fields fields to include in response
	 * @return int|WP_Error
	 */
	public function get_coupon_by_code( $code, $fields = null ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $code ) );

		if ( is_null( $id ) ) {
			return new WP_Error( 'woocommerce_api_invalid_coupon_code', __( 'Invalid coupon code', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $this->get_coupon( $id, $fields );
	}

	/**
	 * Create a coupon
	 *
	 * @since 2.2
	 * @param array $data
	 * @return array
	 */
	public function create_coupon( $data ) {
		global $wpdb;

		$data = isset( $data['coupon'] ) ? $data['coupon'] : array();

		// Check user permission
		if ( ! current_user_can( 'publish_shop_coupons' ) ) {
			return new WP_Error( 'woocommerce_api_user_cannot_create_coupon', __( 'You do not have permission to create coupons', 'woocommerce' ), array( 'status' => 401 ) );
		}

		$data = apply_filters( 'woocommerce_api_create_coupon_data', $data, $this );

		// Check if coupon code is specified
		if ( ! isset( $data['code'] ) ) {
			return new WP_Error( 'woocommerce_api_missing_coupon_code', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'code' ), array( 'status' => 400 ) );
		}

		$coupon_code = apply_filters( 'woocommerce_coupon_code', $data['code'] );

		// Check for duplicate coupon codes
		$coupon_found = $wpdb->get_var( $wpdb->prepare( "
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = 'shop_coupon'
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->posts.post_title = '%s'
		 ", $coupon_code ) );

		if ( $coupon_found ) {
			return new WP_Error( 'woocommerce_api_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$defaults = array(
			'type'                       => 'fixed_cart',
			'amount'                     => 0,
			'individual_use'             => 'no',
			'product_ids'                => array(),
			'exclude_product_ids'        => array(),
			'usage_limit'                => '',
			'usage_limit_per_user'       => '',
			'limit_usage_to_x_items'     => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'apply_before_tax'           => 'yes',
			'free_shipping'              => 'no',
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => 'no',
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => array(),
		);

		$coupon_data = wp_parse_args( $data, $defaults );

		// Validate coupon types
		if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_coupon_types() ) ) ) {
			return new WP_Error( 'woocommerce_api_invalid_coupon_type', sprintf( __( 'Invalid coupon type - the coupon type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_coupon_types() ) ) ), array( 'status' => 400 ) );
		}

		$new_coupon = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'shop_coupon',
			'post_excerpt' => isset( $data['description'] ) ? $data['description'] : '',
 		);

		$id = wp_insert_post( $new_coupon, $wp_error = false );

		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'woocommerce_api_cannot_create_coupon', $id->get_error_message(), array( 'status' => 400 ) );
		}

		// set coupon meta
		update_post_meta( $id, 'discount_type', $coupon_data['type'] );
		update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
		update_post_meta( $id, 'individual_use', $coupon_data['individual_use'] );
		update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
		update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
		update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
		update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
		update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
		update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );
		update_post_meta( $id, 'expiry_date', wc_clean( $coupon_data['expiry_date'] ) );
		update_post_meta( $id, 'apply_before_tax', wc_clean( $coupon_data['apply_before_tax'] ) );
		update_post_meta( $id, 'free_shipping', wc_clean( $coupon_data['free_shipping'] ) );
		update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_categories'] ) ) );
		update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_categories'] ) ) );
		update_post_meta( $id, 'exclude_sale_items', wc_clean( $coupon_data['exclude_sale_items'] ) );
		update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
		update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
		update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_email'] ) ) );

		do_action( 'woocommerce_api_create_coupon', $id, $data );

		$this->server->send_status( 201 );

		return $this->get_coupon( $id );
	}

	/**
	 * Edit a coupon
	 *
	 * @since 2.2
	 * @param int $id the coupon ID
	 * @param array $data
	 * @return array
	 */
	public function edit_coupon( $id, $data ) {

		$data = isset( $data['coupon'] ) ? $data['coupon'] : array();

		$id = $this->validate_request( $id, 'shop_coupon', 'edit' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$data = apply_filters( 'woocommerce_api_edit_coupon_data', $data, $id, $this );

		if ( isset( $data['code'] ) ) {
			global $wpdb;

			$coupon_code = apply_filters( 'woocommerce_coupon_code', $data['code'] );

			// Check for duplicate coupon codes
			$coupon_found = $wpdb->get_var( $wpdb->prepare( "
				SELECT $wpdb->posts.ID
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type = 'shop_coupon'
				AND $wpdb->posts.post_status = 'publish'
				AND $wpdb->posts.post_title = '%s'
				AND $wpdb->posts.ID != %s
			 ", $coupon_code, $id ) );

			if ( $coupon_found ) {
				return new WP_Error( 'woocommerce_api_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$id = wp_update_post( array( 'ID' => intval( $id ), 'post_title' => $coupon_code, 'post_excerpt' => isset( $data['description'] ) ? $data['description'] : '' ) );
			if ( 0 === $id ) {
				return new WP_Error( 'woocommerce_api_cannot_update_coupon', __( 'Failed to update coupon', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $data['type'] ) ) {
			// Validate coupon types
			if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_coupon_types() ) ) ) {
				return new WP_Error( 'woocommerce_api_invalid_coupon_type', sprintf( __( 'Invalid coupon type - the coupon type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_coupon_types() ) ) ), array( 'status' => 400 ) );
			}
			update_post_meta( $id, 'discount_type', $data['type'] );
		}

		if ( isset( $data['amount'] ) ) {
			update_post_meta( $id, 'coupon_amount', wc_format_decimal( $data['amount'] ) );
		}

		if ( isset( $data['individual_use'] ) ) {
			update_post_meta( $id, 'individual_use', $data['individual_use'] );
		}

		if ( isset( $data['product_ids'] ) ) {
			update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $data['product_ids'] ) ) ) );
		}

		if ( isset( $data['exclude_product_ids'] ) ) {
			update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $data['exclude_product_ids'] ) ) ) );
		}

		if ( isset( $data['usage_limit'] ) ) {
			update_post_meta( $id, 'usage_limit', absint( $data['usage_limit'] ) );
		}

		if ( isset( $data['usage_limit_per_user'] ) ) {
			update_post_meta( $id, 'usage_limit_per_user', absint( $data['usage_limit_per_user'] ) );
		}

		if ( isset( $data['limit_usage_to_x_items'] ) ) {
			update_post_meta( $id, 'limit_usage_to_x_items', absint( $data['limit_usage_to_x_items'] ) );
		}

		if ( isset( $data['usage_count'] ) ) {
			update_post_meta( $id, 'usage_count', absint( $data['usage_count'] ) );
		}

		if ( isset( $data['expiry_date'] ) ) {
			update_post_meta( $id, 'expiry_date', wc_clean( $data['expiry_date'] ) );
		}

		if ( isset( $data['apply_before_tax'] ) ) {
			update_post_meta( $id, 'apply_before_tax', wc_clean( $data['apply_before_tax'] ) );
		}

		if ( isset( $data['enable_free_shipping'] ) ) {
			update_post_meta( $id, 'free_shipping', ( true === $data['enable_free_shipping'] ) ? 'yes' : 'no' );
		}

		if ( isset( $data['product_category_ids'] ) ) {
			update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $data['product_category_ids'] ) ) );
		}

		if ( isset( $data['exclude_product_category_ids'] ) ) {
			update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $data['exclude_product_category_ids'] ) ) );
		}

		if ( isset( $data['exclude_sale_items'] ) ) {
			update_post_meta( $id, 'exclude_sale_items', wc_clean( $data['exclude_sale_items'] ) );
		}

		if ( isset( $data['minimum_amount'] ) ) {
			update_post_meta( $id, 'minimum_amount', wc_format_decimal( $data['minimum_amount'] ) );
		}

		if ( isset( $data['maximum_amount'] ) ) {
			update_post_meta( $id, 'maximum_amount', wc_format_decimal( $data['maximum_amount'] ) );
		}

		if ( isset( $data['customer_emails'] ) ) {
			update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $data['customer_emails'] ) ) );
		}

		do_action( 'woocommerce_api_edit_coupon', $id, $data );

		return $this->get_coupon( $id );
	}

	/**
	 * Delete a coupon
	 *
	 * @since  2.2
	 * @param int $id the coupon ID
	 * @param bool $force true to permanently delete coupon, false to move to trash
	 * @return array
	 */
	public function delete_coupon( $id, $force = false ) {

		$id = $this->validate_request( $id, 'shop_coupon', 'delete' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		do_action( 'woocommerce_api_delete_coupon', $id, $this );

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

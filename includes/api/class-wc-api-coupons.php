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


class WC_API_Coupons extends WC_API_Base {

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
	public function registerRoutes( $routes ) {

		# GET|POST /coupons
		$routes[ $this->base ] = array(
			array( array( $this, 'getCoupons' ),     WC_API_Server::READABLE ),
			array( array( $this, 'createCoupon' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /coupons/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'getCouponsCount' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /coupons/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'getCoupon' ),  WC_API_Server::READABLE ),
			array( array( $this, 'editCoupon' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'deleteCoupon' ), WC_API_Server::DELETABLE ),
		);

		return $routes;
	}

	/**
	 * Get all coupons
	 *
	 * @TODO should we support an extra "code" param for lookup instead of "q" which searches both title & description?
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @param string $q search terms
	 * @param int $limit coupons per response
	 * @param int $offset
	 * @return array
	 */
	public function getCoupons( $fields = null, $created_at_min = null, $created_at_max = null, $q = null, $limit = null, $offset = null ) {

		$request_args = array(
			'created_at_min' => $created_at_min,
			'created_at_max' => $created_at_max,
			'q'              => $q,
			'limit'          => $limit,
			'offset'         => $offset,
		);

		$query = $this->queryCoupons( $request_args );

		$coupons = array();

		foreach( $query->posts as $coupon_id ) {

			$coupons[] = $this->getCoupon( $coupon_id, $fields );
		}

		return array( 'coupons' => $coupons );
	}

	/**
	 * Get the coupon for the given ID
	 *
	 * @since 2.1
	 * @param int $id the coupon ID
	 * @param string $fields fields to include in response
	 * @return array
	 */
	public function getCoupon( $id, $fields = null ) {
		global $wpdb;

		// get the coupon code
		$code = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE id = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $id ) );

		if ( ! $code )
			return new WP_Error( 'wc_api_invalid_coupon_id', __( 'Invalid coupon ID', 'woocommerce' ), array( 'status' => 404 ) );

		$coupon = new WC_Coupon( $code );

		// TODO: how to implement coupon meta?
		$coupon_data = array(
			'id'                         => $coupon->id,
			'code'                       => $coupon->code,
			'type'                       => $coupon->type,
			'amount'                     => $coupon->amount, // TODO: should this be formatted?
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

		return apply_filters( 'woocommerce_api_coupon_response', $coupon_data, $coupon, $fields );
	}

	/**
	 * Get the total number of coupons
	 *
	 * @since 2.1
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @return array
	 */
	public function getCouponsCount( $created_at_min = null, $created_at_max = null ) {

		$query = $this->queryCoupons( array( 'created_at_min' => $created_at_min, 'created_at_max' => $created_at_max ) );

		return array( 'count' => $query->found_posts );
	}

	/**
	 * Create a coupon
	 *
	 * @since 2.1
	 * @param array $data
	 * @return array
	 */
	public function createCoupon( $data ) {

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
	public function editCoupon( $id, $data ) {

		// TODO: implement
		return $this->getCoupon( $id );
	}

	/**
	 * Delete a coupon
	 *
	 * @since 2.1
	 * @param int $id the coupon ID
	 * @param bool $force true to permanently delete coupon, false to move to trash
	 * @return array
	 */
	public function deleteCoupon( $id, $force = false ) {

		return $this->deleteResource( $id, 'coupon', ( 'true' === $force ) );
	}

	/**
	 * Helper method to get coupon post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return array
	 */
	private function queryCoupons( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_coupon',
			'post_status' => 'publish',
			'orderby'     => 'title',
		);

		$query_args = $this->mergeQueryArgs( $query_args, $args );

		// TODO: navigation/total count headers for pagination

		return new WP_Query( $query_args );
	}

}

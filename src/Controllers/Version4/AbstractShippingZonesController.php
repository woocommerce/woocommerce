<?php
/**
 * REST API Shipping Zones Controller base
 *
 * Houses common functionality between Shipping Zones and Locations.
 *
 * @package Automattic/WooCommerce/RestApi
 * @since    3.0.0
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Shipping Zones base class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends AbstractController
 */
abstract class AbstractShippingZonesController extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping/zones';

	/**
	 * Permission to check.
	 *
	 * @var string
	 */
	protected $resource_type = 'settings';

	/**
	 * Retrieve a Shipping Zone by it's ID.
	 *
	 * @param int $zone_id Shipping Zone ID.
	 * @return WC_Shipping_Zone|\WP_Error
	 */
	protected function get_zone( $zone_id ) {
		$zone = \WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );

		if ( false === $zone ) {
			return new \WP_Error( 'woocommerce_rest_shipping_zone_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $zone;
	}

	/**
	 * Check whether a given request has permission to read Shipping Zones.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new \WP_Error( 'rest_no_route', __( 'Shipping is disabled.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create Shipping Zones.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new \WP_Error( 'rest_no_route', __( 'Shipping is disabled.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		return parent::create_item_permissions_check( $request );
	}

	/**
	 * Check whether a given request has permission to edit Shipping Zones.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new \WP_Error( 'rest_no_route', __( 'Shipping is disabled.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		return parent::update_item_permissions_check( $request );
	}

	/**
	 * Check whether a given request has permission to delete Shipping Zones.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new \WP_Error( 'rest_no_route', __( 'Shipping is disabled.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		return parent::delete_item_permissions_check( $request );
	}
}

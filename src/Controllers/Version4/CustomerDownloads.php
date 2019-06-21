<?php
/**
 * REST API Customer Downloads controller
 *
 * Handles requests to the /customers/<customer_id>/downloads endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\Permissions;

/**
 * REST API Customer Downloads controller class.
 */
class CustomerDownloads extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'customers/(?P<customer_id>[\d]+)/downloads';

	/**
	 * Permission to check.
	 *
	 * @var string
	 */
	protected $resource_type = 'customers';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {
		$this->register_items_route( [ 'read' ] );
	}

	/**
	 * Check whether a given request has permission to read customers.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$customer = get_user_by( 'id', (int) $request['customer_id'] );

		if ( ! $customer ) {
			return new \WP_Error( 'woocommerce_rest_customer_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! Permissions::user_can_read( $this->resource_type, $customer->ID ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all customer downloads.
	 *
	 * @param \WP_REST_Request $request Request params.
	 * @return array
	 */
	public function get_items( $request ) {
		$downloads = wc_get_customer_available_downloads( (int) $request['customer_id'] );

		$data = array();
		foreach ( $downloads as $download_data ) {
			$download = $this->prepare_item_for_response( (object) $download_data, $request );
			$download = $this->prepare_response_for_collection( $download );
			$data[]   = $download;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param object           $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		return array(
			'download_id'         => $object->download_id,
			'download_url'        => $object->download_url,
			'product_id'          => $object->product_id,
			'product_name'        => $object->product_name,
			'download_name'       => $object->download_name,
			'order_id'            => $object->order_id,
			'order_key'           => $object->order_key,
			'downloads_remaining' => '' === $object->downloads_remaining ? 'unlimited' : $object->downloads_remaining,
			'access_expires'      => $object->access_expires ? wc_rest_prepare_date_response( $object->access_expires ) : 'never',
			'access_expires_gmt'  => $object->access_expires ? wc_rest_prepare_date_response( get_gmt_from_date( $object->access_expires ) ) : 'never',
			'file'                => $object->file,
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$base  = str_replace( '(?P<customer_id>[\d]+)', $request['customer_id'], $this->rest_base );
		$links = array(
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'product' => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $item->product_id ) ),
			),
			'order' => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $item->order_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Customer Download's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'customer_download',
			'type'       => 'object',
			'properties' => array(
				'download_id'         => array(
					'description' => __( 'Download ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'download_url'        => array(
					'description' => __( 'Download file URL.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'product_id'          => array(
					'description' => __( 'Downloadable product ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'product_name'        => array(
					'description' => __( 'Product name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'download_name'       => array(
					'description' => __( 'Downloadable file name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'order_id'            => array(
					'description' => __( 'Order ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'order_key'           => array(
					'description' => __( 'Order key.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'downloads_remaining' => array(
					'description' => __( 'Number of downloads remaining.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'access_expires'      => array(
					'description' => __( "The date when download access expires, in the site's timezone.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'access_expires_gmt'  => array(
					'description' => __( 'The date when download access expires, as GMT.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'file'                => array(
					'description' => __( 'File details.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'name' => array(
							'description' => __( 'File name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'file' => array(
							'description' => __( 'File URL.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}

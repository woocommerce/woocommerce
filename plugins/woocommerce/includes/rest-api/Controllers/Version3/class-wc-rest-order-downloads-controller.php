<?php
/**
 * REST API Order Downloads controller
 *
 * Handles requests to the /orders/<order_id>/downloads endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Order Downloads controller class.
 *
 * @package Automattic/WooCommerce/RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Order_Downloads_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/downloads';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Register the routes for order downloads.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => array(
					'order_id' => array(
						'description' => __( 'The order ID.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read order downloads.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->get_id() && ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get downloadable items from an order.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$downloads = $order->get_downloadable_items();

		$data = array();
		foreach ( $downloads as $download ) {
			$download = $this->prepare_item_for_response( (object) $download, $request );
			$download = $this->prepare_response_for_collection( $download );
			$data[]   = $download;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a single download output for response.
	 *
	 * @param stdClass        $download Download object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $download, $request ) {
		$data = array(
			'download_id'         => $download->download_id,
			'download_url'        => $download->download_url,
			'product_id'          => $download->product_id,
			'product_name'        => $download->product_name,
			'product_url'         => $download->product_url,
			'download_name'       => $download->download_name,
			'order_id'            => $download->order_id,
			'order_key'           => $download->order_key,
			'downloads_remaining' => '' === $download->downloads_remaining ? 'unlimited' : $download->downloads_remaining,
			'access_expires'      => $download->access_expires ? wc_rest_prepare_date_response( $download->access_expires ) : 'never',
			'access_expires_gmt'  => $download->access_expires ? wc_rest_prepare_date_response( get_gmt_from_date( $download->access_expires ) ) : 'never',
			'file'                => $download->file,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $download, $request ) );

		/**
		 * Filter order download data returned from the REST API.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param stdClass         $download  Download object used to create response.
		 * @param WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_order_download', $response, $download, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param stdClass        $download Download object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given order download.
	 */
	protected function prepare_links( $download, $request ) {
		$order_id = $download->order_id;
		$base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );
		$links    = array(
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'product'    => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $download->product_id ) ),
			),
			'order'      => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $download->order_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Order Download's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order_download',
			'type'       => 'object',
			'properties' => array(
				'download_id'         => array(
					'description' => __( 'Download ID (MD5).', 'woocommerce' ),
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
					'description' => __( 'Downloadable product name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'product_url'         => array(
					'description' => __( 'Downloadable product URL.', 'woocommerce' ),
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

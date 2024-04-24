<?php
/**
 * REST API Refunds controller
 *
 * Handles requests to the /refunds endpoint.
 * Allows for querying refunds directly regardless of associated orders.
 *
 * @package WooCommerce\RestApi
 * @since   9.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Order_Refunds_Controller
 */
class WC_REST_Refunds_Controller extends WC_REST_Order_Refunds_Controller {
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
	protected $rest_base = 'refunds';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order_refund';

	/**
	 * Register the routes for order refunds.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
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
	 * Prepare objects query.
	 *
	 * @since  9.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );
		unset( $args['post_parent__in'] );

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for an order collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @since 9.0.0.
		 */
		$args = apply_filters( 'woocommerce_rest_refunds_prepare_object_query', $args, $request );

		return $args;
	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @since  9.0.0
	 *
	 * @param  WC_Order_Refund $refund  Refund data.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function prepare_object_for_response( $refund, $request ) {
		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );

		$data    = $this->get_formatted_item_data( $refund );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $refund, $request ) );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This filter is documented in includes/rest-api/Controllers/Version2/class-wc-rest-order-refunds-v2-controller.php */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $refund, $request );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Order_Refund $refund  Refund data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $refund, $request ) {
		$base  = str_replace( '(?P<order_id>[\d]+)', $refund->get_parent_id(), 'orders/(?P<order_id>[\d]+)/refunds' );
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $refund->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up'         => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $refund->get_parent_id() ) ),
			),
		);

		return $links;
	}
}

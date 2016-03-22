<?php
/**
 * REST API Order Notes controller
 *
 * Handles requests to the /orders/<order_id>/notes endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Order Notes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Order_Notes_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/notes';

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'note' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read order notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list order notes.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access create order notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a order note.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete a order note.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get order notes from an order.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_items( $request ) {
		$id    = (int) $request['id'];
		$order = get_post( (int) $request['order_id'] );

		if ( empty( $order->post_type ) || 'shop_order' !== $order->post_type ) {
			return new WP_Error( 'woocommerce_rest_order_invalid_id', __( 'Invalid order id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$args = array(
			'post_id' => $order->ID,
			'approve' => 'approve',
			'type'    => 'order_note'
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$data = array();
		foreach ( $notes as $note ) {
			$order_note = $this->prepare_item_for_response( $note, $request );
			$order_note = $this->prepare_response_for_collection( $order_note );
			$data[]     = $order_note;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get a single order note.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id    = (int) $request['id'];
		$order = get_post( (int) $request['order_id'] );

		if ( empty( $order->post_type ) || 'shop_order' !== $order->post_type ) {
			return new WP_Error( 'woocommerce_rest_order_invalid_id', __( 'Invalid order id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$note = get_comment( $id );

		if ( empty( $id ) || empty( $note ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$order_note = $this->prepare_item_for_response( $note, $request );
		$response   = rest_ensure_response( $order_note );

		return $response;
	}

	/**
	 * Prepare a single order note output for response.
	 *
	 * @param WP_Comment $note Order note object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $note, $request ) {
		$data = array(
			'id'            => $note->comment_ID,
			'created_at'    => wc_rest_api_prepare_date_response( $note->comment_date_gmt ),
			'note'          => $note->comment_content,
			'customer_note' => (bool) get_comment_meta( $note->comment_ID, 'is_customer_note', true ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $note ) );

		/**
		 * Filter order note object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Comment       $note     Order note object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_order_note', $response, $note, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Comment $note Delivery order_note object.
	 * @return array Links for the given order note.
	 */
	protected function prepare_links( $note ) {
		$order_id = (int) $note->comment_post_ID;
		$base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );

		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $note->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/wc/v1/orders/%d', $order_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Order Notes schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'tax',
			'type'       => 'order_note',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_at' => array(
					'description' => __( "The date the order note was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'note' => array(
					'description' => __( 'Order note.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_note' => array(
					'description' => __( 'Shows/define if the note is only for reference or for the customer (the user will be notified).', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
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

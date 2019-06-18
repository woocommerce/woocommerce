<?php
/**
 * REST API Order Notes controller
 *
 * Handles requests to the /orders/<order_id>/notes endpoint.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

use WooCommerce\RestApi\Controllers\Version4\Utilities\Permissions;

/**
 * REST API Order Notes controller class.
 */
class OrderNotes extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/notes';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args' => array(
					'order_id'  => array(
						'description' => __( 'The order ID.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
						array(
							'note' => array(
								'type'        => 'string',
								'description' => __( 'Order note content.', 'woocommerce' ),
								'required'    => true,
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
					'order_id'  => array(
						'description' => __( 'The order ID.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);
	}

	/**
	 * Check if a given request has access to read a order note.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		if ( $order && ! Permissions::user_can_read( $this->post_type, $order->get_id() ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete a order note.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		if ( $order && ! Permissions::user_can_delete( $this->post_type, $order->get_id() ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get order notes from an order.
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return array|\WP_Error
	 */
	public function get_items( $request ) {
		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$args = array(
			'post_id' => $order->get_id(),
			'approve' => 'approve',
			'type'    => 'order_note',
		);

		// Allow filter by order note type.
		if ( 'customer' === $request['type'] ) {
			$args['meta_query'] = array( // WPCS: slow query ok.
				array(
					'key'     => 'is_customer_note',
					'value'   => 1,
					'compare' => '=',
				),
			);
		} elseif ( 'internal' === $request['type'] ) {
			$args['meta_query'] = array( // WPCS: slow query ok.
				array(
					'key'     => 'is_customer_note',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10 );

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
	 * Create a single order note.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new \WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'woocommerce_rest_order_invalid_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Create the note.
		$note_id = $order->add_order_note( $request['note'], $request['customer_note'], $request['added_by_user'] );

		if ( ! $note_id ) {
			return new \WP_Error( 'woocommerce_api_cannot_create_order_note', __( 'Cannot create order note, please try again.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$note = get_comment( $note_id );
		$this->update_additional_fields_for_object( $note, $request );

		/**
		 * Fires after a order note is created or updated via the REST API.
		 *
		 * @param \WP_Comment      $note      New order note object.
		 * @param \WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( 'woocommerce_rest_insert_order_note', $note, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, str_replace( '(?P<order_id>[\d]+)', $order->get_id(), $this->rest_base ), $note_id ) ) );

		return $response;
	}

	/**
	 * Get a single order note.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$id    = (int) $request['id'];
		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'woocommerce_rest_order_invalid_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$note = get_comment( $id );

		if ( empty( $id ) || empty( $note ) || intval( $note->comment_post_ID ) !== intval( $order->get_id() ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$order_note = $this->prepare_item_for_response( $note, $request );
		$response   = rest_ensure_response( $order_note );

		return $response;
	}

	/**
	 * Delete a single order note.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id    = (int) $request['id'];
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new \WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Webhooks do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$order = wc_get_order( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'woocommerce_rest_order_invalid_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$note = get_comment( $id );

		if ( empty( $id ) || empty( $note ) || intval( $note->comment_post_ID ) !== intval( $order->get_id() ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$request->set_param( 'context', 'edit' );
		$previous = $this->prepare_item_for_response( $note, $request );
		$result   = wc_delete_order_note( $note->comment_ID );

		if ( ! $result ) {
			return new \WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'woocommerce' ), 'order_note' ), array( 'status' => 500 ) );
		}

		$response = new \WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a order note is deleted or trashed via the REST API.
		 *
		 * @param \WP_Comment       $note     The deleted or trashed order note.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_order_note', $note, $response, $request );

		return $response;
	}

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param \WP_Comment      $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		return array(
			'id'               => (int) $object->comment_ID,
			'author'           => __( 'WooCommerce', 'woocommerce' ) === $object->comment_author ? 'system' : $object->comment_author,
			'date_created'     => wc_rest_prepare_date_response( $object->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $object->comment_date_gmt ),
			'note'             => $object->comment_content,
			'customer_note'    => (bool) get_comment_meta( $object->comment_ID, 'is_customer_note', true ),
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
		$order_id = (int) $item->comment_post_ID;
		$base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );
		$links    = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $item->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $order_id ) ),
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
			'title'      => 'order_note',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'author'           => array(
					'description' => __( 'Order note author.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'     => array(
					'description' => __( "The date the order note was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt' => array(
					'description' => __( 'The date the order note was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'note'             => array(
					'description' => __( 'Order note content.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_note'    => array(
					'description' => __( 'If true, the note will be shown to customers and they will be notified. If false, the note will be for admin reference only.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'added_by_user'    => array(
					'description' => __( 'If true, this note will be attributed to the current user. If false, the note will be attributed to the system.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
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
		$params            = array();
		$params['context'] = $this->get_context_param( array( 'default' => 'view' ) );
		$params['type']    = array(
			'default'           => 'any',
			'description'       => __( 'Limit result to customers or internal notes.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array( 'any', 'customer', 'internal' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}

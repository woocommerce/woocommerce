<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Order Notes controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OrderNotes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
use WP_Http;
use WP_Error;
use WP_Comment;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * OrdersNotes Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'order-notes';

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return OrderNoteSchema::get_item_schema();
	}

	/**
	 * Get the collection args schema.
	 *
	 * @return array
	 */
	protected function get_query_schema(): array {
		return QueryUtils::get_query_schema();
	}

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'order_id' => array(
						'description' => __( 'The order ID that notes belong to.', 'woocommerce' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
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
				),
			)
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed           $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, WP_REST_Request $request ): array {
		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, (int) $item->comment_ID ) ),
			),
			'collection' => array(
				'href' => add_query_arg(
					array( 'order_id' => (int) $item->comment_post_ID ),
					rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) )
				),
			),
		);
	}

	/**
	 * Prepare a single order note item for response.
	 *
	 * @param WP_Comment      $note Note object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $note, WP_REST_Request $request ): array {
		return array(
			'id'               => (int) $note->comment_ID,
			'order_id'         => (int) $note->comment_post_ID,
			'author'           => $note->comment_author,
			'date_created'     => wc_rest_prepare_date_response( $note->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $note->comment_date_gmt ),
			'note'             => $note->comment_content,
			'is_customer_note' => (bool) get_comment_meta( $note->comment_ID, 'is_customer_note', true ),
		);
	}

	/**
	 * Check if a given request has access to the order.
	 *
	 * @param  WC_Order|boolean $order The order object.
	 * @return WP_Error|boolean
	 */
	protected function order_permissions_check( $order ) {
		if ( ! $order || ! $order instanceof WC_Order ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid order ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		if ( ! $this->check_permissions( 'order', 'edit', (int) $order->get_id() ) ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'cannot_edit', __( 'Sorry, you are not allowed to access notes for this order.', 'woocommerce' ), rest_authorization_required_code() );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return $this->order_permissions_check( Utils::get_order_by_note_id( (int) $request['id'] ) );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->order_permissions_check( Utils::get_order_by_id( (int) $request['order_id'] ) );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		return $this->order_permissions_check( Utils::get_order_by_id( (int) $request['order_id'] ) );
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->order_permissions_check( Utils::get_order_by_note_id( (int) $request['id'] ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$note = Utils::get_note_by_id( (int) $request['id'] );

		if ( ! $note ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		return $this->prepare_item_for_response( $note, $request );
	}

	/**
	 * Get collection of orders.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$order = Utils::get_order_by_id( (int) $request['order_id'] );

		if ( ! $order ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid order ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		$results = QueryUtils::get_query_results( $request, $order );
		$items   = array();

		foreach ( $results as $result ) {
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $result, $request ) );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return $this->get_route_error_response( $this->get_error_prefix() . 'exists', __( 'Cannot create existing order note.', 'woocommerce' ), WP_Http::BAD_REQUEST );
		}

		$order   = Utils::get_order_by_id( (int) $request['order_id'] );
		$note_id = $order ? $order->add_order_note( $request['note'], $request['is_customer_note'], true ) : null;

		if ( ! $note_id ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'cannot_create', __( 'Cannot create order note.', 'woocommerce' ), WP_Http::INTERNAL_SERVER_ERROR );
		}

		$note = get_comment( $note_id );
		$this->update_additional_fields_for_object( $note, $request );

		/**
		 * Fires after a single object is created via the REST API.
		 *
		 * @param WP_Comment         $note    Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @since 10.2.0
		 */
		do_action( $this->get_hook_prefix() . 'created', $note, $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );
		$response->set_status( WP_Http::CREATED );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $note_id ) ) );

		return $response;
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$note = Utils::get_note_by_id( (int) $request['id'] );

		if ( empty( $note ) ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );

		$result = wc_delete_order_note( (int) $note->comment_ID );

		if ( ! $result ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'cannot_delete', __( 'This object cannot be deleted.', 'woocommerce' ), WP_Http::INTERNAL_SERVER_ERROR );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param WP_Comment         $note   The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 * @since 10.2.0
		 */
		do_action( $this->get_hook_prefix() . 'deleted', $note, $response, $request );

		return $response;
	}
}

<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Order Notes controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Schema\OrderNoteSchema;
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
	 * Schema class for this route.
	 *
	 * @var OrderNoteSchema
	 */
	protected $item_schema;

	/**
	 * Query utils class.
	 *
	 * @var QueryUtils
	 */
	protected $query_utils;

	/**
	 * Initialize the controller.
	 *
	 * @param OrderNoteSchema $item_schema Order schema class.
	 * @param CollectionQuery $query_utils Query utils class.
	 * @internal
	 */
	final public function init( OrderNoteSchema $item_schema, CollectionQuery $query_utils ) {
		$this->item_schema      = $item_schema;
		$this->collection_query = $query_utils;
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the collection args schema.
	 *
	 * @return array
	 */
	protected function get_query_schema(): array {
		return $this->collection_query->get_query_schema();
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
						'description'       => __( 'The order ID that notes belong to.', 'woocommerce' ),
						'type'              => 'integer',
						'validate_callback' => function ( $value ) {
							return $this->is_valid_order_id( $value );
						},
						'required'          => true,
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
	 * @param mixed            $item WordPress representation of the item.
	 * @param WP_REST_Request  $request Request object.
	 * @param WP_REST_Response $response Response object.
	 * @return array
	 */
	protected function prepare_links( $item, WP_REST_Request $request, WP_REST_Response $response ): array {
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
		return $this->item_schema->get_item_response( $note, $request );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$order = $this->get_order_by_note_id( (int) $request['id'] );

		if ( ! $order ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		if ( ! wc_rest_check_post_permissions( 'shop_order', 'read', $order->get_id() ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'shop_order', 'read', (int) $request['order_id'] ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'shop_order', 'create', (int) $request['order_id'] ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$order = $this->get_order_by_note_id( (int) $request['id'] );

		if ( ! $order ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		if ( ! wc_rest_check_post_permissions( 'shop_order', 'delete', $order->get_id() ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$note = $this->get_note_by_id( (int) $request['id'] );

		if ( ! $note ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
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
		$order = $this->get_order_by_id( (int) $request['order_id'] );

		if ( ! $order ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$query_args = $this->collection_query->get_query_args( $request );
		$results    = $this->collection_query->get_query_results( $query_args, $request );
		$items      = array();

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
			return $this->get_route_error_by_code( self::RESOURCE_EXISTS );
		}

		$order   = $this->get_order_by_id( (int) $request['order_id'] );
		$note_id = $order ? $order->add_order_note( $request['note'], $request['is_customer_note'], true ) : null;

		if ( ! $note_id ) {
			return $this->get_route_error_by_code( self::CANNOT_CREATE );
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
		$note = $this->get_note_by_id( (int) $request['id'] );

		if ( empty( $note ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );

		$result = wc_delete_order_note( (int) $note->comment_ID );

		if ( ! $result ) {
			return $this->get_route_error_by_code( self::CANNOT_DELETE );
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

	/**
	 * Check if an order is valid.
	 *
	 * @param mixed $order_id The order ID.
	 * @return bool True if the order is valid, false otherwise.
	 */
	protected function is_valid_order_id( $order_id ): bool {
		$order = $this->get_order_by_id( (int) $order_id );
		return $order && $order instanceof WC_Order;
	}

	/**
	 * Get an order by ID.
	 *
	 * @param int $order_id The order ID.
	 * @return WC_Order|null
	 */
	protected function get_order_by_id( int $order_id ) {
		if ( ! $order_id ) {
			return null;
		}
		$order = wc_get_order( $order_id );
		return $order && 'shop_order' === $order->get_type() ? $order : null;
	}
	/**
	 * Get the parent order of a note.
	 *
	 * @param int|WP_Comment $note_id The note ID or note object.
	 * @return WC_Order|null
	 */
	protected function get_order_by_note_id( $note_id ) {
		$note = $note_id instanceof WP_Comment ? $note_id : $this->get_note_by_id( (int) $note_id );
		if ( ! $note ) {
			return null;
		}
		return $this->get_order_by_id( (int) $note->comment_post_ID );
	}

	/**
	 * Get a note by ID.
	 *
	 * @param int $note_id The note ID.
	 * @return WP_Comment|null
	 */
	protected function get_note_by_id( int $note_id ) {
		if ( ! $note_id ) {
			return null;
		}
		$note = get_comment( $note_id );
		return $note && 'order_note' === $note->comment_type ? $note : null;
	}
}

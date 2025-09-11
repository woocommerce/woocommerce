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
use Automattic\WooCommerce\RestApi\Schemas\V4\OrderNoteSchema;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\Internal\Utilities\Users;
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
	 * Post type used for permissions checks.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->schema_controller = new OrderNoteSchema();
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
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = array(
			'context'   => $this->get_context_param( array( 'default' => 'view' ) ),
			'note_type' => array(
				'default'           => 'all',
				'description'       => __( 'Limit result to customer notes or private notes.', 'woocommerce' ),
				'type'              => 'string',
				'enum'              => array( 'all', 'customer', 'private' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		/**
		 * Filter the collection params for the orders controller.
		 *
		 * @param array $params The collection params.
		 * @since 10.2.0
		 */
		return apply_filters( $this->get_hook_prefix() . 'collection_params', $params, $this );
	}

	/**
	 * Add links to the response.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WP_Comment       $note   Note object (wp comment).
	 * @param WP_REST_Request  $request Request object.
	 * @return WP_REST_Response
	 */
	protected function add_links( WP_REST_Response $response, WP_Comment $note, WP_REST_Request $request ) {
		$order_id = (int) $note->comment_post_ID;

		$response->add_link( 'self', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, (int) $note->comment_ID ) ) );
		$response->add_link( 'collection', rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );
		$response->add_link( 'order', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order_id ) ) );

		return $response;
	}

	/**
	 * Prepare a single order note item for response.
	 *
	 * @param WP_Comment      $note Note object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $note, $request ) {
		$data     = array(
			'id'               => (int) $note->comment_ID,
			'order_id'         => (int) $note->comment_post_ID,
			'author'           => __( 'woocommerce', 'woocommerce' ) === $note->comment_author ? 'system' : $note->comment_author,
			'date_created'     => wc_rest_prepare_date_response( $note->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $note->comment_date_gmt ),
			'note'             => $note->comment_content,
			'is_customer_note' => (bool) get_comment_meta( $note->comment_ID, 'is_customer_note', true ),
		);
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $request['context'] ?? 'view' );
		$response = $this->add_links( rest_ensure_response( $data ), $note, $request );

		/**
		 * Filter the data for a response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Comment          $order   Order object.
		 * @param WP_REST_Request  $request  Request object.
		 * @since 10.2.0
		 */
		return rest_ensure_response( apply_filters( $this->get_hook_prefix() . 'item_response', $response, $note, $request ) );
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
		return $note ? $note : null;
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
		return $order && $this->post_type === $order->get_type() ? $order : null;
	}
	/**
	 * Get the parent order of a note.
	 *
	 * @param int|WP_Comment $note The note ID or note object.
	 * @return WC_Order|null
	 */
	protected function get_order_by_note( $note ) {
		$note = $note instanceof WP_Comment ? $note : $this->get_note_by_id( (int) $note );
		if ( ! $note ) {
			return null;
		}
		return $this->get_order_by_id( (int) $note->comment_post_ID );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$order = $this->get_order_by_note( (int) $request['id'] );

		if ( $order && ! wc_rest_check_post_permissions( $this->post_type, 'read', $order->get_id() ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), rest_authorization_required_code() );
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
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		return $this->prepare_item_for_response( $note, $request );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', (int) $request['order_id'] ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
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
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid order ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		$args = array(
			'post_id' => $order->get_id(),
			'approve' => 'approve',
			'type'    => 'order_note',
		);

		// Allow filter by order note type.
		if ( 'customer' === $request['note_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'is_customer_note',
					'value'   => 1,
					'compare' => '=',
				),
			);
		} elseif ( 'private' === $request['note_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'is_customer_note',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );
		$notes = get_comments( $args );
		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$items = array();

		foreach ( $notes as $note ) {
			$item    = $this->prepare_item_for_response( $note, $request );
			$items[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$order = $this->get_order_by_id( (int) $request['order_id'] );

		if ( ! $order ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'invalid_id', __( 'Invalid order ID.', 'woocommerce' ), WP_Http::NOT_FOUND );
		}

		if ( ! wc_rest_check_post_permissions( $this->post_type, 'edit', $order->get_id() ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), rest_authorization_required_code() );
		}

		return true;
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

		$order   = $this->get_order_by_id( (int) $request['order_id'] );
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
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$order = $this->get_order_by_note( (int) $request['id'] );

		if ( $order && ! wc_rest_check_post_permissions( $this->post_type, 'delete', $order->get_id() ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_delete', __( 'Sorry, you cannot delete this resource.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
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

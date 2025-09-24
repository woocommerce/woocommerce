<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Orders controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\RestApi\Routes\V4\Orders\Schema\OrderSchema;
use WP_Http;
use WP_Error;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Orders Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders';

	/**
	 * Post type used for orders.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Schema class for this route.
	 *
	 * @var OrderSchema
	 */
	protected $item_schema;

	/**
	 * Query utils class.
	 *
	 * @var QueryUtils
	 */
	protected $query_utils;

	/**
	 * Update utils class.
	 *
	 * @var UpdateUtils
	 */
	protected $update_utils;

	/**
	 * Initialize the controller.
	 *
	 * @param OrderSchema     $item_schema Order schema class.
	 * @param CollectionQuery $query_utils Query utils class.
	 * @param UpdateUtils     $update_utils Update utils class.
	 * @internal
	 */
	final public function init( OrderSchema $item_schema, CollectionQuery $query_utils, UpdateUtils $update_utils ) {
		$this->item_schema      = $item_schema;
		$this->collection_query = $query_utils;
		$this->update_utils     = $update_utils;
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
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
						array(
							'set_paid' => array(
								'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
								'type'        => 'boolean',
								'default'     => false,
							),
						)
					),
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
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
						array(
							'set_paid' => array(
								'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
								'type'        => 'boolean',
								'default'     => false,
							),
						)
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						),
					),
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
		$links = array(
			'self'            => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $item->get_id() ) ),
			),
			'collection'      => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'email-templates' => array(
				'href'       => rest_url( sprintf( '/wc/v3/%s/%d/actions/email_templates', $this->rest_base, $item->get_id() ) ),
				'embeddable' => true,
			),
			'order-notes'     => array(
				'href'       => rest_url( sprintf( '/%s/order-notes?order_id=%d', $this->namespace, $item->get_id() ) ),
				'embeddable' => true,
			),
		);

		if ( $item->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $item->get_customer_id() ) ),
			);
		}

		if ( $item->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $item->get_parent_id() ) ),
			);
		}

		return $links;
	}

	/**
	 * Prepare a single order object for response.
	 *
	 * @param WC_Order        $order Order object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $order, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $order, $request, $this->get_fields_for_response( $request ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $this->is_valid_order_for_request( $order ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return $this->prepare_item_for_response( $order, $request );
	}

	/**
	 * Get collection of orders.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args = $this->collection_query->get_query_args( $request );
		$results    = $this->collection_query->get_query_results( array_merge( $query_args, array( 'post_type' => $this->post_type ) ), $request );
		$items      = array();

		foreach ( $results['results'] as $result ) {
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $result, $request ) );
		}

		$pagination_util = new Pagination();
		$response        = $pagination_util->add_headers( rest_ensure_response( $items ), $request, $results['total'], $results['pages'] );

		return $response;
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
			return $this->get_route_error_by_code( self::RESOURCE_EXISTS );
		}

		try {
			$order = new WC_Order();
			$order->set_created_via( ! empty( $request['created_via'] ) ? sanitize_text_field( wp_unslash( $request['created_via'] ) ) : 'rest-api' );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

			$this->update_utils->update_order_from_request( $order, $request, true );
			$this->update_additional_fields_for_object( $order, $request );

			/**
			 * Fires after a single object is created via the REST API.
			 *
			 * @param WC_Order         $order    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @since 10.2.0
			 */
			do_action( $this->get_hook_prefix() . 'created', $order, $request );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $order, $request );
			$response->set_status( WP_Http::CREATED );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order->get_id() ) ) );

			return $response;
		} catch ( \WC_Data_Exception $e ) {
			$data = $e->getErrorData();

			if ( $order && $order instanceof WC_Order && $order->get_id() ) {
				try {
					$order->set_status( 'checkout-draft' );
					$order->save();
					// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( \Exception $_ ) {
					// We don't want a failure in changing the order status
					// to throw on itself, but we don't have anything meaningful
					// to do with this failure either.
				}
				$data['new_draft_order_id'] = $order->get_id();
			}

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $data );
		} catch ( \WC_REST_Exception $e ) {
			if ( $order && $order instanceof WC_Order && $order->get_id() ) {
				$order->delete( true );
			}
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $this->is_valid_order_for_request( $order ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		try {
			$this->update_utils->update_order_from_request( $order, $request, false );
			$this->update_additional_fields_for_object( $order, $request );

			/**
			 * Fires after a single object is updated via the REST API.
			 *
			 * @param WC_Data         $order    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 * @since 10.2.0
			 */
			do_action( $this->get_hook_prefix() . 'updated', $order, $request );

			$request->set_param( 'context', 'edit' );
			return $this->prepare_item_for_response( $order, $request );
		} catch ( \WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $this->is_valid_order_for_request( $order ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$request->set_param( 'context', 'edit' );

		$force    = (bool) $request['force'];
		$response = $this->prepare_item_for_response( $order, $request );

		if ( $force ) {
			$result = $order->delete( true );
		} else {
			/**
			 * Filter whether an object is trashable.
			 *
			 * @param boolean $supports_trash Whether the object type support trashing.
			 * @param WC_Order $order         The object being considered for trashing support.
			 * @since 10.2.0
			 */
			$supports_trash = apply_filters( $this->get_hook_prefix() . 'object_trashable', EMPTY_TRASH_DAYS > 0, $order );

			if ( ! $supports_trash ) {
				return $this->get_route_error_by_code( self::TRASH_NOT_SUPPORTED );
			}

			if ( 'trash' === $order->get_status() ) {
				return $this->get_route_error_by_code( self::CANNOT_TRASH );
			}

			$order->delete();
			$result = 'trash' === $order->get_status();
		}

		if ( ! $result ) {
			return $this->get_route_error_by_code( self::CANNOT_DELETE );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param WC_Order         $order   The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 * @since 10.2.0
		 */
		do_action( $this->get_hook_prefix() . 'deleted', $order, $response, $request );

		return $response;
	}

	/**
	 * Check if an order is valid.
	 *
	 * @param WC_Order $order The order object.
	 * @return bool True if the order is valid, false otherwise.
	 */
	protected function is_valid_order_for_request( $order ): bool {
		return $order instanceof WC_Order && $order->get_id() !== 0 && 'shop_order_refund' !== $order->get_type();
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
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
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
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
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'edit', $request['id'] ) ) {
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
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $request['id'] ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}
}

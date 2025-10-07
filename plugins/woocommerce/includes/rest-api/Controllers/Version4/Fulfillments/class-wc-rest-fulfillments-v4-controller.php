<?php
/**
 * Order Fulfillments REST Controller for API Version 4
 *
 * This is a completely independent base controller for WooCommerce API v4.
 * Unlike previous versions, this does not inherit from v3, v2, or v1 controllers.
 *
 * @class   WC_REST_Fulfillments_V4_Controller
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\OrderFulfillmentsRestController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce REST API Version 4 Fulfillments Controller
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_V4_Controller
 * @version 4.0.0
 */
class WC_REST_Fulfillments_V4_Controller extends WC_REST_V4_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'fulfillments';

	/**
	 * Order fulfillments controller instance.
	 *
	 * @var OrderFulfillmentsRestController
	 */
	protected $order_fulfillments_controller;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->order_fulfillments_controller = new OrderFulfillmentsRestController();
	}

	/**
	 * Register the routes for fulfillments.
	 *
	 * @since 4.0.0
	 */
	public function register_routes() {
		// Register the route for getting and setting order fulfillments.
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fulfillments' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_args_for_get_fulfillments(),
					'schema'              => $this->get_schema_for_get_fulfillments(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_args_for_create_fulfillment(),
					'schema'              => $this->get_schema_for_create_fulfillment(),
				),
			),
		);

		// Register the route for getting a specific fulfillment.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<fulfillment_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_args_for_get_fulfillment(),
					'schema'              => $this->get_schema_for_get_fulfillment(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_args_for_update_fulfillment(),
					'schema'              => $this->get_schema_for_update_fulfillment(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_args_for_delete_fulfillment(),
					'schema'              => $this->get_schema_for_delete_fulfillment(),
				),
			),
		);
	}

	/**
	 * Get a list of fulfillments for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_fulfillments( WP_REST_Request $request ): WP_REST_Response {
		$order_id = (int) $request->get_param( 'order_id' );

		// Validate the order ID.
		if ( ! $order_id ) {
			return $this->prepare_error_response(
				'woocommerce_rest_order_id_required',
				__( 'The order ID is required.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $this->prepare_error_response(
				'woocommerce_rest_order_invalid_id',
				__( 'Invalid order ID.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::NOT_FOUND ) )
			);
		}

		$request->set_param( 'order_id', $order_id );
		return $this->order_fulfillments_controller->get_fulfillments( $request );
	}

	/**
	 * Create a fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function create_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$params    = $request->get_json_params();
		$entity_id = $params['entity_id'] ?? null;

		// Validate the entity ID.
		if ( ! $entity_id ) {
			return $this->prepare_error_response(
				'woocommerce_rest_entity_id_required',
				__( 'The entity ID is required.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
			);
		}
		$order = wc_get_order( (int) $entity_id );
		if ( ! $order ) {
			return $this->prepare_error_response(
				'woocommerce_rest_order_invalid_id',
				__( 'Invalid order ID.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::NOT_FOUND ) )
			);
		}

		$request->set_param( 'order_id', $entity_id );
		return $this->order_fulfillments_controller->create_fulfillment( $request );
	}

	/**
	 * Get a specific fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$fulfillment_id = (int) $request->get_param( 'fulfillment_id' );
		$fulfillment    = new Fulfillment( $fulfillment_id );

		if ( ! $fulfillment->get_id() ) {
			return $this->prepare_error_response(
				'woocommerce_rest_fulfillment_invalid_id',
				__( 'Invalid fulfillment ID.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::NOT_FOUND ) )
			);
		}

		if ( $fulfillment->get_entity_type() !== \WC_Order::class ) {
			return $this->prepare_error_response(
				'woocommerce_rest_invalid_entity_type',
				__( 'The entity type must be "order".', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
			);
		}

		$order_id = (int) $fulfillment->get_entity_id();
		$request->set_param( 'order_id', $order_id );
		return $this->order_fulfillments_controller->get_fulfillment( $request );
	}

	/**
	 * Update a specific fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function update_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$fulfillment_id = (int) $request->get_param( 'fulfillment_id' );
		$fulfillment    = new Fulfillment( $fulfillment_id );

		if ( ! $fulfillment->get_id() ) {
			return $this->prepare_error_response(
				'woocommerce_rest_fulfillment_invalid_id',
				__( 'Invalid fulfillment ID.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::NOT_FOUND ) )
			);
		}

		if ( $fulfillment->get_entity_type() !== \WC_Order::class ) {
			return $this->prepare_error_response(
				'woocommerce_rest_invalid_entity_type',
				__( 'The entity type must be "order".', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
			);
		}

		$order_id = (int) $fulfillment->get_entity_id();
		$request->set_param( 'order_id', $order_id );
		return $this->order_fulfillments_controller->update_fulfillment( $request );
	}

	/**
	 * Delete a specific fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function delete_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$fulfillment_id = (int) $request->get_param( 'fulfillment_id' );
		$fulfillment    = new Fulfillment( $fulfillment_id );
		$order_id       = (int) $fulfillment->get_entity_id();
		$request->set_param( 'order_id', $order_id );
		return $this->order_fulfillments_controller->delete_fulfillment( $request );
	}


	/**
	 * Permission check for REST API endpoints, given the request method.
	 * For all fulfillments methods that have an order_id, we need to be sure the user has permission to view the order.
	 * For all other methods, we check if the user is logged in as admin and has the required capability.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|\WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 *
	 * @throws \WP_Error If the URL contains an order, but the order does not exist.
	 */
	public function check_permission_for_fulfillments( WP_REST_Request $request ) {
		// Fetch the order first if there's an order_id in the request.
		$order = null;

		// If there's an order_id in the request, try to get the order.
		if ( $request->has_param( 'order_id' ) ) {
			$order_id = (int) $request->get_param( 'order_id' );
			$order    = wc_get_order( $order_id );
		}

		// If there's a fulfillment_id in the request, try to get the order from the fulfillment.
		if ( ! $order && $request->has_param( 'fulfillment_id' ) ) {
			$fulfillment_id = (int) $request->get_param( 'fulfillment_id' );
			if ( $fulfillment_id ) {
				try {
					$fulfillment = new Fulfillment( $fulfillment_id );
					$order_id    = (int) $fulfillment->get_entity_id();
					$order       = wc_get_order( $order_id );
				} catch ( ApiException $ex ) {
					return new \WP_Error(
						$ex->getErrorCode(),
						$ex->getMessage(),
						array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
					);
				} catch ( \Exception $e ) {
					return new \WP_Error(
						'woocommerce_rest_fulfillment_invalid_id',
						$e->getMessage(),
						array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
					);
				}
			}
		}

		// If there's no order_id in the request, try to get it from the request body.
		$body_params = $request->get_json_params();
		if ( ! $order && isset( $body_params['entity_id'] ) && isset( $body_params['entity_type'] ) ) {
			if ( \WC_Order::class !== $body_params['entity_type'] ) {
				return new \WP_Error(
					'woocommerce_rest_invalid_entity_type',
					esc_html__( 'The entity type must be "order".', 'woocommerce' ),
					array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
				);
			}

			$order_id = (int) $body_params['entity_id'];
			$order    = wc_get_order( $order_id );
		}

		// If there's still no order, return an error.
		if ( ! $order ) {
			return new \WP_Error(
				'woocommerce_rest_order_id_required',
				esc_html__( 'The order ID is required.', 'woocommerce' ),
				array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
			);
		}

		// Check if the user is logged in as admin, and has the required capability.
		// Admins who can manage WooCommerce can view all fulfillments.
		if ( current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		// Check if the order exists, and if the current user is the owner of the order, and the request is a read request.
		// We allow this because we need to render the order fulfillments on the customer's order details and order tracking pages.
		// But they will be only able to view them, not edit.
		if ( get_current_user_id() === $order->get_customer_id() && WP_REST_Server::READABLE === $request->get_method() ) {
			return true;
		}

		// Return an error related to the request method.
		$error_information = $this->get_authentication_error_by_method( $request->get_method() );

		if ( is_null( $error_information ) ) {
			return false;
		}

		return new \WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Returns an authentication error message for a given HTTP verb.
	 *
	 * @param string $method HTTP method.
	 * @return array|null Error information on success, null otherwise.
	 */
	protected function get_authentication_error_by_method( string $method ) {
		$errors = array(
			'GET'    => array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => __( 'Sorry, you cannot view resources.', 'woocommerce' ),
			),
			'POST'   => array(
				'code'    => 'woocommerce_rest_cannot_create',
				'message' => __( 'Sorry, you cannot create resources.', 'woocommerce' ),
			),
			'PUT'    => array(
				'code'    => 'woocommerce_rest_cannot_update',
				'message' => __( 'Sorry, you cannot update resources.', 'woocommerce' ),
			),
			'PATCH'  => array(
				'code'    => 'woocommerce_rest_cannot_update',
				'message' => __( 'Sorry, you cannot update resources.', 'woocommerce' ),
			),
			'DELETE' => array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => __( 'Sorry, you cannot delete resources.', 'woocommerce' ),
			),
		);

		return $errors[ $method ] ?? null;
	}

	/**
	 * Get the arguments for the get order fulfillments endpoint.
	 *
	 * @return array
	 */
	private function get_args_for_get_fulfillments(): array {
		return array(
			'order_id' => array(
				'description' => __( 'Unique identifier for the order.', 'woocommerce' ),
				'type'        => 'integer',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	/**
	 * Get the schema for the get order fulfillments endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_get_fulfillments(): array {
		$schema          = $this->get_base_schema();
		$schema['title'] = __( 'Get fulfillments response.', 'woocommerce' );
		$schema['type']  = 'array';
		$schema['items'] = array(
			'type'       => 'object',
			'properties' => $this->get_read_schema_for_fulfillment(),
		);
		return $schema;
	}

	/**
	 * Get the arguments for the create fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_args_for_create_fulfillment(): array {
		return $this->get_write_args_for_fulfillment( true );
	}

	/**
	 * Get the schema for the create fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_create_fulfillment(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Create fulfillment response.', 'woocommerce' );
		$schema['properties'] = $this->get_read_schema_for_fulfillment();
		return $schema;
	}

	/**
	 * Get the arguments for the get fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_args_for_get_fulfillment(): array {
		return array(
			'fulfillment_id' => array(
				'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Get the schema for the get fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_get_fulfillment(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Get fulfillment response.', 'woocommerce' );
		$schema['properties'] = $this->get_read_schema_for_fulfillment();

		return $schema;
	}

	/**
	 * Get the arguments for the update fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_args_for_update_fulfillment(): array {
		return $this->get_write_args_for_fulfillment( false );
	}

	/**
	 * Get the schema for the update fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_update_fulfillment(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Update fulfillment response.', 'woocommerce' );
		$schema['type']       = 'object';
		$schema['properties'] = $this->get_read_schema_for_fulfillment();

		return $schema;
	}

	/**
	 * Get the arguments for the delete fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_args_for_delete_fulfillment(): array {
		return array(
			'fulfillment_id'  => array(
				'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
				'type'        => 'integer',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
			'notify_customer' => array(
				'description' => __( 'Whether to notify the customer about the fulfillment update.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	/**
	 * Get the schema for the delete fulfillment endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_delete_fulfillment(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Delete fulfillment response.', 'woocommerce' );
		$schema['properties'] = array(
			'message' => array(
				'description' => __( 'The response message.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the base schema for the fulfillment with a read context.
	 *
	 * @return array
	 */
	private function get_read_schema_for_fulfillment() {
		return array(
			'id'           => array(
				'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'entity_type'  => array(
				'description' => __( 'The type of entity for which the fulfillment is created.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
			'entity_id'    => array(
				'description' => __( 'Unique identifier for the entity.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
			'status'       => array(
				'description' => __( 'The status of the fulfillment.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'unfulfilled',
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
			'is_fulfilled' => array(
				'description' => __( 'Whether the fulfillment is fulfilled.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'required'    => true,
				'context'     => array( 'view', 'edit' ),
			),
			'date_updated' => array(
				'description' => __( 'The date the fulfillment was last updated.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'required'    => true,
			),
			'date_deleted' => array(
				'description' => __( 'The date the fulfillment was deleted.', 'woocommerce' ),
				'anyOf'       => array(
					array(
						'type' => 'string',
					),
					array(
						'type' => 'null',
					),
				),
				'default'     => null,
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'required'    => true,
			),
			'meta_data'    => array(
				'description' => __( 'Meta data for the fulfillment.', 'woocommerce' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => $this->get_schema_for_meta_data(),
			),
		);
	}

	/**
	 * Get the base args for the fulfillment with a write context.
	 *
	 * @param bool $is_create Whether the args list is for a create request.
	 *
	 * @return array
	 */
	private function get_write_args_for_fulfillment( bool $is_create = false ) {
		return array_merge(
			! $is_create ? array(
				'fulfillment_id' => array(
					'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			) : array(),
			array(
				'entity_type'     => array(
					'description' => __( 'The type of entity for which the fulfillment is created. Must be "order".', 'woocommerce' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'entity_id'       => array(
					'description' => __( 'Unique identifier for the entity.', 'woocommerce' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'status'          => array(
					'description' => __( 'The status of the fulfillment.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'unfulfilled',
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				),
				'is_fulfilled'    => array(
					'description' => __( 'Whether the fulfillment is fulfilled.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data'       => array(
					'description' => __( 'Meta data for the fulfillment.', 'woocommerce' ),
					'type'        => 'array',
					'required'    => true,
					'schema'      => $this->get_schema_for_meta_data(),
				),
				'notify_customer' => array(
					'description' => __( 'Whether to notify the customer about the fulfillment update.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Get the schema for the meta data.
	 *
	 * @return array
	 */
	private function get_schema_for_meta_data(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'    => array(
					'description' => __( 'The unique identifier for the meta data. Set `0` for new records.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'key'   => array(
					'description' => __( 'The key of the meta data.', 'woocommerce' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'value' => array(
					'description' => __( 'The value of the meta data.', 'woocommerce' ),
					'type'        => 'string',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
			'required'   => true,
			'context'    => array( 'view', 'edit' ),
			'readonly'   => true,
		);
	}

	/**
	 * Prepare an error response.
	 *
	 * @param string $code The error code.
	 * @param string $message The error message.
	 * @param array  $data Additional error data, including 'status' key for HTTP status code.
	 *
	 * @return WP_REST_Response The error response.
	 */
	private function prepare_error_response( $code, $message, $data ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'code'    => $code,
				'message' => $message,
				'data'    => $data,
			),
			$data['status'] ?? WP_Http::BAD_REQUEST
		);
	}
}

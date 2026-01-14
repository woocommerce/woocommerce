<?php
/**
 * Order Fulfillments REST Controller for API Version 4
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 * This is a completely independent base controller for WooCommerce API v4.
 * Unlike previous versions, this does not inherit from v3, v2, or v1 controllers.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\OrderFulfillmentsRestController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema\FulfillmentSchema;
use WP_Http;
use WP_Error;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fulfillments Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'fulfillments';

	/**
	 * Schema class for this route.
	 *
	 * @var FulfillmentSchema
	 */
	protected $item_schema;

	/**
	 * Order fulfillments controller instance.
	 *
	 * @var OrderFulfillmentsRestController
	 */
	protected $order_fulfillments_controller;

	/**
	 * Initialize the controller.
	 *
	 * @param FulfillmentSchema               $item_schema                   Fulfillment schema class.
	 * @param OrderFulfillmentsRestController $order_fulfillments_controller Order fulfillments controller.
	 *
	 * @internal
	 */
	final public function init( FulfillmentSchema $item_schema, OrderFulfillmentsRestController $order_fulfillments_controller ) {
		$this->item_schema                   = $item_schema;
		$this->order_fulfillments_controller = $order_fulfillments_controller;
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
				'schema' => array( $this, 'get_public_item_schema' ),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fulfillments' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => array(
						'order_id' => array(
							'description' => __( 'Unique identifier for the order.', 'woocommerce' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			),
		);

		// Register the route for getting a specific fulfillment.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<fulfillment_id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'fulfillment_id' => array(
						'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_fulfillments' ),
					'args'                => array(
						'notify_customer' => array(
							'description' => __( 'Whether to notify the customer about the fulfillment update.', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'required'    => false,
						),
					),
				),
			),
		);

		// Register the route for getting shipping providers.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/providers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_providers' ),
					'permission_callback' => array( $this, 'check_permission_for_providers' ),
					'schema'              => array( $this, 'get_schema_for_providers' ),
				),
			)
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

		if ( $fulfillment->get_entity_type() !== WC_Order::class ) {
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

		if ( $fulfillment->get_entity_type() !== WC_Order::class ) {
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
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 *
	 * @throws WP_Error If the URL contains an order, but the order does not exist.
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
					return new WP_Error(
						$ex->getErrorCode(),
						$ex->getMessage(),
						array( 'status' => esc_attr( WP_Http::BAD_REQUEST ) )
					);
				} catch ( \Exception $e ) {
					return new WP_Error(
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
			if ( WC_Order::class !== $body_params['entity_type'] ) {
				return new WP_Error(
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
			return new WP_Error(
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

		if ( false === $error_information ) {
			return false;
		}

		return $error_information;
	}

	/**
	 * Get the schema for the fulfillment resource. This is consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 *
	 * @return array The schema for the fulfillment resource.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the item response for a fulfillment.
	 *
	 * @param mixed           $item    The fulfillment item.
	 * @param WP_REST_Request $request The request object.
	 * @return array The item response.
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $item, $request, $this->get_fields_for_response( $request ) );
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

	/**
	 * Get all shipping providers.
	 *
	 * @since 10.5.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_providers( WP_REST_Request $request ): WP_REST_Response {
		$providers = \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils::get_shipping_providers_object();

		/**
		 * Filters the shipping providers response before it is returned.
		 *
		 * Each provider in the array must have the following structure:
		 * - 'label' (string): The display name of the provider.
		 * - 'icon' (string): URL to the provider's icon.
		 * - 'value' (string): The provider's unique identifier.
		 * - 'url' (string): The tracking URL template.
		 *
		 * @param array           $providers The shipping providers data.
		 * @param WP_REST_Request $request   The request object.
		 *
		 * @since 10.5.0
		 */
		$providers = apply_filters( 'woocommerce_rest_prepare_fulfillments_providers', $providers, $request );

		// Validate filtered result to prevent extensions from returning invalid structures.
		if ( ! is_array( $providers ) ) {
			_doing_it_wrong(
				'woocommerce_rest_prepare_fulfillments_providers',
				esc_html__( 'The filter must return an array of providers.', 'woocommerce' ),
				'10.5.0'
			);
			$providers = array();
		} else {
			$providers = $this->validate_providers_structure( $providers );
		}

		return new WP_REST_Response( $providers, WP_Http::OK );
	}

	/**
	 * Validate the structure of providers returned by a filter.
	 *
	 * Removes any providers that don't have the required keys (label, icon, value, url).
	 *
	 * @since 10.5.0
	 * @param array $providers The providers array to validate.
	 * @return array The validated providers array with invalid entries removed.
	 */
	private function validate_providers_structure( array $providers ): array {
		$required_keys   = array( 'label', 'icon', 'value', 'url' );
		$valid_providers = array();
		$has_invalid     = false;

		foreach ( $providers as $key => $provider ) {
			if ( ! is_array( $provider ) ) {
				$has_invalid = true;
				continue;
			}

			$missing_keys = array_diff( $required_keys, array_keys( $provider ) );
			if ( ! empty( $missing_keys ) ) {
				$has_invalid = true;
				continue;
			}

			$valid_providers[ $key ] = $provider;
		}

		if ( $has_invalid ) {
			_doing_it_wrong(
				'woocommerce_rest_prepare_fulfillments_providers',
				esc_html__( 'Some providers were removed because they are missing required keys (label, icon, value, url).', 'woocommerce' ),
				'10.5.0'
			);
		}

		return $valid_providers;
	}

	/**
	 * Check permissions for accessing shipping providers.
	 *
	 * @since 10.5.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the current user has the capability, otherwise a WP_Error.
	 */
	public function check_permission_for_providers( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}

		return true;
	}

	/**
	 * Get the schema for the providers endpoint.
	 *
	 * @since 10.5.0
	 * @return array The schema for the providers endpoint.
	 */
	public function get_schema_for_providers(): array {
		return array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => __( 'Shipping providers', 'woocommerce' ),
			'type'                 => 'object',
			'additionalProperties' => array(
				'type'       => 'object',
				'properties' => array(
					'label' => array(
						'description' => __( 'The display name of the shipping provider.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'icon'  => array(
						'description' => __( 'The icon URL for the shipping provider.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'value' => array(
						'description' => __( 'The unique key for the shipping provider.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'url'   => array(
						'description' => __( 'The tracking URL template for the shipping provider.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			),
		);
	}
}

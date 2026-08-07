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

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\OrderFulfillmentsRestController;
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
	 * The fulfillment addressed by the current single-item request.
	 *
	 * Populated by resolve_fulfillment_from_url() so the permission callback and the handler
	 * that follows it act on the same object instead of loading it twice.
	 *
	 * @var Fulfillment|null
	 */
	private $requested_fulfillment = null;

	/**
	 * The request the fulfillment above was resolved for.
	 *
	 * The controller is a shared instance, so more than one request can run through it in a
	 * single process. Tying the resolved fulfillment to its request keeps the reuse inside that
	 * request, where nothing has written to the row yet, instead of handing a later request a
	 * copy the delegate has since updated or deleted.
	 *
	 * @var WP_REST_Request|null
	 *
	 * @phpstan-var WP_REST_Request<array<string, mixed>>|null
	 */
	private $requested_fulfillment_request = null;

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
					'permission_callback' => array( $this, 'check_permission_for_single_fulfillment' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_single_fulfillment' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_fulfillment' ),
					'permission_callback' => array( $this, 'check_permission_for_single_fulfillment' ),
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
		$fulfillment = $this->resolve_fulfillment_from_url( $request );

		if ( is_wp_error( $fulfillment ) ) {
			return $this->error_response_from_wp_error( $fulfillment );
		}

		$this->pin_request_to_fulfillment( $request, $fulfillment );
		return $this->order_fulfillments_controller->get_fulfillment( $request );
	}

	/**
	 * Update a specific fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function update_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$fulfillment = $this->resolve_fulfillment_from_url( $request );

		if ( is_wp_error( $fulfillment ) ) {
			return $this->error_response_from_wp_error( $fulfillment );
		}

		$this->pin_request_to_fulfillment( $request, $fulfillment );
		return $this->order_fulfillments_controller->update_fulfillment( $request );
	}

	/**
	 * Delete a specific fulfillment for a specific order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function delete_fulfillment( WP_REST_Request $request ): WP_REST_Response {
		$fulfillment = $this->resolve_fulfillment_from_url( $request );

		if ( is_wp_error( $fulfillment ) ) {
			return $this->error_response_from_wp_error( $fulfillment );
		}

		$this->pin_request_to_fulfillment( $request, $fulfillment );
		return $this->order_fulfillments_controller->delete_fulfillment( $request );
	}

	/**
	 * Permission check for the collection and create endpoints.
	 *
	 * The collection endpoint is authorized against the order_id query argument, and the create
	 * endpoint against the entity_id in the request body. A fulfillment_id query argument has no
	 * effect on which order is authorized.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 */
	public function check_permission_for_fulfillments( WP_REST_Request $request ) {
		// Fetch the order first if there's an order_id in the request.
		$order = null;

		if ( $request->has_param( 'order_id' ) ) {
			$order_id = (int) $request->get_param( 'order_id' );
			$order    = wc_get_order( $order_id );
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

		return $this->check_order_access( $order, $request );
	}

	/**
	 * Permission check for the single-fulfillment endpoints.
	 *
	 * The order to authorize against is derived from the requested fulfillment, read from the
	 * fulfillment_id route placeholder, so it always matches the order the handler acts on. Any
	 * request-supplied order_id is ignored.
	 *
	 * A caller who cannot read that order is answered as if the fulfillment did not exist, so the
	 * route cannot be used to tell an ID that exists from one that does not.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 *
	 * @since 11.1.0
	 */
	public function check_permission_for_single_fulfillment( WP_REST_Request $request ) {
		$fulfillment = $this->resolve_fulfillment_from_url( $request );

		if ( is_wp_error( $fulfillment ) ) {
			return $fulfillment;
		}

		$order = wc_get_order( (int) $fulfillment->get_entity_id() );
		if ( ! $order instanceof WC_Order ) {
			return new WP_Error(
				'woocommerce_rest_order_invalid_id',
				esc_html__( 'Invalid order ID.', 'woocommerce' ),
				array( 'status' => WP_Http::NOT_FOUND )
			);
		}

		$access = $this->check_order_access( $order, $request );

		// Callers who can read the order keep the real answer: the owner of an order needs to see
		// that a write was refused rather than that the fulfillment vanished. Anonymous callers
		// keep the 401 so they can retry with credentials. Everyone else is told the fulfillment
		// does not exist, because reporting a refusal would confirm that this ID does.
		if ( true === $access || 0 === get_current_user_id() || $this->user_can_read_order( $order ) ) {
			return $access;
		}

		return new WP_Error(
			'woocommerce_rest_fulfillment_invalid_id',
			esc_html__( 'Invalid fulfillment ID.', 'woocommerce' ),
			array( 'status' => WP_Http::NOT_FOUND )
		);
	}

	/**
	 * Load the fulfillment addressed by the fulfillment_id route placeholder.
	 *
	 * The ID is read from get_url_params() rather than get_param(), because
	 * WP_REST_Request::get_parameter_order() ranks query string arguments above URL placeholders.
	 * Reading it with get_param() would let a `?fulfillment_id=` argument point the handler at a
	 * different fulfillment than the one the permission callback authorized.
	 *
	 * The result is kept on the instance so the handler that runs after the permission callback
	 * reuses the same object rather than reading it from the database again.
	 *
	 * @param WP_REST_Request $request The request to read the placeholder from.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return Fulfillment|WP_Error The requested fulfillment, or an error if it does not exist or does not belong to an order.
	 */
	private function resolve_fulfillment_from_url( WP_REST_Request $request ) {
		$url_params     = $request->get_url_params();
		$fulfillment_id = (int) ( $url_params['fulfillment_id'] ?? 0 );

		if ( ! $fulfillment_id ) {
			return new WP_Error(
				'woocommerce_rest_fulfillment_invalid_id',
				esc_html__( 'Invalid fulfillment ID.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		if ( $this->requested_fulfillment_request === $request
			&& $this->requested_fulfillment instanceof Fulfillment
			&& $this->requested_fulfillment->get_id() === $fulfillment_id ) {
			return $this->requested_fulfillment;
		}

		try {
			// The data store throws when no row matches, so this is also the not-found path.
			$fulfillment = new Fulfillment( $fulfillment_id );
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'woocommerce_rest_fulfillment_invalid_id',
				esc_html__( 'Invalid fulfillment ID.', 'woocommerce' ),
				array( 'status' => WP_Http::NOT_FOUND )
			);
		}

		if ( WC_Order::class !== $fulfillment->get_entity_type() ) {
			return new WP_Error(
				'woocommerce_rest_invalid_entity_type',
				esc_html__( 'The entity type must be "order".', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$this->requested_fulfillment         = $fulfillment;
		$this->requested_fulfillment_request = $request;

		return $fulfillment;
	}

	/**
	 * Overwrite the fulfillment and order the delegate controller will act on.
	 *
	 * The delegate reads both with get_param(), so the values resolved from the route placeholder
	 * are written back to the request before handing it over. WP_REST_Request::set_param() writes
	 * to every parameter source that already holds the key, so a spoofed query string value is
	 * replaced rather than left in place at a higher priority.
	 *
	 * id, entity_type and entity_id are pinned for the same reason. The delegate's update handler
	 * feeds the whole JSON body into Fulfillment::set_props(), and all three are settable props, so
	 * a request body could otherwise reassign the fulfillment to another entity, set an entity type
	 * that no longer resolves, or write the addressed fulfillment's values over a different row.
	 *
	 * @param WP_REST_Request $request     The request being delegated.
	 * @param Fulfillment     $fulfillment The fulfillment the request was authorized against.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return void
	 */
	private function pin_request_to_fulfillment( WP_REST_Request $request, Fulfillment $fulfillment ): void {
		$request->set_param( 'fulfillment_id', $fulfillment->get_id() );
		$request->set_param( 'id', $fulfillment->get_id() );
		$request->set_param( 'order_id', (int) $fulfillment->get_entity_id() );
		$request->set_param( 'entity_type', $fulfillment->get_entity_type() );
		$request->set_param( 'entity_id', (string) $fulfillment->get_entity_id() );
	}

	/**
	 * Convert a WP_Error into the response shape the handlers return.
	 *
	 * @param WP_Error $error The error to convert.
	 *
	 * @return WP_REST_Response
	 */
	private function error_response_from_wp_error( WP_Error $error ): WP_REST_Response {
		$error_data = $error->get_error_data();
		$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : WP_Http::BAD_REQUEST;

		return $this->prepare_error_response(
			(string) $error->get_error_code(),
			$error->get_error_message(),
			array( 'status' => $status )
		);
	}

	/**
	 * Check whether the current user may access fulfillments of the given order.
	 *
	 * @param \WC_Order|\WC_Order_Refund|false|null $order   The order the request was authorized against, or a falsy value if none was resolved.
	 * @param WP_REST_Request                       $request The request for which the permission is checked.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 */
	private function check_order_access( $order, WP_REST_Request $request ) {
		// If there's no order, return an error.
		if ( ! $order ) {
			return new WP_Error(
				'woocommerce_rest_order_id_required',
				esc_html__( 'The order ID is required.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		// wc_get_order() returns a refund object for refund IDs. Refunds have no customer and cannot have fulfillments.
		if ( ! $order instanceof WC_Order ) {
			return new WP_Error(
				'woocommerce_rest_order_invalid_id',
				esc_html__( 'Invalid order ID.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		// Check if the user is logged in as admin, and has the required capability.
		// Admins who can manage WooCommerce can view all fulfillments.
		if ( current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		// The order's own customer gets read access only.
		if ( WP_REST_Server::READABLE === $request->get_method() && $this->user_can_read_order( $order ) ) {
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
	 * Check whether the current user may read the given order.
	 *
	 * @param WC_Order $order The order to check.
	 *
	 * @return bool True if the current user manages WooCommerce or owns the order.
	 */
	private function user_can_read_order( WC_Order $order ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		// Guest order fulfillments are rendered server-side via templates, so they don't need REST API access.
		// The get_current_user_id() > 0 check prevents unauthenticated users from accessing guest orders
		// where both get_current_user_id() and get_customer_id() would return 0.
		return get_current_user_id() > 0 && get_current_user_id() === $order->get_customer_id();
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
		$providers = array();
		foreach ( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils::get_shipping_providers() as $provider ) {
			$providers[ $provider->get_key() ] = array(
				'label' => $provider->get_name(),
				'icon'  => $provider->get_icon(),
				'value' => $provider->get_key(),
				'url'   => $provider->get_tracking_url( '__PLACEHOLDER__' ) ?? '',
			);
		}

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

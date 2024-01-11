<?php

namespace Automattic\WooCommerce\Internal\ReceiptRendering;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use \WP_HTTP_Response;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;
use \Exception;
use \InvalidArgumentException;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Controller for the REST endpoints associated to the receipt rendering engine.
 * The endpoints require the read_shop_order capability for the order at hand.
 */
class ReceiptRenderingRestController implements RegisterHooksInterface {
	use AccessiblePrivateMethods;

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	private string $route_namespace = 'wc/v3';

	/**
	 * Holds authentication error messages for each HTTP verb.
	 *
	 * @var array
	 */
	private array $authentication_errors_by_method;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->authentication_errors_by_method = array(
			'GET'    => array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => __( 'Sorry, you cannot view resources.', 'woocommerce' ),
			),
			'POST'   => array(
				'code'    => 'woocommerce_rest_cannot_create',
				'message' => __( 'Sorry, you cannot create resources.', 'woocommerce' ),
			),
			'DELETE' => array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => __( 'Sorry, you cannot delete resources.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Register the hooks used by the class.
	 */
	public function register() {
		self::add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'handle_woocommerce_rest_api_get_rest_namespaces' ) );
	}

	/**
	 * Handle the woocommerce_rest_api_get_rest_namespaces filter
	 * to add ourselves to the list of REST API controllers registered by WooCommerce.
	 *
	 * @param array $namespaces The original list of WooCommerce REST API namespaces/controllers.
	 * @return array The updated list of WooCommerce REST API namespaces/controllers.
	 */
	private function handle_woocommerce_rest_api_get_rest_namespaces( array $namespaces ): array {
		$namespaces['wc/v3']['order-receipts'] = self::class;
		return $namespaces;
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		self::mark_method_as_accessible( 'run' );

		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/receipt',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( 'create_order_receipt', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'read_shop_order', $request->get_param( 'id' ) ),
					'args'                => $this->get_args_for_create_order_receipt(),
					'schema'              => $this->get_schema_for_get_and_post_order_receipt(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/receipt',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( 'get_order_receipt', $request ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'read_shop_order', $request->get_param( 'id' ) ),
					'args'                => $this->get_args_for_get_order_receipt(),
					'schema'              => $this->get_schema_for_get_and_post_order_receipt(),
				),
			)
		);

	}

	/**
	 * Handle a request for one of the provided REST API endpoints.
	 *
	 * If an exception is thrown, the exception message will be returned as part of the response
	 * if the user has the 'manage_woocommerce' capability.
	 *
	 * @param string          $method_name The name of the class method to execute.
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response The response to send back to the client.
	 */
	private function run( string $method_name, WP_REST_Request $request ) {
		try {
			return rest_ensure_response( $this->$method_name( $request ) );
		} catch ( Exception $ex ) {
			wc_get_logger()->error( "ReceiptRenderingRestController: when executing method $method_name: {$ex->getMessage()}" );
			return $this->internal_wp_error( $ex );
		}
	}

	/**
	 * Return an WP_Error object for an internal server error, with exception information if the current user is an admin.
	 *
	 * @param Exception $exception The exception to maybe include information from.
	 * @return WP_Error
	 */
	private function internal_wp_error( Exception $exception ): WP_Error {
		$data = array( 'status' => 500 );
		if ( $this->current_user_can( 'manage_woocommerce' ) ) {
			$data['exception_message'] = $exception->getMessage();
		}
		$data['exception_message'] = $exception->getMessage();

		return new WP_Error( 'woocommerce_rest_internal_error', __( 'Internal server error', 'woocommerce' ), $data );
	}

	/**
	 * Permission check for JSON REST API endpoints.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 * @param string          $required_capability_name The name of the required capability.
	 * @param mixed           ...$extra_args Extra arguments to be used for the permission check.
	 * @return bool|WP_Error True if the current user has the capability, an "Unauthorized" error otherwise.
	 */
	private function check_permission( WP_REST_Request $request, string $required_capability_name, ...$extra_args ) {
		return $this->check_permission_by_method( $request->get_method(), $required_capability_name, $extra_args );
	}

	/**
	 * Permission check for REST API endpoints, given the request method.
	 *
	 * @param string $method The HTTP method of the request.
	 * @param string $required_capability_name The name of the required capability.
	 * @param mixed  ...$extra_args Extra arguments to be used for the permission check.
	 * @return bool|WP_Error True if the current user has the capability, an "Unauthorized" error otherwise.
	 */
	private function check_permission_by_method( string $method, string $required_capability_name, ...$extra_args ) {
		if ( $this->current_user_can( $required_capability_name, $extra_args ) ) {
			return true;
		}

		$error_information = $this->authentication_errors_by_method[ $method ] ?? null;
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Check if the current user has a given capability.
	 *
	 * @param string $capability The capability to check.
	 * @param mixed  ...$extra_args Extra arguments to be passed to current_user_can.
	 * @return bool True if the user has the specified capability.
	 */
	private function current_user_can( string $capability, ...$extra_args ): bool {
		return wc_get_container()->get( LegacyProxy::class )->call_function( 'current_user_can', $capability, $extra_args );
	}

	/**
	 * Handle the GET /orders/id/receipt:
	 *
	 * Return the data for a receipt if it exists, or a 404 error if it doesn't.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error
	 */
	public function get_order_receipt( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'id' );
		$filename = wc_get_container()->get( ReceiptRenderingEngine::class )->get_existing_receipt( $order_id );

		return is_null( $filename ) ?
			new WP_Error( 'woocommerce_rest_not_found', __( 'Receipt not found', 'woocommerce' ), array( 'status' => 404 ) ) :
			$this->get_response_for_file( $filename );
	}

	/**
	 * Handle the POST /orders/id/receipt:
	 *
	 * Return the data for a receipt if it exists, or create a new receipt and return its data otherwise.
	 *
	 * Optional query string arguments:
	 *
	 * expiration_date: formatted as yyyy-mm-dd.
	 * expiration_days: a number, 0 is today, 1 is tomorrow, etc.
	 * force_new: defaults to false, if true, create a new receipt even if one already exists for the order.
	 *
	 * If neither expiration_date nor expiration_days are supplied, the default is expiration_days = 1.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function create_order_receipt( WP_REST_Request $request ) {
		$expiration_date =
			$request->get_param( 'expiration_date' ) ??
			gmdate( 'Y-m-d', strtotime( "+{$request->get_param('expiration_days')} days" ) );

		$order_id = $request->get_param( 'id' );

		try {
			$filename = wc_get_container()->get( ReceiptRenderingEngine::class )->generate_receipt( $order_id, $expiration_date, $request->get_param( 'force_new' ) );
		} catch ( InvalidArgumentException $ex ) {
			return new WP_Error( 'woocommerce_invalid_argument', $ex->getMessage(), array( 'status' => 400 ) );
		}

		return is_null( $filename ) ?
			new WP_Error( 'woocommerce_rest_not_found', __( 'Order not found', 'woocommerce' ), array( 'status' => 404 ) ) :
			$this->get_response_for_file( $filename );
	}

	/**
	 * Formats the response for both the GET and POST endpoints.
	 *
	 * @param string $filename The filename to return the information for.
	 * @return array The data for the actual response to be returned.
	 */
	private function get_response_for_file( string $filename ) {
		$expiration_date = TransientFilesEngine::get_expiration_date( $filename );
		$public_url      = wc_get_container()->get( TransientFilesEngine::class )->get_public_url( $filename );

		return array(
			'receipt_url'     => $public_url,
			'expiration_date' => $expiration_date,
		);
	}

	/**
	 * Get the accepted arguments for the GET request.
	 *
	 * @return array[] The accepted arguments for the GET request.
	 */
	private function get_args_for_get_order_receipt(): array {
		return array(
			'id' => array(
				'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Get the schema for both the GET and the POST requests.
	 *
	 * @return array[]
	 */
	private function get_schema_for_get_and_post_order_receipt(): array {
		$schema               = $this->get_base_schema();
		$schema['properties'] = array(
			'receipt_url'     => array(
				'description' => __( 'Public url of the receipt.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'expiration_date' => array(
				'description' => __( 'Expiration date of the receipt, formatted as yyyy-mm-dd.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * @return array[]
	 */
	private function get_args_for_create_order_receipt(): array {
		return array(
			'id'              => array(
				'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'expiration_date' => array(
				'description' => __( 'Expiration date formatted as yyyy-mm-dd.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'default'     => null,
			),
			'expiration_days' => array(
				'description' => __( 'Number of days to be added to the current date to get the expiration date.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'default'     => 1,
			),
			'force_new'       => array(
				'description' => __( 'True to force the creation of a new receipt even if one already exists and has not expired yet.', 'woocommerce' ),
				'type'        => 'boolean',
				'required'    => false,
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'default'     => false,
			),
		);
	}

	/**
	 * Get the base schema for the REST API endpoints.
	 *
	 * @return array
	 */
	private function get_base_schema(): array {
		return array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'order receipts',
			'type'    => 'object',
		);
	}
}

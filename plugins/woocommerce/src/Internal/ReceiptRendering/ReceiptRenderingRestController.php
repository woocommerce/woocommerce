<?php

namespace Automattic\WooCommerce\Internal\ReceiptRendering;

use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_Error;
use Automattic\WooCommerce\Internal\RestApiControllerBase;

/**
 * Controller for the REST endpoints associated to the receipt rendering engine.
 * The endpoints require the read_shop_order capability for the order at hand.
 */
class ReceiptRenderingRestController extends RestApiControllerBase {

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'order-receipts';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/receipt',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'create_order_receipt' ),
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
					'callback'            => fn( $request ) => $this->run( $request, 'get_order_receipt' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'read_shop_order', $request->get_param( 'id' ) ),
					'args'                => $this->get_args_for_get_order_receipt(),
					'schema'              => $this->get_schema_for_get_and_post_order_receipt(),
				),
			)
		);

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

		$filename = wc_get_container()->get( ReceiptRenderingEngine::class )->generate_receipt( $order_id, $expiration_date, $request->get_param( 'force_new' ) );

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
	private function get_response_for_file( string $filename ): array {
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
}

<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Controller for the REST endpoint to add order statuses to the WooCommerce REST API.
 */
class OrderStatusRestController extends RestApiControllerBase {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders/statuses';

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Register the routes for order statuses.
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_rest_api_namespace(),
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Get all order statuses.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ) {
		$order_statuses     = wc_get_order_statuses();
		$formatted_statuses = array();

		foreach ( $order_statuses as $status_slug => $status_name ) {
			$slug = str_replace( 'wc-', '', $status_slug );

			$formatted_statuses[] = array(
				'slug' => $slug,
				'name' => wc_get_order_status_name( $slug ),
			);
		}

		if ( ! $formatted_statuses ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'Order statuses not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $formatted_statuses );
	}

	/**
	 * Get the order status schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order_status',
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'description' => __( 'Order status slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Order status name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}
}

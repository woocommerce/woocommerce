<?php
/**
 * REST API ActivityPanelCounts Controller
 *
 * Handles requests to /activity-panel/counts.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * ActivityPanelCounts controller.
 *
 * @internal
 */
class ActivityPanelCounts extends \WC_REST_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'activity-panel/counts';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_counts' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_counts_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Return the orders/reviews/low-stock counts used by the Activity Panel in one response,
	 * instead of one request per count.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_counts( $request ) {
		$order_statuses = (array) $request->get_param( 'order_statuses' );

		// When a merchant has cleared every actionable order status there is nothing
		// "to fulfill". Short-circuit to 0 rather than querying: an empty status list
		// would otherwise count every order, and the previous client-side
		// getUnreadOrders() returned 0 in this case.
		$orders_to_fulfill_count = empty( $order_statuses )
			? 0
			: $this->get_count_via(
				'/wc-analytics/orders',
				array(
					'page'     => 1,
					'per_page' => 1,
					'status'   => $order_statuses,
					'_fields'  => array( 'id' ),
				)
			);

		return rest_ensure_response(
			array(
				'orders_to_fulfill_count'     => $orders_to_fulfill_count,
				'reviews_to_moderate_count'   => $this->get_count_via(
					'/wc-analytics/products/reviews',
					array(
						'page'     => 1,
						'per_page' => 1,
						'status'   => $request->get_param( 'review_status' ),
						'_fields'  => array( 'id' ),
					)
				),
				'products_low_in_stock_count' => $this->get_count_via(
					'/wc-analytics/products/count-low-in-stock',
					array( 'status' => $request->get_param( 'product_status' ) )
				),
			)
		);
	}

	/**
	 * Run one of the existing count endpoints internally and read its total off the response,
	 * so the counting logic itself (query building, permission checks) isn't duplicated here.
	 *
	 * @param string $route  REST route to call, e.g. '/wc-analytics/orders'.
	 * @param array  $params Query params for the sub-request.
	 * @return int|null Null when the sub-request failed, so callers can tell "unknown" apart from a real zero count.
	 */
	private function get_count_via( $route, $params ) {
		$sub_request = new \WP_REST_Request( 'GET', $route );
		foreach ( $params as $key => $value ) {
			$sub_request->set_param( $key, $value );
		}

		$response = rest_do_request( $sub_request );

		if ( $response->is_error() ) {
			wc_get_logger()->warning(
				sprintf( 'Activity Panel counts sub-request to %s failed.', $route ),
				array( 'source' => 'activity-panel-counts' )
			);
			return null;
		}

		$headers = $response->get_headers();
		if ( isset( $headers['X-WP-Total'] ) ) {
			return (int) $headers['X-WP-Total'];
		}

		$data = $response->get_data();
		return isset( $data['total'] ) ? (int) $data['total'] : null;
	}

	/**
	 * Get the query params for the /activity-panel/counts endpoint.
	 *
	 * @return array
	 */
	public function get_counts_params() {
		$params                   = array();
		$params['context']        = $this->get_context_param( array( 'default' => 'view' ) );
		$params['order_statuses'] = array(
			'description'       => __( 'Order statuses counted as "to fulfill".', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'default'           => $this->get_default_order_statuses(),
			'sanitize_callback' => 'wp_parse_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['review_status']  = array(
			'description'       => __( 'Review status counted as "to moderate".', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'hold',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product_status'] = array(
			'description'       => __( 'Product post status used for the low stock count.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'publish',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the default order statuses counted as "to fulfill", matching the store's own
	 * actionable order statuses setting.
	 *
	 * @return array
	 */
	private function get_default_order_statuses() {
		$actionable = get_option( 'woocommerce_actionable_order_statuses', false );

		// Any array is respected as-is, including an explicitly empty one: the merchant
		// intentionally cleared all actionable statuses, so there is nothing to fulfill,
		// matching the previous client-side behaviour. A missing (never configured) or
		// malformed option falls back to the built-in defaults.
		return is_array( $actionable ) ? $actionable : array( 'processing', 'on-hold' );
	}

	/**
	 * Get the schema for the /activity-panel/counts response.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'activity_panel_counts',
			'type'       => 'object',
			'properties' => array(
				'orders_to_fulfill_count'     => array(
					'description' => __( 'Number of orders to fulfill. Null if the underlying sub-request failed.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
				),
				'reviews_to_moderate_count'   => array(
					'description' => __( 'Number of reviews awaiting moderation. Null if the underlying sub-request failed.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
				),
				'products_low_in_stock_count' => array(
					'description' => __( 'Number of products low in stock. Null if the underlying sub-request failed.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}

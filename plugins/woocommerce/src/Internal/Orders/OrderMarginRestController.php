<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller exposing order margin data at wc/v3/orders/{id}/margin.
 *
 * @since 10.8.0
 */
class OrderMarginRestController extends RestApiControllerBase {

	/**
	 * The OrderMarginCalculator instance to use.
	 *
	 * @var OrderMarginCalculator
	 */
	private OrderMarginCalculator $margin_calculator;

	/**
	 * Initialize the instance.
	 *
	 * @internal
	 * @param OrderMarginCalculator $margin_calculator The instance of OrderMarginCalculator to use.
	 */
	final public function init( OrderMarginCalculator $margin_calculator ): void {
		$this->margin_calculator = $margin_calculator;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'order-margin';
	}

	/**
	 * Register the REST API routes handled by this controller.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/margin',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_order_margin' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'edit_shop_orders' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Handle GET wc/v3/orders/{id}/margin.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array|WP_Error The margin data or an error.
	 */
	protected function get_order_margin( WP_REST_Request $request ) {
		$order_id = (int) $request->get_param( 'id' );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return new WP_Error(
				'woocommerce_rest_order_not_found',
				/* translators: %d: order ID */
				sprintf( __( 'Order #%d not found.', 'woocommerce' ), $order_id ),
				array( 'status' => 404 )
			);
		}

		return $this->margin_calculator->get_margin_for_order( $order );
	}
}

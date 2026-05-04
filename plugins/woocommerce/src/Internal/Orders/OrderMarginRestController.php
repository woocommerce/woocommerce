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
}

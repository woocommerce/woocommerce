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

	/**
	 * Get the schema for the order margin response.
	 *
	 * @return array
	 */
	public function get_public_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order-margin',
			'type'       => 'object',
			'properties' => array(
				'net_revenue'  => array(
					'description' => __( 'Order subtotal minus discounts, excluding tax and shipping.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'cogs'         => array(
					'description' => __( 'Total cost of goods sold for the order.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'gross_profit' => array(
					'description' => __( 'Net revenue minus cost of goods sold.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'margin'       => array(
					'description' => __( 'Gross profit as a percentage of net revenue.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'cogs_enabled' => array(
					'description' => __( 'Whether the Cost of Goods Sold feature is currently enabled.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'items'        => array(
					'description' => __( 'Per-line-item margin breakdown.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'item_id'      => array(
								'description' => __( 'Order item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'readonly'    => true,
							),
							'product_id'   => array(
								'description' => __( 'Product ID.', 'woocommerce' ),
								'type'        => 'integer',
								'readonly'    => true,
							),
							'name'         => array(
								'description' => __( 'Line item name.', 'woocommerce' ),
								'type'        => 'string',
								'readonly'    => true,
							),
							'quantity'     => array(
								'description' => __( 'Quantity ordered.', 'woocommerce' ),
								'type'        => 'number',
								'readonly'    => true,
							),
							'revenue'      => array(
								'description' => __( 'Line total after discounts, before tax.', 'woocommerce' ),
								'type'        => 'number',
								'readonly'    => true,
							),
							'cogs'         => array(
								'description' => __( 'Cost of goods sold for this line item.', 'woocommerce' ),
								'type'        => 'number',
								'readonly'    => true,
							),
							'gross_profit' => array(
								'description' => __( 'Revenue minus cost of goods sold.', 'woocommerce' ),
								'type'        => 'number',
								'readonly'    => true,
							),
							'margin'       => array(
								'description' => __( 'Gross profit as a percentage of revenue.', 'woocommerce' ),
								'type'        => 'number',
								'readonly'    => true,
							),
						),
					),
				),
			),
		);
	}
}

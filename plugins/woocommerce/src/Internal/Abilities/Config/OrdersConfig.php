<?php
/**
 * Orders MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Orders MCP tool.
 */
class OrdersConfig {

	/**
	 * Get the configuration array for the orders tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/orders',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Orders_Controller::class,
			'route'              => '/wc/v3/orders',
			'label'              => __( 'Manage orders', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce orders. Use action parameter to list, get, create, update, or delete orders.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'status',
				'customer',
				'product',
				'page',
				'per_page',
				'order',
				'orderby',
				// Essential create/update parameters.
				'billing',
				'shipping',
				'line_items',
				'customer_id',
				'customer_note',
				'payment_method',
				'set_paid',
				'meta_data',
			),
		);
	}
}

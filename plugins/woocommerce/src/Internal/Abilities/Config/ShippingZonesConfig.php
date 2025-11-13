<?php
/**
 * Shipping Zones MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Shipping Zones MCP tool.
 */
class ShippingZonesConfig {

	/**
	 * Get the configuration array for the shipping zones tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/shipping-zones',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Shipping_Zones_Controller::class,
			'route'              => '/wc/v3/shipping/zones',
			'label'              => __( 'Manage shipping zones', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce shipping zones. Use action parameter to list, get, create, update, or delete shipping zones.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list parameters.
				'page',
				'per_page',
				// Essential create/update parameters.
				'name',
				'order',
			),
		);
	}
}

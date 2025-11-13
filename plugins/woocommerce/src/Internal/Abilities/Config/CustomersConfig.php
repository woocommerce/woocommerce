<?php
/**
 * Customers MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Customers MCP tool.
 */
class CustomersConfig {

	/**
	 * Get the configuration array for the customers tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/customers',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Customers_Controller::class,
			'route'              => '/wc/v3/customers',
			'label'              => __( 'Manage customers', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce customers. Use action parameter to list, get, create, update, or delete customers.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'email',
				'role',
				'page',
				'per_page',
				'order',
				'orderby',
				// Essential create/update parameters.
				'first_name',
				'last_name',
				'username',
				'billing',
				'shipping',
				'meta_data',
			),
		);
	}
}

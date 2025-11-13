<?php
/**
 * Coupons MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Coupons MCP tool.
 */
class CouponsConfig {

	/**
	 * Get the configuration array for the coupons tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/coupons',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Coupons_Controller::class,
			'route'              => '/wc/v3/coupons',
			'label'              => __( 'Manage coupons', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce coupons. Use action parameter to list, get, create, update, or delete coupons.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'code',
				'page',
				'per_page',
				// Essential create/update parameters.
				'amount',
				'discount_type',
				'description',
				'date_expires',
				'product_ids',
				'excluded_product_ids',
				'usage_limit',
				'meta_data',
			),
		);
	}
}

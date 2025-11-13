<?php
/**
 * Product Categories MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Product Categories MCP tool.
 */
class ProductCategoriesConfig {

	/**
	 * Get the configuration array for the product categories tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/product-categories',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Product_Categories_Controller::class,
			'route'              => '/wc/v3/products/categories',
			'label'              => __( 'Manage product categories', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce product categories. Use action parameter to list, get, create, update, or delete product categories.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'page',
				'per_page',
				'order',
				'orderby',
				// Essential create/update parameters.
				'name',
				'slug',
				'parent',
				'description',
				'image',
				'meta_data',
			),
		);
	}
}

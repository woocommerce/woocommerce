<?php
/**
 * Products MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Products MCP tool.
 */
class ProductsConfig {

	/**
	 * Get the configuration array for the products tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/products',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Products_Controller::class,
			'route'              => '/wc/v3/products',
			'label'              => __( 'Manage products', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce products. Use action parameter to list, get, create, update, or delete products.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'status',
				'type',
				'sku',
				'category',
				'tag',
				'featured',
				'on_sale',
				'page',
				'per_page',
				'order',
				'orderby',
				// Essential create/update parameters.
				'name',
				'slug',
				'description',
				'short_description',
				'regular_price',
				'sale_price',
				'stock_status',
				'manage_stock',
				'stock_quantity',
				'categories',
				'tags',
				'images',
				'attributes',
				'variations',
				'default_attributes',
				'virtual',
				'downloadable',
				'weight',
				'dimensions',
				'shipping_class',
				'meta_data',
			),
		);
	}
}

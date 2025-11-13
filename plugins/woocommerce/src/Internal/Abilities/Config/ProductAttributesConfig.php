<?php
/**
 * Product Attributes MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Product Attributes MCP tool.
 */
class ProductAttributesConfig {

	/**
	 * Get the configuration array for the product attributes tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/product-attributes',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Product_Attributes_Controller::class,
			'route'              => '/wc/v3/products/attributes',
			'label'              => __( 'Manage product attributes', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce product attributes. Use action parameter to list, get, create, update, or delete product attributes.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list parameters.
				'page',
				'per_page',
				// Essential create/update parameters.
				'name',
				'slug',
				'type',
				'order_by',
				'has_archives',
			),
		);
	}
}

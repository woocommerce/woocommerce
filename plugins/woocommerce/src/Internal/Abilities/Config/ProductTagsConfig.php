<?php
/**
 * Product Tags MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Product Tags MCP tool.
 */
class ProductTagsConfig {

	/**
	 * Get the configuration array for the product tags tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/product-tags',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Product_Tags_Controller::class,
			'route'              => '/wc/v3/products/tags',
			'label'              => __( 'Manage product tags', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce product tags. Use action parameter to list, get, create, update, or delete product tags.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'search',
				'page',
				'per_page',
				// Essential create/update parameters.
				'name',
				'slug',
				'description',
			),
		);
	}
}

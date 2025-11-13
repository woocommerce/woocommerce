<?php
/**
 * Product Reviews MCP tool configuration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration for Product Reviews MCP tool.
 */
class ProductReviewsConfig {

	/**
	 * Get the configuration array for the product reviews tool.
	 *
	 * @return array Configuration array.
	 */
	public static function get_config(): array {
		return array(
			'id'                 => 'woocommerce/product-reviews',
			'operations'         => array( 'list', 'get', 'create', 'update', 'delete' ),
			'controller'         => \WC_REST_Product_Reviews_Controller::class,
			'route'              => '/wc/v3/products/reviews',
			'label'              => __( 'Manage product reviews', 'woocommerce' ),
			'description'        => __( 'Manage WooCommerce product reviews. Use action parameter to list, get, create, update, or delete product reviews.', 'woocommerce' ),
			'allowed_parameters' => array(
				// Core operation parameters.
				'id',
				'action',
				// Essential list/search parameters.
				'product',
				'status',
				'page',
				'per_page',
				// Essential create/update parameters.
				'product_id',
				'review',
				'reviewer',
				'reviewer_email',
				'rating',
			),
		);
	}
}

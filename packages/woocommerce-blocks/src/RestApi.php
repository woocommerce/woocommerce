<?php
/**
 * Registers controllers in the blocks REST API namespace.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * RestApi class.
 */
class RestApi {

	/**
	 * Initialize class features.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes() {
		$controllers = self::get_controllers();

		foreach ( $controllers as $name => $class ) {
			$instance = new $class();
			$instance->register_routes();
		}
	}

	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	protected static function get_controllers() {
		return [
			'product-attributes'      => __NAMESPACE__ . '\RestApi\Controllers\ProductAttributes',
			'product-attribute-terms' => __NAMESPACE__ . '\RestApi\Controllers\ProductAttributeTerms',
			'product-categories'      => __NAMESPACE__ . '\RestApi\Controllers\ProductCategories',
			'product-tags'            => __NAMESPACE__ . '\RestApi\Controllers\ProductTags',
			'products'                => __NAMESPACE__ . '\RestApi\Controllers\Products',
			'variations'              => __NAMESPACE__ . '\RestApi\Controllers\Variations',
		];
	}
}

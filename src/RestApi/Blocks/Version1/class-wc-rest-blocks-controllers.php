<?php
/**
 * Returns controllers in this REST API namespace.
 *
 * @package WooCommerce/RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Controllers class.
 */
class WC_REST_Blocks_Controllers {
	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	public static function get_controllers() {
		return [
			'product-attributes'      => 'WC_REST_Blocks_Product_Attributes_Controller',
			'product-attribute-terms' => 'WC_REST_Blocks_Product_Attribute_Terms_Controller',
			'product-categories'      => 'WC_REST_Blocks_Product_Categories_Controller',
			'products'                => 'WC_REST_Blocks_Products_Controller',
		];
	}
}

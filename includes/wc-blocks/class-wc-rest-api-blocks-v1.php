<?php
/**
 * V1 Blocks API helpers.
 *
 * @package WooCommerce/RestAPI/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Rest_API_Blocks_V1 class.
 */
class WC_Rest_API_Blocks_V1 {

	/**
	 * Include controller classes for this API.
	 */
	public function includes() {
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-posts-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-crud-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-terms-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-blocks-product-attributes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-blocks-product-attribute-terms-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-blocks-product-categories-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-blocks-products-controller.php';
	}

	/**
	 * Get list of controller classes for this API.
	 *
	 * @return array Array of class names.
	 */
	public function get_controllers() {
		return array(
			'WC_REST_Blocks_Product_Attributes_Controller',
			'WC_REST_Blocks_Product_Attribute_Terms_Controller',
			'WC_REST_Blocks_Product_Categories_Controller',
			'WC_REST_Blocks_Products_Controller',
		);
	}
}

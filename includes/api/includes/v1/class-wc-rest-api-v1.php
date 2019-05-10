<?php
/**
 * V1 API helpers.
 *
 * @package WooCommerce/RestAPI/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Rest_API_V1 class.
 */
class WC_Rest_API_V1 {

	/**
	 * Include controller classes for this API.
	 */
	public function includes() {
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-posts-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-crud-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-terms-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-coupons-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customer-downloads-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customers-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-orders-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-notes-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-refunds-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attribute-terms-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attributes-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-categories-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-reviews-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-shipping-classes-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-tags-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-products-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-sales-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-top-sellers-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-reports-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-tax-classes-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-taxes-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-webhook-deliveries-v1-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-webhooks-v1-controller.php';
	}

	/**
	 * Get list of controller classes for this API.
	 *
	 * @return array Array of class names.
	 */
	public function get_controllers() {
		return array(
			'WC_REST_Coupons_V1_Controller',
			'WC_REST_Customer_Downloads_V1_Controller',
			'WC_REST_Customers_V1_Controller',
			'WC_REST_Order_Notes_V1_Controller',
			'WC_REST_Order_Refunds_V1_Controller',
			'WC_REST_Orders_V1_Controller',
			'WC_REST_Product_Attribute_Terms_V1_Controller',
			'WC_REST_Product_Attributes_V1_Controller',
			'WC_REST_Product_Categories_V1_Controller',
			'WC_REST_Product_Reviews_V1_Controller',
			'WC_REST_Product_Shipping_Classes_V1_Controller',
			'WC_REST_Product_Tags_V1_Controller',
			'WC_REST_Products_V1_Controller',
			'WC_REST_Report_Sales_V1_Controller',
			'WC_REST_Report_Top_Sellers_V1_Controller',
			'WC_REST_Reports_V1_Controller',
			'WC_REST_Tax_Classes_V1_Controller',
			'WC_REST_Taxes_V1_Controller',
			'WC_REST_Webhook_Deliveries_V1_Controller',
			'WC_REST_Webhooks_V1_Controller',
		);
	}
}

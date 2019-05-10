<?php
/**
 * V2 API helpers.
 *
 * @package WooCommerce/RestAPI/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Rest_API_V2 class.
 */
class WC_Rest_API_V2 {

	/**
	 * Include controller classes for this API.
	 */
	public function includes() {
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-posts-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-crud-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-terms-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-coupons-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customer-downloads-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customers-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-orders-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-network-orders-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-notes-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-refunds-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attribute-terms-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attributes-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-categories-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-reviews-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-shipping-classes-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-tags-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-products-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-variations-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-sales-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-top-sellers-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-reports-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-settings-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-setting-options-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zones-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zone-locations-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zone-methods-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-tax-classes-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-taxes-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-webhook-deliveries-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-webhooks-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-system-status-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-system-status-tools-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-methods-v2-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-payment-gateways-v2-controller.php';
	}

	/**
	 * Get list of controller classes for this API.
	 *
	 * @return array Array of class names.
	 */
	public function get_controllers() {
		return array(
			'WC_REST_Coupons_V2_Controller',
			'WC_REST_Customer_Downloads_V2_Controller',
			'WC_REST_Customers_V2_Controller',
			'WC_REST_Network_Orders_V2_Controller',
			'WC_REST_Order_Notes_V2_Controller',
			'WC_REST_Order_Refunds_V2_Controller',
			'WC_REST_Orders_V2_Controller',
			'WC_REST_Product_Attribute_Terms_V2_Controller',
			'WC_REST_Product_Attributes_V2_Controller',
			'WC_REST_Product_Categories_V2_Controller',
			'WC_REST_Product_Reviews_V2_Controller',
			'WC_REST_Product_Shipping_Classes_V2_Controller',
			'WC_REST_Product_Tags_V2_Controller',
			'WC_REST_Products_V2_Controller',
			'WC_REST_Product_Variations_V2_Controller',
			'WC_REST_Report_Sales_V2_Controller',
			'WC_REST_Report_Top_Sellers_V2_Controller',
			'WC_REST_Reports_V2_Controller',
			'WC_REST_Settings_V2_Controller',
			'WC_REST_Setting_Options_V2_Controller',
			'WC_REST_Shipping_Zones_V2_Controller',
			'WC_REST_Shipping_Zone_Locations_V2_Controller',
			'WC_REST_Shipping_Zone_Methods_V2_Controller',
			'WC_REST_Tax_Classes_V2_Controller',
			'WC_REST_Taxes_V2_Controller',
			'WC_REST_Webhook_Deliveries_V2_Controller',
			'WC_REST_Webhooks_V2_Controller',
			'WC_REST_System_Status_V2_Controller',
			'WC_REST_System_Status_Tools_V2_Controller',
			'WC_REST_Shipping_Methods_V2_Controller',
			'WC_REST_Payment_Gateways_V2_Controller',
		);
	}
}

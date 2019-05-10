<?php
/**
 * V3 API helpers.
 *
 * @package WooCommerce/RestAPI/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Rest_API_V3 class.
 */
class WC_Rest_API_V3 {

	/**
	 * Include controller classes for this API.
	 */
	public function includes() {
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-posts-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-crud-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-terms-controller.php';
		include_once dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-coupons-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customer-downloads-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-customers-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-orders-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-network-orders-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-notes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-order-refunds-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attribute-terms-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-attributes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-categories-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-reviews-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-shipping-classes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-tags-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-products-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-product-variations-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-reports-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-sales-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-top-sellers-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-orders-totals-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-products-totals-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-customers-totals-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-coupons-totals-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-report-reviews-totals-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-settings-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-setting-options-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zone-locations-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-zone-methods-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-tax-classes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-taxes-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-webhooks-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-system-status-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-system-status-tools-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-shipping-methods-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-payment-gateways-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-data-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-data-continents-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-data-countries-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-rest-data-currencies-controller.php';
	}

	/**
	 * Get list of controller classes for this API.
	 *
	 * @return array Array of class names.
	 */
	public function get_controllers() {
		return array(
			'WC_REST_Coupons_Controller',
			'WC_REST_Customer_Downloads_Controller',
			'WC_REST_Customers_Controller',
			'WC_REST_Network_Orders_Controller',
			'WC_REST_Order_Notes_Controller',
			'WC_REST_Order_Refunds_Controller',
			'WC_REST_Orders_Controller',
			'WC_REST_Product_Attribute_Terms_Controller',
			'WC_REST_Product_Attributes_Controller',
			'WC_REST_Product_Categories_Controller',
			'WC_REST_Product_Reviews_Controller',
			'WC_REST_Product_Shipping_Classes_Controller',
			'WC_REST_Product_Tags_Controller',
			'WC_REST_Products_Controller',
			'WC_REST_Product_Variations_Controller',
			'WC_REST_Report_Sales_Controller',
			'WC_REST_Report_Top_Sellers_Controller',
			'WC_REST_Report_Orders_Totals_Controller',
			'WC_REST_Report_Products_Totals_Controller',
			'WC_REST_Report_Customers_Totals_Controller',
			'WC_REST_Report_Coupons_Totals_Controller',
			'WC_REST_Report_Reviews_Totals_Controller',
			'WC_REST_Reports_Controller',
			'WC_REST_Settings_Controller',
			'WC_REST_Setting_Options_Controller',
			'WC_REST_Shipping_Zones_Controller',
			'WC_REST_Shipping_Zone_Locations_Controller',
			'WC_REST_Shipping_Zone_Methods_Controller',
			'WC_REST_Tax_Classes_Controller',
			'WC_REST_Taxes_Controller',
			'WC_REST_Webhooks_Controller',
			'WC_REST_System_Status_Controller',
			'WC_REST_System_Status_Tools_Controller',
			'WC_REST_Shipping_Methods_Controller',
			'WC_REST_Payment_Gateways_Controller',
			'WC_REST_Data_Controller',
			'WC_REST_Data_Continents_Controller',
			'WC_REST_Data_Countries_Controller',
			'WC_REST_Data_Currencies_Controller',
		);
	}
}

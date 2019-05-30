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
class WC_REST_Controllers_V3 {
	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	public static function get_controllers() {
		return [
			'coupons'                  => 'WC_REST_Coupons_Controller',
			'customer-downloads'       => 'WC_REST_Customer_Downloads_Controller',
			'customers'                => 'WC_REST_Customers_Controller',
			'network-orders'           => 'WC_REST_Network_Orders_Controller',
			'order-notes'              => 'WC_REST_Order_Notes_Controller',
			'order-refunds'            => 'WC_REST_Order_Refunds_Controller',
			'orders'                   => 'WC_REST_Orders_Controller',
			'product-attribute-terms'  => 'WC_REST_Product_Attribute_Terms_Controller',
			'product-attributes'       => 'WC_REST_Product_Attributes_Controller',
			'product-categories'       => 'WC_REST_Product_Categories_Controller',
			'product-reviews'          => 'WC_REST_Product_Reviews_Controller',
			'product-shipping-classes' => 'WC_REST_Product_Shipping_Classes_Controller',
			'product-tags'             => 'WC_REST_Product_Tags_Controller',
			'products'                 => 'WC_REST_Products_Controller',
			'product-variations'       => 'WC_REST_Product_Variations_Controller',
			'reports-sales'            => 'WC_REST_Report_Sales_Controller',
			'reports-top-sellers'      => 'WC_REST_Report_Top_Sellers_Controller',
			'reports-orders-totals'    => 'WC_REST_Report_Orders_Totals_Controller',
			'reports-products-totals'  => 'WC_REST_Report_Products_Totals_Controller',
			'reports-customers-totals' => 'WC_REST_Report_Customers_Totals_Controller',
			'reports-coupons-totals'   => 'WC_REST_Report_Coupons_Totals_Controller',
			'reports-reviews-totals'   => 'WC_REST_Report_Reviews_Totals_Controller',
			'reports'                  => 'WC_REST_Reports_Controller',
			'settings'                 => 'WC_REST_Settings_Controller',
			'settings-options'         => 'WC_REST_Setting_Options_Controller',
			'shipping-zones'           => 'WC_REST_Shipping_Zones_Controller',
			'shipping-zone-locations'  => 'WC_REST_Shipping_Zone_Locations_Controller',
			'shipping-zone-methods'    => 'WC_REST_Shipping_Zone_Methods_Controller',
			'tax-classes'              => 'WC_REST_Tax_Classes_Controller',
			'taxes'                    => 'WC_REST_Taxes_Controller',
			'webhooks'                 => 'WC_REST_Webhooks_Controller',
			'system-status'            => 'WC_REST_System_Status_Controller',
			'system-status-tools'      => 'WC_REST_System_Status_Tools_Controller',
			'shipping-methods'         => 'WC_REST_Shipping_Methods_Controller',
			'payment-gateways'         => 'WC_REST_Payment_Gateways_Controller',
			'data'                     => 'WC_REST_Data_Controller',
			'data-continents'          => 'WC_REST_Data_Continents_Controller',
			'data-countries'           => 'WC_REST_Data_Countries_Controller',
			'data-currencies'          => 'WC_REST_Data_Currencies_Controller',
		];
	}
}

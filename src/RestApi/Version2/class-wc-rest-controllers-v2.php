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
class WC_REST_Controllers_V2 {
	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	public static function get_controllers() {
		return [
			'coupons'                  => 'WC_REST_Coupons_V2_Controller',
			'customer-downloads'       => 'WC_REST_Customer_Downloads_V2_Controller',
			'customers'                => 'WC_REST_Customers_V2_Controller',
			'network-orders'           => 'WC_REST_Network_Orders_V2_Controller',
			'order-notes'              => 'WC_REST_Order_Notes_V2_Controller',
			'order-refunds'            => 'WC_REST_Order_Refunds_V2_Controller',
			'orders'                   => 'WC_REST_Orders_V2_Controller',
			'product-attribute-terms'  => 'WC_REST_Product_Attribute_Terms_V2_Controller',
			'product-attributes'       => 'WC_REST_Product_Attributes_V2_Controller',
			'product-categories'       => 'WC_REST_Product_Categories_V2_Controller',
			'product-reviews'          => 'WC_REST_Product_Reviews_V2_Controller',
			'product-shipping-classes' => 'WC_REST_Product_Shipping_Classes_V2_Controller',
			'product-tags'             => 'WC_REST_Product_Tags_V2_Controller',
			'products'                 => 'WC_REST_Products_V2_Controller',
			'product-variations'       => 'WC_REST_Product_Variations_V2_Controller',
			'reports-sales'            => 'WC_REST_Report_Sales_V2_Controller',
			'reports-top-sellers'      => 'WC_REST_Report_Top_Sellers_V2_Controller',
			'reports'                  => 'WC_REST_Reports_V2_Controller',
			'settings'                 => 'WC_REST_Settings_V2_Controller',
			'settings-options'         => 'WC_REST_Setting_Options_V2_Controller',
			'shipping-zones'           => 'WC_REST_Shipping_Zones_V2_Controller',
			'shipping-zone-locations'  => 'WC_REST_Shipping_Zone_Locations_V2_Controller',
			'shipping-zone-methods'    => 'WC_REST_Shipping_Zone_Methods_V2_Controller',
			'tax-classes'              => 'WC_REST_Tax_Classes_V2_Controller',
			'taxes'                    => 'WC_REST_Taxes_V2_Controller',
			'webhooks'                 => 'WC_REST_Webhooks_V2_Controller',
			'webhook-deliveries'       => 'WC_REST_Webhook_Deliveries_V2_Controller',
			'system-status'            => 'WC_REST_System_Status_V2_Controller',
			'system-status-tools'      => 'WC_REST_System_Status_Tools_V2_Controller',
			'shipping-methods'         => 'WC_REST_Shipping_Methods_V2_Controller',
			'payment-gateways'         => 'WC_REST_Payment_Gateways_V2_Controller',
		];
	}
}

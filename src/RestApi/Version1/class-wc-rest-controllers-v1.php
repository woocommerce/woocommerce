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
class WC_REST_Controllers_V1 {
	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	public static function get_controllers() {
		return [
			'coupons'                  => 'WC_REST_Coupons_V1_Controller',
			'customer-downloads'       => 'WC_REST_Customer_Downloads_V1_Controller',
			'customers'                => 'WC_REST_Customers_V1_Controller',
			'order-notes'              => 'WC_REST_Order_Notes_V1_Controller',
			'order-refunds'            => 'WC_REST_Order_Refunds_V1_Controller',
			'orders'                   => 'WC_REST_Orders_V1_Controller',
			'product-attribute-terms'  => 'WC_REST_Product_Attribute_Terms_V1_Controller',
			'product-attributes'       => 'WC_REST_Product_Attributes_V1_Controller',
			'product-categories'       => 'WC_REST_Product_Categories_V1_Controller',
			'product-reviews'          => 'WC_REST_Product_Reviews_V1_Controller',
			'product-shipping-classes' => 'WC_REST_Product_Shipping_Classes_V1_Controller',
			'product-tags'             => 'WC_REST_Product_Tags_V1_Controller',
			'products'                 => 'WC_REST_Products_V1_Controller',
			'reports-sales'            => 'WC_REST_Report_Sales_V1_Controller',
			'reports-top-sellers'      => 'WC_REST_Report_Top_Sellers_V1_Controller',
			'reports'                  => 'WC_REST_Reports_V1_Controller',
			'tax-classes'              => 'WC_REST_Tax_Classes_V1_Controller',
			'taxes'                    => 'WC_REST_Taxes_V1_Controller',
			'webhooks'                 => 'WC_REST_Webhooks_V1_Controller',
			'webhook-deliveries'       => 'WC_REST_Webhook_Deliveries_V1_Controller',
		];
	}
}

<?php
/**
 * Returns endpoints in this REST API namespace.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * Controllers class.
 */
class Controllers {
	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	public static function get_controllers() {
		$controllers = [
			'coupons'                  => __NAMESPACE__ . '\Controllers\Coupons',
			'customer-downloads'       => __NAMESPACE__ . '\Controllers\CustomerDownloads',
			'customers'                => __NAMESPACE__ . '\Controllers\Customers',
			'data'                     => __NAMESPACE__ . '\Controllers\Data',
			'data-continents'          => __NAMESPACE__ . '\Controllers\Data\Continents',
			'data-countries'           => __NAMESPACE__ . '\Controllers\Data\Countries',
			'data-currencies'          => __NAMESPACE__ . '\Controllers\Data\Currencies',
			'data-download-ips'        => __NAMESPACE__ . '\Controllers\Data\DownloadIPs',
			'leaderboards'             => __NAMESPACE__ . '\Controllers\Leaderboards',
			'network-orders'           => __NAMESPACE__ . '\Controllers\NetworkOrders',
			'order-notes'              => __NAMESPACE__ . '\Controllers\OrderNotes',
			'order-refunds'            => __NAMESPACE__ . '\Controllers\OrderRefunds',
			'orders'                   => __NAMESPACE__ . '\Controllers\Orders',
			'payment-gateways'         => __NAMESPACE__ . '\Controllers\PaymentGateways',
			'product-attributes'       => __NAMESPACE__ . '\Controllers\ProductAttributes',
			'product-attribute-terms'  => __NAMESPACE__ . '\Controllers\ProductAttributeTerms',
			'product-categories'       => __NAMESPACE__ . '\Controllers\ProductCategories',
			'product-reviews'          => __NAMESPACE__ . '\Controllers\ProductReviews',
			'products'                 => __NAMESPACE__ . '\Controllers\Products',
			'product-shipping-classes' => __NAMESPACE__ . '\Controllers\ProductShippingClasses',
			'product-tags'             => __NAMESPACE__ . '\Controllers\ProductTags',
			'product-variations'       => __NAMESPACE__ . '\Controllers\ProductVariations',
			'reports'                  => __NAMESPACE__ . '\Controllers\Reports',
			'settings'                 => __NAMESPACE__ . '\Controllers\Settings',
			'settings-options'         => __NAMESPACE__ . '\Controllers\SettingsOptions',
			'shipping-methods'         => __NAMESPACE__ . '\Controllers\ShippingMethods',
			'shipping-zone-locations'  => __NAMESPACE__ . '\Controllers\ShippingZoneLocations',
			'shipping-zone-methods'    => __NAMESPACE__ . '\Controllers\ShippingZoneMethods',
			'shipping-zones'           => __NAMESPACE__ . '\Controllers\ShippingZones',
			'system-status'            => __NAMESPACE__ . '\Controllers\SystemStatus',
			'system-status-tools'      => __NAMESPACE__ . '\Controllers\SystemStatusTools',
			'tax-classes'              => __NAMESPACE__ . '\Controllers\TaxClasses',
			'taxes'                    => __NAMESPACE__ . '\Controllers\Taxes',
			'webhooks'                 => __NAMESPACE__ . '\Controllers\Webhooks',
		];

		if ( class_exists( 'WC_Admin_Reports_Sync' ) ) {
			$controllers['reports-categories']             = __NAMESPACE__ . '\Controllers\Reports\Categories';
			$controllers['reports-coupons']                = __NAMESPACE__ . '\Controllers\Reports\Coupons';
			$controllers['reports-coupon-stats']           = __NAMESPACE__ . '\Controllers\Reports\CouponStats';
			$controllers['reports-customers']              = __NAMESPACE__ . '\Controllers\Reports\Customers';
			$controllers['reports-customer-stats']         = __NAMESPACE__ . '\Controllers\Reports\CustomerStats';
			$controllers['reports-downloads']              = __NAMESPACE__ . '\Controllers\Reports\Downloads';
			$controllers['reports-download-stats']         = __NAMESPACE__ . '\Controllers\Reports\DownloadStats';
			$controllers['reports-import']                 = __NAMESPACE__ . '\Controllers\Reports\Import';
			$controllers['reports-orders']                 = __NAMESPACE__ . '\Controllers\Reports\Orders';
			$controllers['reports-order-stats']            = __NAMESPACE__ . '\Controllers\Reports\OrderStats';
			$controllers['reports-performance-indicators'] = __NAMESPACE__ . '\Controllers\Reports\PerformanceIndicators';
			$controllers['reports-products']               = __NAMESPACE__ . '\Controllers\Reports\Products';
			$controllers['reports-product-stats']          = __NAMESPACE__ . '\Controllers\Reports\ProductStats';
			$controllers['reports-revenue-stats']          = __NAMESPACE__ . '\Controllers\Reports\RevenueStats';
			$controllers['reports-stock']                  = __NAMESPACE__ . '\Controllers\Reports\Stock';
			$controllers['reports-stock-stats']            = __NAMESPACE__ . '\Controllers\Reports\StockStats';
			$controllers['reports-taxes']                  = __NAMESPACE__ . '\Controllers\Reports\Taxes';
			$controllers['reports-tax-stats']              = __NAMESPACE__ . '\Controllers\Reports\TaxStats';
			$controllers['reports-variations']             = __NAMESPACE__ . '\Controllers\Reports\Variations';
		}

		return $controllers;
	}
}

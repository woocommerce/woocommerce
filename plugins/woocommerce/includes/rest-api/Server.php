<?php
/**
 * Initialize this version of the REST API.
 *
 * @package WooCommerce\RestApi
 */

namespace Automattic\WooCommerce\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\Utilities\SingletonTrait;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Controller as OrderNotesController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\Controller as ShippingZonesController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\Controller as ShippingZoneMethodController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Controller as OrdersController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Controller as RefundsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products\Controller as SettingsProductsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\Controller as ProductsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Controller as PaymentGatewaysController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Controller as OfflinePaymentMethodsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\Controller as CustomersController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Controller as GeneralSettingsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Email\Controller as EmailSettingsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax\Controller as TaxSettingsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails\Controller as EmailsSettingsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Controller as FulfillmentsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Account\Controller as AccountSettingsController;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Server {
	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod -- Not an injection method.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );

		\WC_REST_System_Status_V2_Controller::register_cache_clean();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$container    = wc_get_container();
		$legacy_proxy = $container->get( LegacyProxy::class );
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = $container->has( $controller_class ) ?
					$container->get( $controller_class ) :
					$legacy_proxy->get_instance_of( $controller_class );
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		$namespaces = array(
			'wc/v1'        => wc_rest_should_load_namespace( 'wc/v1' ) ? $this->get_v1_controllers() : array(),
			'wc/v2'        => wc_rest_should_load_namespace( 'wc/v2' ) ? $this->get_v2_controllers() : array(),
			'wc/v3'        => wc_rest_should_load_namespace( 'wc/v3' ) ? $this->get_v3_controllers() : array(),
			'wc-telemetry' => wc_rest_should_load_namespace( 'wc-telemetry' ) ? $this->get_telemetry_controllers() : array(),
		);

		if ( wc_rest_should_load_namespace( 'wc/v4' ) && Features::is_enabled( 'rest-api-v4' ) ) {
			$namespaces['wc/v4'] = $this->get_v4_controllers();
		}

		/**
		 * Filter the list of REST API controllers to load.
		 *
		 * @since 4.5.0
		 * @param array $controllers List of $namespace => $controllers to load.
		 */
		return apply_filters( 'woocommerce_rest_api_get_rest_namespaces', $namespaces );
	}

	/**
	 * List of controllers in the wc/v1 namespace.
	 *
	 * @return array
	 */
	protected function get_v1_controllers() {
		return array(
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
		);
	}

	/**
	 * List of controllers in the wc/v2 namespace.
	 *
	 * @return array
	 */
	protected function get_v2_controllers() {
		return array(
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
		);
	}

	/**
	 * List of controllers in the wc/v3 namespace.
	 *
	 * @return array
	 */
	protected function get_v3_controllers() {
		$controllers = array(
			'coupons'                  => 'WC_REST_Coupons_Controller',
			'customer-downloads'       => 'WC_REST_Customer_Downloads_Controller',
			'customers'                => 'WC_REST_Customers_Controller',
			'layout-templates'         => 'WC_REST_Layout_Templates_Controller',
			'network-orders'           => 'WC_REST_Network_Orders_Controller',
			'order-notes'              => 'WC_REST_Order_Notes_Controller',
			'order-refunds'            => 'WC_REST_Order_Refunds_Controller',
			'orders'                   => 'WC_REST_Orders_Controller',
			'product-attribute-terms'  => 'WC_REST_Product_Attribute_Terms_Controller',
			'product-attributes'       => 'WC_REST_Product_Attributes_Controller',
			'product-categories'       => 'WC_REST_Product_Categories_Controller',
			'product-custom-fields'    => 'WC_REST_Product_Custom_Fields_Controller',
			'product-reviews'          => 'WC_REST_Product_Reviews_Controller',
			'product-shipping-classes' => 'WC_REST_Product_Shipping_Classes_Controller',
			'product-tags'             => 'WC_REST_Product_Tags_Controller',
			'products'                 => 'WC_REST_Products_Controller',
			'product-variations'       => 'WC_REST_Product_Variations_Controller',
			'refunds'                  => 'WC_REST_Refunds_Controller',
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
			'variations'               => 'WC_REST_Variations_Controller',
			'webhooks'                 => 'WC_REST_Webhooks_Controller',
			'system-status'            => 'WC_REST_System_Status_Controller',
			'system-status-tools'      => 'WC_REST_System_Status_Tools_Controller',
			'shipping-methods'         => 'WC_REST_Shipping_Methods_Controller',
			'payment-gateways'         => 'WC_REST_Payment_Gateways_Controller',
			'data'                     => 'WC_REST_Data_Controller',
			'data-continents'          => 'WC_REST_Data_Continents_Controller',
			'data-countries'           => 'WC_REST_Data_Countries_Controller',
			'data-currencies'          => 'WC_REST_Data_Currencies_Controller',
			'paypal-standard'          => 'WC_REST_Paypal_Standard_Controller',
			'paypal-webhooks'          => 'WC_REST_Paypal_Webhooks_Controller',
			'paypal-buttons'           => 'WC_REST_Paypal_Buttons_Controller',
		);

		if ( Features::is_enabled( 'products-catalog-api' ) ) {
			$controllers['products-catalog'] = 'WC_REST_Products_Catalog_Controller';
		}

		return $controllers;
	}

	/**
	 * List of controllers in the wc/v4 namespace.
	 *
	 * @return array
	 */
	protected function get_v4_controllers() {
		return array(
			'fulfillments'              => FulfillmentsController::class,
			'products'                  => ProductsController::class,
			'customers'                 => CustomersController::class,
			'order-notes'               => OrderNotesController::class,
			'shipping-zones'            => ShippingZonesController::class,
			'shipping-zone-method'      => ShippingZoneMethodController::class,
			'orders'                    => OrdersController::class,
			'refunds'                   => RefundsController::class,
			'offline-payment-methods'   => OfflinePaymentMethodsController::class,
			'settings-general'          => GeneralSettingsController::class,
			'settings-email'            => EmailSettingsController::class,
			'settings-emails'           => EmailsSettingsController::class,
			'settings-products'         => SettingsProductsController::class,
			'settings-payment-gateways' => PaymentGatewaysController::class,
			'settings-tax'              => TaxSettingsController::class,
			'settings-account'          => AccountSettingsController::class,
			// This is a wrapper that redirects V4 settings requests to the V3 settings controller.
			'settings'                  => 'WC_REST_Settings_V4_Controller',
		);
	}

	/**
	 * Get instance of a V4 controller.
	 *
	 * @param string $identifier Controller identifier.
	 * @param string $route Route class name.
	 * @return object The instance of the controller.
	 */
	protected function get_v4_controller( $identifier, $route ) {
		if ( isset( $this->controllers['wc/v4'][ $identifier ] ) ) {
			return $this->controllers['wc/v4'][ $identifier ];
		}
		return new $route();
	}

	/**
	 * List of controllers in the telemetry namespace.
	 *
	 * @return array
	 */
	protected function get_telemetry_controllers() {
		return array(
			'tracker' => 'WC_REST_Telemetry_Controller',
		);
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}
}

<?php
/**
 * Initialize this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Utilities\SingletonTrait;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class RestApi {
	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $endpoints = [];

	/**
	 * REST API package version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Return REST API package version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Set REST API package version.
	 *
	 * @param string $version Version to set.
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace ) {
			$this->endpoints[ $namespace ] = [];

			foreach ( $this->get_rest_controllers( $namespace ) as $name => $class ) {
				$this->endpoints[ $namespace ][ $class ] = new $class();
				$this->endpoints[ $namespace ][ $class ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array
	 */
	protected function get_rest_namespaces() {
		return [
			'wc/v1',
			'wc/v2',
			'wc/v3',
			'wc-blocks/v1',
		];
	}

	/**
	 * Get API controllers - new controllers/endpoints should be registered here.
	 *
	 * @param string $namespace Namespace to get controllers for.
	 * @return array
	 */
	protected function get_rest_controllers( $namespace ) {
		switch ( $namespace ) {
			case 'wc/v1':
				return [
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
				];
			case 'wc/v2':
				return [
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
				];
			case 'wc/v3':
				return [
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
				];
			case 'wc-blocks/v1':
				return [
					'WC_REST_Blocks_Product_Attributes_Controller',
					'WC_REST_Blocks_Product_Attribute_Terms_Controller',
					'WC_REST_Blocks_Product_Categories_Controller',
					'WC_REST_Blocks_Products_Controller',
				];
		}
		return array();
	}
}

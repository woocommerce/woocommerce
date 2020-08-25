<?php
/**
 * REST API bootstrap.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Loader;

/**
 * Init class.
 */
class Init {
	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Boostrap REST API.
	 */
	public function __construct() {
		// Hook in data stores.
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'add_data_stores' ) );
		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		// Add currency symbol to orders endpoint response.
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'add_currency_symbol_to_order_response' ) );
	}

	/**
	 * Init REST API.
	 */
	public function rest_api_init() {
		$controllers = array(
			'Automattic\WooCommerce\Admin\API\Notes',
			'Automattic\WooCommerce\Admin\API\NoteActions',
			'Automattic\WooCommerce\Admin\API\Coupons',
			'Automattic\WooCommerce\Admin\API\Customers',
			'Automattic\WooCommerce\Admin\API\Data',
			'Automattic\WooCommerce\Admin\API\DataCountries',
			'Automattic\WooCommerce\Admin\API\DataDownloadIPs',
			'Automattic\WooCommerce\Admin\API\Leaderboards',
			'Automattic\WooCommerce\Admin\API\Marketing',
			'Automattic\WooCommerce\Admin\API\MarketingOverview',
			'Automattic\WooCommerce\Admin\API\Options',
			'Automattic\WooCommerce\Admin\API\Orders',
			'Automattic\WooCommerce\Admin\API\Products',
			'Automattic\WooCommerce\Admin\API\ProductCategories',
			'Automattic\WooCommerce\Admin\API\ProductVariations',
			'Automattic\WooCommerce\Admin\API\ProductReviews',
			'Automattic\WooCommerce\Admin\API\ProductVariations',
			'Automattic\WooCommerce\Admin\API\Reports\Controller',
			'Automattic\WooCommerce\Admin\API\SettingOptions',
			'Automattic\WooCommerce\Admin\API\Reports\Import\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Export\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Products\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Variations\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Products\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Revenue\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Orders\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Categories\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Taxes\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Coupons\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Stock\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Downloads\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Customers\Controller',
			'Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\Controller',
			'Automattic\WooCommerce\Admin\API\Taxes',
			'Automattic\WooCommerce\Admin\API\Themes',
			'Automattic\WooCommerce\Admin\API\Plugins',
		);

		if ( Loader::is_onboarding_enabled() ) {
			$controllers = array_merge(
				$controllers,
				array(
					'Automattic\WooCommerce\Admin\API\OnboardingProfile',
					'Automattic\WooCommerce\Admin\API\OnboardingTasks',
					'Automattic\WooCommerce\Admin\API\OnboardingThemes',
				)
			);
		}

		// The performance indicators controller must be registered last, after other /stats endpoints have been registered.
		$controllers[] = 'Automattic\WooCommerce\Admin\API\Reports\PerformanceIndicators\Controller';

		$controllers = apply_filters( 'woocommerce_admin_rest_controllers', $controllers );

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Adds data stores.
	 *
	 * @param array $data_stores List of data stores.
	 * @return array
	 */
	public static function add_data_stores( $data_stores ) {
		return array_merge(
			$data_stores,
			array(
				'report-revenue-stats'   => 'Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore',
				'report-orders'          => 'Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore',
				'report-orders-stats'    => 'Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore',
				'report-products'        => 'Automattic\WooCommerce\Admin\API\Reports\Products\DataStore',
				'report-variations'      => 'Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore',
				'report-products-stats'  => 'Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore',
				'report-categories'      => 'Automattic\WooCommerce\Admin\API\Reports\Categories\DataStore',
				'report-taxes'           => 'Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore',
				'report-taxes-stats'     => 'Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore',
				'report-coupons'         => 'Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore',
				'report-coupons-stats'   => 'Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\DataStore',
				'report-downloads'       => 'Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore',
				'report-downloads-stats' => 'Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\DataStore',
				'admin-note'             => 'Automattic\WooCommerce\Admin\Notes\DataStore',
				'report-customers'       => 'Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore',
				'report-customers-stats' => 'Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore',
				'report-stock-stats'     => 'Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore',
			)
		);
	}

	/**
	 * Add the currency symbol (in addition to currency code) to each Order
	 * object in REST API responses. For use in formatAmount().
	 *
	 * @param {WP_REST_Response} $response REST response object.
	 * @returns {WP_REST_Response}
	 */
	public static function add_currency_symbol_to_order_response( $response ) {
		$response_data                    = $response->get_data();
		$currency_code                    = $response_data['currency'];
		$currency_symbol                  = get_woocommerce_currency_symbol( $currency_code );
		$response_data['currency_symbol'] = html_entity_decode( $currency_symbol );
		$response->set_data( $response_data );

		return $response;
	}
}

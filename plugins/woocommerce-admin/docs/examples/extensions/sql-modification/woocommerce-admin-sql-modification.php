<?php
/**
 * Plugin Name: WooCommerce Admin SQL modification Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Make the currency settings available to the javascript client using
 * AssetDataRegistry, available in WooCommerce 3.9.
 *
 * The add_currency_settings function is a most basic example, but below is
 * a more elaborate example of how one might use AssetDataRegistry in classes.
 *
	```php
	<?php

	class MyClassWithAssetData {
		private $asset_data_registry;
		public function __construct( Automattic\WooCommerce\Blocks\AssetDataRegistry $asset_data_registry ) {
			$this->asset_data_registry = $asset_data_registry;
		}

		protected function some_method_adding_assets() {
			$this->asset_data_registry->add( 'myData', [ 'foo' => 'bar' ] );
		}
	}

	// somewhere in the extensions bootstrap
	class Bootstrap {
		protected $container;
		public function __construct( Automattic\WooCommerce\Blocks\Container $container ) {
			$this->container = $container;
			$this->container->register( MyClassWithAssetData::class, function( $blocks_container ) => {
				return new MyClassWithAssetData( $blocks_container->get( Automattic\WooCommerce\Blocks\AssetDataRegistry::class ) );
			} );
		}
	}

	// now anywhere MyClassWithAssetData is instantiated it will automatically be
	// constructed with the AssetDataRegistry
	```
 */
function add_currency_settings() {
	$currencies = array(
		array(
			'label' => __( 'United States Dollar', 'woocommerce-admin' ),
			'value' => 'USD',
		),
		array(
			'label' => __( 'New Zealand Dollar', 'woocommerce-admin' ),
			'value' => 'NZD',
		),
		array(
			'label' => __( 'South African Rand', 'woocommerce-admin' ),
			'value' => 'ZAR',
		),
	);

	$data_registry = Automattic\WooCommerce\Blocks\Package::container()->get(
		Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
	);

	$data_registry->add( 'multiCurrency', $currencies );
}

/**
 * Register the JS.
 */
function add_report_register_script() {

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ) {
		return;
	}

	add_currency_settings();

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'sql-modification',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'sql-modification' );
}
add_action( 'admin_enqueue_scripts', 'add_report_register_script' );

/**
 * Add the query argument `currency` for caching purposes. Otherwise, a
 * change of the currency will return the previous request's data.
 *
 * @param array $args query arguments.
 * @return array augmented query arguments.
 */
function apply_currency_arg( $args ) {
	$currency = 'USD';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['currency'] ) ) {
		$currency = sanitize_text_field( wp_unslash( $_GET['currency'] ) );
	}
	// phpcs:enable

	$args['currency'] = $currency;

	return $args;
}

add_filter( 'woocommerce_analytics_revenue_query_args', 'apply_currency_arg' );

add_filter( 'woocommerce_analytics_orders_query_args', 'apply_currency_arg' );
add_filter( 'woocommerce_analytics_orders_stats_query_args', 'apply_currency_arg' );

add_filter( 'woocommerce_analytics_products_query_args', 'apply_currency_arg' );
add_filter( 'woocommerce_analytics_products_stats_query_args', 'apply_currency_arg' );

add_filter( 'woocommerce_analytics_categories_query_args', 'apply_currency_arg' );

add_filter( 'woocommerce_analytics_coupons_query_args', 'apply_currency_arg' );
add_filter( 'woocommerce_analytics_coupons_stats_query_args', 'apply_currency_arg' );

add_filter( 'woocommerce_analytics_taxes_query_args', 'apply_currency_arg' );
add_filter( 'woocommerce_analytics_taxes_stats_query_args', 'apply_currency_arg' );

/**
 * Add a JOIN clause.
 *
 * @param array $clauses an array of JOIN query strings.
 * @return array augmented clauses.
 */
function add_join_subquery( $clauses ) {
	global $wpdb;

	$clauses[] = "JOIN {$wpdb->postmeta} currency_postmeta ON {$wpdb->prefix}wc_order_stats.order_id = currency_postmeta.post_id";

	return $clauses;
}

add_filter( 'woocommerce_analytics_clauses_join_orders_subquery', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_orders_stats_total', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_orders_stats_interval', 'add_join_subquery' );

add_filter( 'woocommerce_analytics_clauses_join_products_subquery', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_products_stats_total', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_products_stats_interval', 'add_join_subquery' );

add_filter( 'woocommerce_analytics_clauses_join_categories_subquery', 'add_join_subquery' );

add_filter( 'woocommerce_analytics_clauses_join_coupons_subquery', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_coupons_stats_total', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_coupons_stats_interval', 'add_join_subquery' );

add_filter( 'woocommerce_analytics_clauses_join_taxes_subquery', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_taxes_stats_total', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_taxes_stats_interval', 'add_join_subquery' );

/**
 * Add a WHERE clause.
 *
 * @param array $clauses an array of WHERE query strings.
 * @return array augmented clauses.
 */
function add_where_subquery( $clauses ) {
	$currency = 'USD';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['currency'] ) ) {
		$currency = sanitize_text_field( wp_unslash( $_GET['currency'] ) );
	}
	// phpcs:enable

	$clauses[] = "AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = '{$currency}'";

	return $clauses;
}
add_filter( 'woocommerce_analytics_clauses_where_orders_subquery', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_orders_stats_total', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_orders_stats_interval', 'add_where_subquery' );

add_filter( 'woocommerce_analytics_clauses_where_products_subquery', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_products_stats_total', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_products_stats_interval', 'add_where_subquery' );

add_filter( 'woocommerce_analytics_clauses_where_categories_subquery', 'add_where_subquery' );

add_filter( 'woocommerce_analytics_clauses_where_coupons_subquery', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_coupons_stats_total', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_coupons_stats_interval', 'add_where_subquery' );

add_filter( 'woocommerce_analytics_clauses_where_taxes_subquery', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_taxes_stats_total', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_taxes_stats_interval', 'add_where_subquery' );

/**
 * Add a SELECT clause.
 *
 * @param array $clauses an array of WHERE query strings.
 * @return array augmented clauses.
 */
function add_select_subquery( $clauses ) {
	$clauses[] = ', currency_postmeta.meta_value AS currency';

	return $clauses;
}

add_filter( 'woocommerce_analytics_clauses_select_orders_subquery', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_orders_stats_total', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_orders_stats_interval', 'add_select_subquery' );

add_filter( 'woocommerce_analytics_clauses_select_products_subquery', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_products_stats_total', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_products_stats_interval', 'add_select_subquery' );

add_filter( 'woocommerce_analytics_clauses_select_categories_subquery', 'add_select_subquery' );

add_filter( 'woocommerce_analytics_clauses_select_coupons_subquery', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_coupons_stats_total', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_coupons_stats_interval', 'add_select_subquery' );

add_filter( 'woocommerce_analytics_clauses_select_taxes_subquery', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_taxes_stats_total', 'add_select_subquery' );
add_filter( 'woocommerce_analytics_clauses_select_taxes_stats_interval', 'add_select_subquery' );

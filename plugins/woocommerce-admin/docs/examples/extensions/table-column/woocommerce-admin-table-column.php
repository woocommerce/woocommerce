<?php
/**
 * Plugin Name: WooCommerce Admin Table Column Example
 *
 * @package WC_Admin
 */

/**
 * Register the JS.
 */
function table_column_register_script() {

	if ( ! class_exists( 'WC_Admin_Loader' ) || ! WC_Admin_Loader::is_admin_page() ) {
		return;
	}

	wp_register_script(
		'table_column',
		plugins_url( '/dist/index.js', __FILE__ ),
		array(
			'wp-hooks',
			'wp-element',
			'wp-i18n',
			'wc-components',
		),
		filemtime( dirname( __FILE__ ) . '/dist/index.js' ),
		true
	);

	wp_enqueue_script( 'table_column' );
}
add_action( 'admin_enqueue_scripts', 'table_column_register_script' );

/**
 * Extended attributes can be used to obtain any attribute from a WC_Product instance that is
 * available by a `get_*` class method. In other words, we can add `average_rating` because
 * `get_average_rating` is an available method on a WC_Product instance.
 *
 * @param array $extended_attributes - Extra information from WC_Product instance.
 * @return array - Extended attributes.
 */
function add_product_extended_attributes( $extended_attributes ) {
	$extended_attributes[] = 'average_rating';
	return $extended_attributes;
}
add_filter( 'woocommerce_rest_reports_products_extended_attributes', 'add_product_extended_attributes' );





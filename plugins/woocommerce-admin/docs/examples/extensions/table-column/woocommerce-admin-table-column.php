<?php
/**
 * Plugin Name: WooCommerce Admin Table Column Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function table_column_register_script() {

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ) {
		return;
	}

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'table_column',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'table_column' );
}
add_action( 'admin_enqueue_scripts', 'table_column_register_script' );

/**
 * When adding fields to a REST API response, we need to update the schema
 * to reflect these changes. Not only does this let API consumers know of
 * the new fields, it also adds them to Report Exports.
 *
 * @param array $properties The endpoint item schema properties.
 * @return array Filtered schema.
 */
function add_product_extended_attributes_schema( $properties ) {
	$properties['extended_info']['average_rating'] = array(
		'type'        => 'number',
		'readonly'    => true,
		'context'     => array( 'view', 'edit' ),
		'description' => 'Average product rating.',
	);

	return $properties;
}

add_filter( 'woocommerce_rest_report_products_schema', 'add_product_extended_attributes_schema' );

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





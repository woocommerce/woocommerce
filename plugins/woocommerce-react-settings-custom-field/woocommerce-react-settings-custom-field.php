<?php
/**
 * Plugin Name: WooCommerce React Settings Custom Field Example
 * Description: Adds a custom "incentive_field" field type and React renderer.
 * Version: 0.1.0
 * Author: WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add a custom field to General settings.
 *
 * @param array  $settings Settings definitions.
 * @param string $section_id Section id.
 * @return array
 */
function wc_rs_custom_field_add_setting( array $settings, string $section_id ): array {
	if ( '' !== $section_id ) {
		return $settings;
	}

	$settings[] = array(
		'title' => __( 'React custom field type', 'woocommerce' ),
		'type'  => 'title',
		'id'    => 'wc_rs_custom_field_group',
	);

	$settings[] = array(
		'title'   => __( 'Incentive field', 'woocommerce' ),
		'id'      => 'wc_rs_incentive_field',
		'type'    => 'incentive_field',
		'default' => __( 'Hello from WooCommerce!', 'woocommerce' ),
	);

	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'wc_rs_custom_field_group',
	);

	return $settings;
}
add_filter( 'woocommerce_get_settings_general', 'wc_rs_custom_field_add_setting', 10, 2 );

/**
 * Allow the custom field type in React settings.
 *
 * @param array $types Supported types.
 * @return array
 */
function wc_rs_custom_field_supported_types( array $types ): array {
	$types[] = 'incentive_field';
	return $types;
}
add_filter( 'woocommerce_react_settings_supported_types', 'wc_rs_custom_field_supported_types', 10, 1 );

/**
 * Map the custom field type to itself.
 *
 * @param array $map Type map.
 * @return array
 */
function wc_rs_custom_field_type_map( array $map ): array {
	$map['incentive_field'] = 'incentive_field';
	return $map;
}
add_filter( 'woocommerce_react_settings_type_map', 'wc_rs_custom_field_type_map', 10, 1 );

/**
 * Load the React renderer in admin.
 *
 * @param string $hook_suffix Current admin screen id.
 */
function wc_rs_custom_field_admin_script( string $hook_suffix ) {
	if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'wc-rs-custom-field-style',
		plugins_url( 'custom-field.css', __FILE__ ),
		array(),
		'0.1.0'
	);

	wp_enqueue_script(
		'wc-rs-custom-field',
		plugins_url( 'custom-field.js', __FILE__ ),
		array( 'wc-admin-settings-embed' ),
		'0.1.0',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'wc_rs_custom_field_admin_script' );

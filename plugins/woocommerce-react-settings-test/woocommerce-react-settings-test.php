<?php
/**
 * Plugin Name: WooCommerce React Settings Test
 * Description: Adds sample fields to WooCommerce General settings for React settings testing.
 * Version: 0.1.0
 * Author: WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add test fields to WooCommerce General settings.
 *
 * @param array  $settings Settings definitions.
 * @param string $section_id Section id.
 * @return array
 */
function wc_react_settings_test_add_general_fields( array $settings, string $section_id ): array {
	if ( '' !== $section_id ) {
		return $settings;
	}

	$settings[] = array(
		'title' => __( 'React settings test fields', 'woocommerce' ),
		'type'  => 'title',
		'id'    => 'wc_react_settings_test_fields',
	);

	$settings[] = array(
		'title'   => __( 'Test text', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_text',
		'type'    => 'text',
		'default' => '',
	);

	$settings[] = array(
		'title'   => __( 'Test textarea', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_textarea',
		'type'    => 'textarea',
		'default' => '',
	);

	$settings[] = array(
		'title'   => __( 'Test checkbox', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_checkbox',
		'type'    => 'checkbox',
		'default' => 'no',
	);

	$settings[] = array(
		'title'   => __( 'Test select', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_select',
		'type'    => 'select',
		'default' => 'one',
		'options' => array(
			'one' => __( 'Option one', 'woocommerce' ),
			'two' => __( 'Option two', 'woocommerce' ),
		),
	);

	$settings[] = array(
		'title'   => __( 'Test multiselect', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_multiselect',
		'type'    => 'multiselect',
		'default' => array( 'one' ),
		'options' => array(
			'one'   => __( 'Option one', 'woocommerce' ),
			'two'   => __( 'Option two', 'woocommerce' ),
			'three' => __( 'Option three', 'woocommerce' ),
		),
	);

	$settings[] = array(
		'title'   => __( 'Test country multiselect', 'woocommerce' ),
		'id'      => 'wc_react_settings_test_countries',
		'type'    => 'multi_select_countries',
		'default' => array( 'US', 'GB' ),
	);

	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'wc_react_settings_test_fields',
	);

	return $settings;
}
add_filter( 'woocommerce_get_settings_general', 'wc_react_settings_test_add_general_fields', 10, 2 );

/**
 * Opt out of React settings rendering for General settings.
 *
 * @param bool   $opt_out Whether to opt out of React rendering.
 * @param string $tab Tab id.
 * @param string $section Section id.
 * @return bool
 */
function wc_react_settings_test_opt_out( bool $opt_out, string $tab, string $section ): bool {
	if ( 'general' === $tab && '' === $section ) {
		return true;
	}

	return $opt_out;
}
add_filter( 'woocommerce_react_settings_opt_out', 'wc_react_settings_test_opt_out', 10, 5 );

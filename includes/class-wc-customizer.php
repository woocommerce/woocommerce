<?php
/**
 * Adds options to the customizer for WooCommerce.
 *
 * @version 3.3.0
 * @package WooCommerce
 * @author  WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Customizer class.
 */
class WC_Customizer {

	public function __construct() {
		add_action( 'customize_register', array( $this, 'add_sections' ) );
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_sections( $wp_customize ) {
		$wp_customize->add_section(
			'woocommerce_display_settings',
			array(
				'title'       => __( 'WooCommerce', 'woocommerce' ),
				'description' => __( 'These settings control how some parts of WooCommerce appear in your store.', 'woocommerce' ),
				'priority'    => 140,
			)
		);

		/**
		 * Store Notice settings.
		 */
		$wp_customize->add_setting(
			'woocommerce_demo_store',
			array(
				'default'              => 'no',
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'wc_bool_to_string',
				'sanitize_js_callback' => 'wc_string_to_bool',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_demo_store_notice',
			array(
				'default'           => __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'woocommerce_demo_store_notice',
			array(
				'label'       => __( 'Store notice', 'woocommerce' ),
				'description' => __( "If enabled, this will be shown site-wide. It can be used to inform visitors about store events and promotions.", 'woocommerce' ),
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'woocommerce_demo_store_notice',
				'type'        => 'textarea',
			)
		);

		$wp_customize->add_control(
			'woocommerce_demo_store',
			array(
				'label'       => __( 'Enable store notice', 'woocommerce' ),
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'woocommerce_demo_store',
				'type'        => 'checkbox',
			)
		);

		/**
		 * Grid settings.
		 */
	}


}

new WC_Customizer();

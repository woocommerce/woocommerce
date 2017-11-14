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
				'title'    => __( 'WooCommerce', 'woocommerce' ),
				'priority' => 1000,
			)
		);

		$wp_customize->add_setting(
			'woocommerce_demo_store_notice',
			array(
				'default'           => __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'control_name',
			array(
				'label'       => __( 'Store notice', 'mytheme' ),
				'description' => __( 'Shown at the top of your store above the header and visible to customers.', 'twentyfifteen' ),
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'woocommerce_demo_store_notice',
				'type'        => 'textarea',
			)
		);
	}


}

new WC_Customizer();

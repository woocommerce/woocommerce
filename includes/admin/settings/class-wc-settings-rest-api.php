<?php
/**
 * WooCommerce API Settings
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings for API.
 */
if ( class_exists( 'WC_Settings_Rest_API', false ) ) {
	return new WC_Settings_Rest_API();
}

/**
 * WC_Settings_Rest_API.
 */
class WC_Settings_Rest_API extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'api';
		$this->label = __( 'API', 'woocommerce' );

		add_action( 'woocommerce_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
		parent::__construct();

		$this->notices();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Settings', 'woocommerce' ),
			'keys'     => __( 'Keys/Apps', 'woocommerce' ),
			'webhooks' => __( 'Webhooks', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section slug.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = apply_filters( 'woocommerce_settings_rest_api', array(
				array(
					'title' => __( 'General options', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options',
				),

				array(
					'title'   => __( 'API', 'woocommerce' ),
					'desc'    => __( 'Enable the REST API', 'woocommerce' ),
					'id'      => 'woocommerce_api_enabled',
					'type'    => 'checkbox',
					'default' => 'yes',
				),

				array(
					'type' => 'sectionend',
					'id' => 'general_options',
				),
			) );
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Form method.
	 *
	 * @param  string $method Method name.
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		global $current_section;

		if ( 'keys' === $current_section ) {
			if ( isset( $_GET['create-key'] ) || isset( $_GET['edit-key'] ) ) { // WPCS: input var okay, CSRF ok.
				return 'post';
			}

			return 'get';
		}

		return 'post';
	}

	/**
	 * Notices.
	 */
	private function notices() {
		if ( isset( $_GET['section'] ) && 'webhooks' === $_GET['section'] ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Webhooks::notices();
		}
		if ( isset( $_GET['section'] ) && 'keys' === $_GET['section'] ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_API_Keys::notices();
		}
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		if ( 'webhooks' === $current_section ) {
			WC_Admin_Webhooks::page_output();
		} elseif ( 'keys' === $current_section ) {
			WC_Admin_API_Keys::page_output();
		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( apply_filters( 'woocommerce_rest_api_valid_to_save', ! in_array( $current_section, array( 'keys', 'webhooks' ), true ) ) ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
		}
	}
}

return new WC_Settings_Rest_API();

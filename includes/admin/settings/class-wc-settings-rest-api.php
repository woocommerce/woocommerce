<?php
/**
 * WooCommerce REST API Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_REST_API' ) ) :

/**
 * WC_Settings_REST_API
 */
class WC_Settings_REST_API extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'api';
		$this->label = __( 'API', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''    => __( 'API Options', 'woocommerce' ),
			'log' => __( 'Log', 'woocommerce' )
		);

		return $sections;
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

 		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Get settings array
	 *
	 * @param string $current_section
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		if ( $current_section == 'log' ) {

			// TODO: implement log display

		} else {

			return apply_filters( 'woocommerce_api_settings', array(

				array(	'title' => __( 'General Options', 'woocommerce' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

				array(
					'title'   => __( 'Enable API', 'woocommerce' ),
					'id'      => 'woocommerce_api_enabled',
					'type'    => 'checkbox',
					'default' => 'yes',
				),

				array(
					'title'   => __( 'Allow read-only public access to Products endpoint', 'woocommerce' ),
					'desc'    => __( 'This enables read-only public access to the products endpoint', 'woocommerce' ),
					'id'      => 'woocommerce_api_public_products_endpoint',
					'type'    => 'checkbox',
					'default' => 'no',
				),

				array( 'type' => 'sectionend', 'id' => 'general_options' ),

				array(	'title' => __( 'API Keys', 'woocommerce' ), 'type' => 'title', 'id' => 'api_key_options' ),

				// TODO: implement key management here

				array( 'type' => 'sectionend', 'id' => 'digital_download_options' ),

			));
		}
	}
}

endif;

return new WC_Settings_REST_API();

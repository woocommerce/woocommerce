<?php
/**
 * WooCommerce Shipping Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Shipping' ) ) :

/**
 * WC_Settings_Shipping.
 */
class WC_Settings_Shipping extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shipping';
		$this->label = __( 'Shipping', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Shipping Options', 'woocommerce' )
		);

		if ( ! defined( 'WC_INSTALLING' ) ) {
			// Load shipping methods so we can show any global options they may have
			$shipping_methods = WC()->shipping->load_shipping_methods();

			foreach ( $shipping_methods as $method ) {
				if ( ! $method->has_settings() ) {
					continue;
				}
				$title = empty( $method->method_title ) ? ucfirst( $method->id ) : $method->method_title;
				$sections[ strtolower( get_class( $method ) ) ] = esc_html( $title );
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = apply_filters('woocommerce_shipping_settings', array(

			array( 'title' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'id' => 'shipping_options' ),

			array(
				'title'         => __( 'Shipping Calculations', 'woocommerce' ),
				'desc'          => __( 'Enable shipping', 'woocommerce' ),
				'id'            => 'woocommerce_calc_shipping',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start'
			),

			array(
				'desc'          => __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
				'id'            => 'woocommerce_enable_shipping_calc',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false
			),

			array(
				'desc'          => __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
				'id'            => 'woocommerce_shipping_cost_requires_address',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false
			),

			array(
				'title'   => __( 'Shipping Destination', 'woocommerce' ),
				'desc'    => __( 'This controls which shipping address is used by default.', 'woocommerce' ),
				'id'      => 'woocommerce_ship_to_destination',
				'default' => 'billing',
				'type'    => 'radio',
				'options' => array(
					'shipping'     => __( 'Default to shipping address', 'woocommerce' ),
					'billing'      => __( 'Default to billing address', 'woocommerce' ),
					'billing_only' => __( 'Only ship to the customer\'s billing address', 'woocommerce' ),
				),
				'autoload'        => false,
				'desc_tip'        =>  true,
				'show_if_checked' => 'option',
			),

			array(
				'title'    => __( 'Restrict shipping to Location(s)', 'woocommerce' ),
				'desc'     => sprintf( __( 'Choose which countries you want to ship to, or choose to ship to all <a href="%s">locations you sell to</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ),
				'id'       => 'woocommerce_ship_to_countries',
				'default'  => '',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'desc_tip' => false,
				'options'  => array(
					''         => __( 'Ship to all countries you sell to', 'woocommerce' ),
					'all'      => __( 'Ship to all countries', 'woocommerce' ),
					'specific' => __( 'Ship to specific countries only', 'woocommerce' )
				)
			),

			array(
				'title'   => __( 'Specific Countries', 'woocommerce' ),
				'desc'    => '',
				'id'      => 'woocommerce_specific_ship_to_countries',
				'css'     => '',
				'default' => '',
				'type'    => 'multi_select_countries'
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Load shipping methods so we can show any global options they may have
		$shipping_methods = WC()->shipping->load_shipping_methods();

		if ( $current_section ) {

 			foreach ( $shipping_methods as $method ) {

				if ( strtolower( get_class( $method ) ) == strtolower( $current_section ) && $method->has_settings() ) {
					$method->admin_options();
					break;
				}
			}
 		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$wc_shipping = WC_Shipping::instance();

		if ( ! $current_section ) {
			WC_Admin_Settings::save_fields( $this->get_settings() );

		} else {
			foreach ( $wc_shipping->get_shipping_methods() as $method_id => $method ) {
				if ( $current_section === sanitize_title( get_class( $method ) ) ) {
					do_action( 'woocommerce_update_options_' . $this->id . '_' . $method->id );
				}
			}
		}

		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

endif;

return new WC_Settings_Shipping();

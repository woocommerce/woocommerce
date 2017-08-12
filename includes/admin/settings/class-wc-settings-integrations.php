<?php
/**
 * WooCommerce Integration Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Integrations', false ) ) :

/**
 * WC_Settings_Integrations.
 */
class WC_Settings_Integrations extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'integration';
		$this->label = __( 'Integration', 'woocommerce' );

		if ( isset( WC()->integrations ) && WC()->integrations->get_integrations() ) {
			parent::__construct();
		}
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		global $current_section;

		$sections = array();

		if ( ! defined( 'WC_INSTALLING' ) ) {
			$integrations = WC()->integrations->get_integrations();

			if ( ! $current_section && ! empty( $integrations ) ) {
				$current_section = current( $integrations )->id;
			}

			if ( sizeof( $integrations ) > 1 ) {
				foreach ( $integrations as $integration ) {
					$title = empty( $integration->method_title ) ? ucfirst( $integration->id ) : $integration->method_title;
					$sections[ strtolower( $integration->id ) ] = esc_html( $title );
				}
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$integrations = WC()->integrations->get_integrations();

		if ( isset( $integrations[ $current_section ] ) ) {
			$integrations[ $current_section ]->admin_options();
		}
	}
}

endif;

return new WC_Settings_Integrations();

<?php
/**
 * WooCommerce site visibility settings
 *
 * @package  WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings for API.
 */
if ( class_exists( 'WC_Settings_Site_Visibility', false ) ) {
	return new WC_Settings_Site_Visibility();
}

/**
 * WC_Settings_Advanced.
 */
class WC_Settings_Site_Visibility extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'site-visibility';
		$this->label = __( 'Site visibility', 'woocommerce' );

		parent::__construct();
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''            => __( 'General', 'woocommerce' ),
			'custom_view' => __( 'Custom View', 'woocommerce' ),
		);
	}


	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		$settings =
			array(
				array(
					'id'   => 'wc_settings_site_visibility_slotfill',
					'type' => 'slotfill_placeholder',
				),
			);

		return $settings;
	}

	/**
	 * Get settings for the Custom View section.
	 *
	 * @return array
	 */
	protected function get_settings_for_custom_view_section() {
		$settings =
			array(
				array(
					'id'   => 'wc_site_visibility_settings_view',
					'type' => 'slotfill_placeholder',
				),
			);

		return $settings;
	}
}


return new WC_Settings_Site_Visibility();

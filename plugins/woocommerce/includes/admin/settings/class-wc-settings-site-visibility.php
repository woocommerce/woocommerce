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
		$this->label = __( 'Site Visibility', 'woocommerce' );

		parent::__construct();
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
					'id'   => 'wc_settings_general_site_visibility_slotfill',
					'type' => 'slotfill_placeholder',
				),
			);

		return $settings;
	}
}


return new WC_Settings_Site_Visibility();

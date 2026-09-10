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
	 * Whether a section of this settings page renders through the settings UI.
	 *
	 * The whole page is a slotfill placeholder that a React app fills in, so it
	 * stays on the classic renderer.
	 *
	 * @since 11.2.0
	 *
	 * @param string $section Section id. An empty string means the default section.
	 * @return bool
	 */
	public function supports_settings_ui( string $section ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- The section is part of the opt-out contract; this implementation applies to every section.
		return false;
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
}


return new WC_Settings_Site_Visibility();

<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

/**
 * Handles getting options from WooCommerce settings pages.
 *
 * Class SettingOptions
 */
class SettingOptions {
	/**
	 * Setting option controller.
	 *
	 * @var \WC_REST_Setting_Options_Controller
	 */
	private $setting_option_controller;

	private $ignore_setting_types = array( 'title', 'sectionend', 'slotfill_placeholder' );


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setting_option_controller = new \WC_REST_Setting_Options_Controller();
	}

	/**
	 * Get options for a specific settings page.
	 *
	 * @param string $page_id The page ID.
	 * @return array
	 *
	 * @throws \Exception If the settings page is not found.
	 */
	public function get_page_options( $page_id ) {
		$settings = $this->setting_option_controller->get_group_settings( $page_id );

		if ( is_wp_error( $settings ) ) {
			throw new \Exception( esc_html( $settings->get_error_message() ) );
		}

		$page_options = array();

		foreach ( $settings as $setting ) {
			// Skip if the setting type is not valid.
			if ( in_array( $setting['type'], $this->ignore_setting_types, true ) || ! isset( $setting['id'] ) ) {
				continue;
			}

			$key = is_array( $setting['option_key'] ) ? $setting['option_key'][0] : $setting['option_key'];

			// Skip if the option key is already in the page options.
			if ( in_array( $key, $page_options, true ) ) {
				continue;
			}

			$page_options[ $key ] = get_option( $key, $setting['default'] ?? null );
		}

		return $page_options;
	}

	/**
	 * Get a settings page by ID.
	 *
	 * @deprecated 9.9.0 This method is no longer used.
	 * @return null
	 */
	public function get_page() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
		return null;
	}

	/**
	 * Get information about a settings page.
	 *
	 * @deprecated 9.9.0 This method is no longer used.
	 * @return array
	 */
	protected function get_page_info() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
		return array();
	}
}

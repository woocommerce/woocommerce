<?php
/**
 * MultiCurrencySettingsPage class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

if ( ! class_exists( '\WC_Settings_Page', false ) && defined( 'WC_ABSPATH' ) ) {
	require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
}

/**
 * WooCommerce settings page adapter for native multi-currency settings rows.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySettingsPage extends \WC_Settings_Page {

	/**
	 * Whether this page should hide the standard settings save button.
	 *
	 * @var bool
	 */
	private bool $hide_save_button = false;

	/**
	 * Settings rows for this page.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $settings = array();

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $manifest Settings page manifest.
	 */
	public function __construct( array $manifest ) {
		$this->id               = is_scalar( $manifest['id'] ?? null ) ? (string) $manifest['id'] : '';
		$this->label            = is_scalar( $manifest['label'] ?? null ) ? (string) $manifest['label'] : '';
		$this->hide_save_button = (bool) ( $manifest['hide_save_button'] ?? false );
		$this->settings         = $this->normalize_settings( $manifest['settings'] ?? array() );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Section being shown.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_settings( $current_section = '' ) {
		unset( $current_section );

		if ( $this->hide_save_button ) {
			$GLOBALS['hide_save_button'] = true;
		}

		return $this->settings;
	}

	/**
	 * Normalize projected settings rows.
	 *
	 * @param mixed $settings Settings rows.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_settings( $settings ): array {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$normalized_settings = array();

		foreach ( $settings as $setting ) {
			if ( is_array( $setting ) ) {
				/**
				 * Projected settings rows are associative settings field arrays.
				 *
				 * @var array<string,mixed> $setting
				 */
				$normalized_settings[] = $setting;
			}
		}

		return $normalized_settings;
	}
}

<?php
/**
 * MultiCurrencySettingsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencySettingsInterface;

/**
 * Settings service for the native multi-currency runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySettingsService implements MultiCurrencySettingsInterface {

	/**
	 * Tell whether development mode is enabled.
	 *
	 * @return bool
	 */
	public function is_dev_mode(): bool {
		return defined( 'WC_STRIPE_DEV_MODE' ) && (bool) WC_STRIPE_DEV_MODE;
	}

	/**
	 * Get the owning plugin file path.
	 *
	 * @return string
	 */
	public function get_plugin_file_path(): string {
		return defined( 'WC_PLUGIN_FILE' ) ? WC_PLUGIN_FILE : '';
	}

	/**
	 * Get the owning plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return defined( 'WC_VERSION' ) ? WC_VERSION : '';
	}
}

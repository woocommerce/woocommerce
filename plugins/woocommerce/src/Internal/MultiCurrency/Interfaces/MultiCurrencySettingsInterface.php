<?php
/**
 * MultiCurrencySettingsInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Interfaces;

/**
 * Settings boundary for the native multi-currency runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface MultiCurrencySettingsInterface {

	/**
	 * Tell whether development mode is enabled.
	 *
	 * @return bool
	 */
	public function is_dev_mode(): bool;

	/**
	 * Get the owning plugin file path.
	 *
	 * @return string
	 */
	public function get_plugin_file_path(): string;

	/**
	 * Get the owning plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;
}

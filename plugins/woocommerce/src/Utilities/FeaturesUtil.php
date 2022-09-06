<?php
/**
 * FeaturesUtil class file.
 */

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Class with methods that allow to retrieve information about the existing WooCommerce features,
 * also has methods for WooCommerce plugins to declare (in)compatibility with the features.
 */
class FeaturesUtil {

	/**
	 * Get all the existing WooCommerce features.
	 *
	 * Returns an associative array where keys are unique feature ids
	 * and values are arrays with these keys:
	 *
	 * - name
	 * - description
	 * - is_experimental
	 * - is_enabled (if $include_enabled_info is passed as true)
	 *
	 * @param bool $include_experimental Include also experimental/work in progress features in the list.
	 * @param bool $include_enabled_info True to include the 'is_enabled' field in the returned features info.
	 * @returns array An array of information about existing features.
	 */
	public static function get_features( bool $include_experimental = false, bool $include_enabled_info = false ): array {
		return wc_get_container()->get( FeaturesController::class )->get_features( $include_experimental, $include_enabled_info );
	}

	/**
	 * Check if a given feature is currently enabled.
	 *
	 * @param  string $feature_id Unique feature id.
	 * @return bool True if the feature is enabled, false if not or if the feature doesn't exist.
	 */
	public static function feature_is_enabled( string $feature_id ): bool {
		return wc_get_container()->get( FeaturesController::class )->feature_is_enabled( $feature_id );
	}

	/**
	 * Declare (in)compatibility with a given feature for a given plugin.
	 *
	 * This method MUST be executed from inside a handler for the 'before_woocommerce_init' hook.
	 *
	 * @param string $feature_id Unique feature id.
	 * @param string $plugin_name Plugin name, as returned by 'wp plugin list'.
	 * @param bool   $positive_compatibility True if the plugin declares being compatible with the feature, false if it declares being incompatible.
	 * @return bool True on success, false on error (feature doesn't exist or not inside the required hook).
	 * @throws \Exception A plugin attempted to declare itself as compatible and incompatible with a given feature at the same time.
	 */
	public static function declare_compatibility( string $feature_id, string $plugin_name, bool $positive_compatibility = true ): bool {
		return wc_get_container()->get( FeaturesController::class )->declare_compatibility( $feature_id, $plugin_name, $positive_compatibility );
	}

	/**
	 * Get the ids of the features that a certain plugin has declared compatibility for.
	 *
	 * This method can't be called before the 'woocommerce_init' hook is fired.
	 *
	 * @param string $plugin_name Plugin name, as returned by 'wp plugin list'.
	 * @return array An array having a 'compatible' and an 'incompatible' key, each holding an array of plugin ids.
	 */
	public static function get_compatible_features_for_plugin( string $plugin_name ): array {
		return wc_get_container()->get( FeaturesController::class )->get_compatible_features_for_plugin( $plugin_name );
	}
}

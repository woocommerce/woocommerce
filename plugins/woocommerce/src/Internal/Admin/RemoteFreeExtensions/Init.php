<?php
/**
 * Handles running payment method specs
 */

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\DefaultFreeExtensions;

/**
 * Remote Payment Methods engine.
 * This goes through the specs and gets eligible payment methods.
 */
class Init {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_updated', array( __CLASS__, 'delete_specs_transient' ) );
	}

	/**
	 * Go through the specs and run them.
	 *
	 * @param array $allowed_bundles Optional array of allowed bundles to be returned.
	 * @return array
	 */
	public static function get_extensions( $allowed_bundles = array() ) {
		$locale = get_user_locale();

		$specs   = self::get_specs();
		$results = EvaluateExtension::evaluate_bundles( $specs, $allowed_bundles );

		$plugins = array_filter(
			$results['bundles'],
			function( $bundle ) {
				return count( $bundle['plugins'] ) > 0;
			}
		);

		// When no plugins are visible, replace it with defaults and save for 3 hours.
		if ( empty( $plugins ) ) {
			RemoteFreeExtensionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => DefaultFreeExtensions::get_all() ), 3 * HOUR_IN_SECONDS );

			return EvaluateExtension::evaluate_bundles( DefaultFreeExtensions::get_all(), $allowed_bundles )['bundles'];
		}

		// When plugins is not empty but has errors, save it for 3 hours.
		if ( count( $results['errors'] ) > 0 ) {
			RemoteFreeExtensionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => $specs ), 3 * HOUR_IN_SECONDS );
		}

		return $results['bundles'];
	}

	/**
	 * Delete the specs transient.
	 */
	public static function delete_specs_transient() {
		RemoteFreeExtensionsDataSourcePoller::get_instance()->delete_specs_transient();
	}

	/**
	 * Get specs or fetch remotely if they don't exist.
	 */
	public static function get_specs() {
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return DefaultFreeExtensions::get_all();
		}
		$specs = RemoteFreeExtensionsDataSourcePoller::get_instance()->get_specs_from_data_sources();

		// Fetch specs if they don't yet exist.
		if ( false === $specs || ! is_array( $specs ) || 0 === count( $specs ) ) {
			return DefaultFreeExtensions::get_all();
		}

		return $specs;
	}
}

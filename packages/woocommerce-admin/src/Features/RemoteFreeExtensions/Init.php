<?php
/**
 * Handles running payment method specs
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\DefaultFreeExtensions;

/**
 * Remote Payment Methods engine.
 * This goes through the specs and gets eligible payment methods.
 */
class Init {
	const SPECS_TRANSIENT_NAME = 'woocommerce_admin_remote_free_extensions_specs';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'change_locale', array( __CLASS__, 'delete_specs_transient' ) );
		add_action( 'woocommerce_admin_updated', array( __CLASS__, 'delete_specs_transient' ) );
	}

	/**
	 * Go through the specs and run them.
	 *
	 * @param array $allowed_bundles Optional array of allowed bundles to be returned.
	 * @return array
	 */
	public static function get_extensions( $allowed_bundles = array() ) {
		$bundles = array();
		$specs   = self::get_specs();

		foreach ( $specs as $spec ) {
			$spec              = (object) $spec;
			$bundle            = (array) $spec;
			$bundle['plugins'] = array();

			if ( ! empty( $allowed_bundles ) && ! in_array( $spec->key, $allowed_bundles, true ) ) {
				continue;
			}

			foreach ( $spec->plugins as $plugin ) {
				$extension = EvaluateExtension::evaluate( (object) $plugin );

				if ( ! property_exists( $extension, 'is_visible' ) || $extension->is_visible ) {
					$bundle['plugins'][] = $extension;
				}
			}

			$bundles[] = $bundle;
		}

		return $bundles;
	}

	/**
	 * Delete the specs transient.
	 */
	public static function delete_specs_transient() {
		delete_transient( self::SPECS_TRANSIENT_NAME );
	}

	/**
	 * Get specs or fetch remotely if they don't exist.
	 */
	public static function get_specs() {
		$specs = get_transient( self::SPECS_TRANSIENT_NAME );

		// Fetch specs if they don't yet exist.
		if ( false === $specs || ! is_array( $specs ) || 0 === count( $specs ) ) {
			if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
				return DefaultFreeExtensions::get_all();
			}

			$specs = DataSourcePoller::read_specs_from_data_sources();

			// Fall back to default specs if polling failed.
			if ( ! $specs || empty( $specs ) ) {
				return DefaultFreeExtensions::get_all();
			}

			set_transient( self::SPECS_TRANSIENT_NAME, $specs, 7 * DAY_IN_SECONDS );
		}

		return $specs;
	}
}

<?php
/**
 * Handles running payment method specs
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;

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
	}

	/**
	 * Go through the specs and run them.
	 */
	public static function get_extensions() {
		$methods = array();
		$specs   = self::get_specs();

		foreach ( $specs as $spec ) {
			$method    = EvaluateExtension::evaluate( $spec );
			$methods[] = $method;
		}

		return $methods;
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
			// We are running too early, need to poll data sources first.
			$specs = DataSourcePoller::read_specs_from_data_sources();
			// Localize top level.
			$specs = self::localize( $specs );
			// Localize plugins.
			foreach ( $specs as $spec ) {
				$spec->plugins = self::localize( $spec->plugins );
			}

			set_transient( self::SPECS_TRANSIENT_NAME, $specs, 7 * DAY_IN_SECONDS );
		}

		return $specs;
	}

	/**
	 * Localize the provided method.
	 *
	 * @param array $specs The specs to localize.
	 * @return array Localized specs.
	 */
	public static function localize( $specs ) {
		$localized_specs = array();

		foreach ( $specs as $spec ) {
			if ( ! isset( $spec->locales ) ) {
				continue;
			}

			$locale = SpecRunner::get_locale( $spec->locales );

			// Skip specs where no matching locale is found.
			if ( ! $locale ) {
				continue;
			}

			$data = (object) array_merge( (array) $locale, (array) $spec );
			unset( $data->locales );

			$localized_specs[] = $data;
		}

		return $localized_specs;
	}
}

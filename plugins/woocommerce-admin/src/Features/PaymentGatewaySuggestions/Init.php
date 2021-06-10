<?php
/**
 * Handles running payment gateway suggestion specs
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\PaymentGatewaysController;

/**
 * Remote Payment Methods engine.
 * This goes through the specs and gets eligible payment gateways.
 */
class Init {
	const SPECS_TRANSIENT_NAME = 'woocommerce_admin_payment_gateway_suggestions_specs';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'change_locale', array( __CLASS__, 'delete_specs_transient' ) );
		PaymentGatewaysController::init();
	}

	/**
	 * Go through the specs and run them.
	 */
	public static function get_methods() {
		$methods = array();
		$specs   = self::get_specs();

		foreach ( $specs as $spec ) {
			$method    = EvaluateMethod::evaluate( $spec );
			$methods[] = $method;
		}

		return array_values(
			array_filter(
				$methods,
				function( $method ) {
					return ! property_exists( $method, 'is_visible' ) || $method->is_visible;
				}
			)
		);

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
				return DefaultPaymentGateways::get_all();
			}

			$specs = DataSourcePoller::read_specs_from_data_sources();

			// Fall back to default specs if polling failed.
			if ( ! $specs ) {
				return DefaultPaymentGateways::get_all();
			}

			$specs = self::localize( $specs );
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

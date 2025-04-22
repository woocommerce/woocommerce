<?php
/**
 * Handles running payment gateway suggestion specs
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteSpecs\RemoteSpecsEngine;

/**
 * Remote Payment Methods engine.
 * This goes through the specs and gets eligible payment gateways.
 */
class Init extends RemoteSpecsEngine {
	/**
	 * Option name for dismissed payment method suggestions.
	 */
	const RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION = 'woocommerce_setting_payments_recommendations_hidden';

	/**
	 * Constructor.
	 */
	public function __construct() {
		PaymentGatewaysController::init();
		add_action( 'update_option_woocommerce_default_country', array( $this, 'delete_specs_transient' ) );
	}

	/**
	 * Go through the specs and run them.
	 *
	 * @param array|null $specs payment suggestion spec array.
	 * @return array
	 */
	public static function get_suggestions( ?array $specs = null ) {
		$locale = get_user_locale();

		$specs           = is_array( $specs ) ? $specs : self::get_specs();
		$results         = EvaluateSuggestion::evaluate_specs( $specs );
		$specs_to_return = $results['suggestions'];
		$specs_to_save   = null;

		if ( empty( $specs_to_return ) ) {
			// When suggestions is empty, replace it with defaults and save for 3 hours.
			$specs_to_save   = DefaultPaymentGateways::get_all();
			$specs_to_return = EvaluateSuggestion::evaluate_specs( $specs_to_save )['suggestions'];
		} elseif ( count( $results['errors'] ) > 0 ) {
			// When suggestions is not empty but has errors, save it for 3 hours.
			$specs_to_save = $specs;
		}

		if ( count( $results['errors'] ) > 0 ) {
			self::log_errors( $results['errors'] );
		}

		if ( $specs_to_save ) {
			PaymentGatewaySuggestionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => $specs_to_save ), 3 * HOUR_IN_SECONDS );
		}

		return $specs_to_return;
	}

	/**
	 * Gets either cached or default suggestions.
	 *
	 * @return array
	 */
	public static function get_cached_or_default_suggestions() {
		$specs = 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' )
			? DefaultPaymentGateways::get_all()
			: PaymentGatewaySuggestionsDataSourcePoller::get_instance()->get_cached_specs();

		if ( ! is_array( $specs ) || 0 === count( $specs ) ) {
			$specs = DefaultPaymentGateways::get_all();
		}
		/**
		 * Allows filtering of payment gateway suggestion specs
		 *
		 * @since 6.4.0
		 *
		 * @param array Gateway specs.
		 */
		$specs   = apply_filters( 'woocommerce_admin_payment_gateway_suggestion_specs', $specs );
		$results = EvaluateSuggestion::evaluate_specs( $specs );
		return $results['suggestions'];
	}

	/**
	 * Delete the specs transient.
	 */
	public static function delete_specs_transient() {
		PaymentGatewaySuggestionsDataSourcePoller::get_instance()->delete_specs_transient();
	}

	/**
	 * Get specs or fetch remotely if they don't exist.
	 */
	public static function get_specs() {
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return apply_filters( 'woocommerce_admin_payment_gateway_suggestion_specs', DefaultPaymentGateways::get_all() );
		}
		$specs = PaymentGatewaySuggestionsDataSourcePoller::get_instance()->get_specs_from_data_sources();

		// Fetch specs if they don't yet exist.
		if ( false === $specs || ! is_array( $specs ) || 0 === count( $specs ) ) {
			return apply_filters( 'woocommerce_admin_payment_gateway_suggestion_specs', DefaultPaymentGateways::get_all() );
		}

		return apply_filters( 'woocommerce_admin_payment_gateway_suggestion_specs', $specs );
	}

	/**
	 * Check if suggestions should be shown in the settings screen.
	 *
	 * @return bool
	 */
	public static function should_display() {
		if ( 'yes' === get_option( self::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION, 'no' ) ) {
			return false;
		}

		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_allow_payment_recommendations', true );
	}

	/**
	 * Dismiss the suggestions.
	 */
	public static function dismiss() {
		return update_option( self::RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION, 'yes' );
	}
}

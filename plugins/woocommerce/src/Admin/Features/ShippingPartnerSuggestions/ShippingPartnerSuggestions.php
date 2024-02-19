<?php

namespace Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Admin\RemoteSpecs\RemoteSpecsEngine;

/**
 * Class ShippingPartnerSuggestions
 */
class ShippingPartnerSuggestions extends RemoteSpecsEngine {

	/**
	 * Go through the specs and run them.
	 *
	 * @param array|null $specs shipping partner suggestion spec array.
	 * @return array
	 */
	public static function get_suggestions( array $specs = null ) {
		$locale = get_user_locale();

		$specs           = is_array( $specs ) ? $specs : self::get_specs();
		$results         = EvaluateSuggestion::evaluate_specs( $specs );
		$specs_to_return = $results['suggestions'];

		// When suggestions is empty, replace it with defaults and save for 3 hours.
		if ( empty( $results['suggestions'] ) ) {
			ShippingPartnerSuggestionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => DefaultShippingPartners::get_all() ), 3 * HOUR_IN_SECONDS );
			$specs_to_return = EvaluateSuggestion::evaluate_specs( DefaultShippingPartners::get_all() )['suggestions'];
		}

		// When suggestions is not empty but has errors, save it for 3 hours.
		if ( count( $results['errors'] ) > 0 ) {
			ShippingPartnerSuggestionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => $specs ), 3 * HOUR_IN_SECONDS );
			self::log_errors( $results['errors'] );
		}

		return $specs_to_return;
	}

	/**
	 * Get specs or fetch remotely if they don't exist.
	 */
	public static function get_specs() {
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			/**
			 * It can be used to modify shipping partner suggestions spec.
			 *
			 * @since 7.4.1
			 */
			return apply_filters( 'woocommerce_admin_shipping_partner_suggestions_specs', DefaultShippingPartners::get_all() );
		}
		$specs = ShippingPartnerSuggestionsDataSourcePoller::get_instance()->get_specs_from_data_sources();

		// Fetch specs if they don't yet exist.
		if ( false === $specs || ! is_array( $specs ) || 0 === count( $specs ) ) {
			/**
			 * It can be used to modify shipping partner suggestions spec.
			 *
			 * @since 7.4.1
			 */
			return apply_filters( 'woocommerce_admin_shipping_partner_suggestions_specs', DefaultShippingPartners::get_all() );
		}

		/**
		 * It can be used to modify shipping partner suggestions spec.
		 *
		 * @since 7.4.1
		 */
		return apply_filters( 'woocommerce_admin_shipping_partner_suggestions_specs', $specs );
	}
}

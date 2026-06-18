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
	public static function get_suggestions( ?array $specs = null ) {
		$locale = get_user_locale();

		$specs           = is_array( $specs ) ? $specs : self::get_specs();
		$results         = EvaluateSuggestion::evaluate_specs( $specs, array( 'source' => 'wc-shipping-partner-suggestions' ) );
		$specs_to_return = $results['suggestions'];
		$specs_to_save   = null;

		if ( empty( $specs_to_return ) ) {
			// When suggestions is empty, replace it with defaults and save for 3 hours.
			$specs_to_save   = DefaultShippingPartners::get_all();
			$specs_to_return = EvaluateSuggestion::evaluate_specs( $specs_to_save, array( 'source' => 'wc-shipping-partner-suggestions' ) )['suggestions'];
		} elseif ( count( $results['errors'] ) > 0 ) {
			// When suggestions is not empty but has errors, save it for 3 hours.
			$specs_to_save = $specs;
		}

		if ( $specs_to_save ) {
			ShippingPartnerSuggestionsDataSourcePoller::get_instance()->set_specs_transient( array( $locale => $specs_to_save ), 3 * HOUR_IN_SECONDS );
		}

		return self::sort_by_primary( $specs_to_return );
	}

	/**
	 * Sort suggestions so that partners whose countries_where_primary list contains the
	 * current store country appear first.
	 *
	 * @param array $suggestions Suggestions to sort.
	 * @return array Sorted suggestions.
	 */
	private static function sort_by_primary( array $suggestions ) {
		$country = wc_get_base_location()['country'] ?? '';

		// Attach original indices to preserve input order for equal-priority items
		// (usort is not stable on PHP < 8.0).
		$indexed = array_map(
			function ( $item, $idx ) {
				return array( $item, $idx );
			},
			$suggestions,
			array_keys( $suggestions )
		);

		usort(
			$indexed,
			function ( $a, $b ) use ( $country ) {
				$a_primary = isset( $a[0]->countries_where_primary ) && is_array( $a[0]->countries_where_primary ) && in_array( $country, $a[0]->countries_where_primary, true );
				$b_primary = isset( $b[0]->countries_where_primary ) && is_array( $b[0]->countries_where_primary ) && in_array( $country, $b[0]->countries_where_primary, true );

				if ( $a_primary === $b_primary ) {
					return $a[1] - $b[1];
				}

				return $a_primary ? -1 : 1;
			}
		);

		return array_column( $indexed, 0 );
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

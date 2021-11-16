<?php

namespace Automattic\WooCommerce\Admin;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;

/**
 * Specs data source poller class for payment gateway suggestions.
 */
class PaymentMethodSuggestionsDataSourcePoller extends DataSourcePoller {


	const ID = 'payment_method_suggestions';

	/**
	 * Option name for dismissed payment method suggestions.
	 */
	const RECOMMENDED_PAYMENT_PLUGINS_DISMISS_OPTION = 'woocommerce_setting_payments_recommendations_hidden';

	/**
	 * Default data sources array.
	 */
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/payment-method/suggestions.json',
	);

	/**
	 * Class instance.
	 *
	 * @var Analytics instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self( self::ID, self::DATA_SOURCES );
		}
		return self::$instance;
	}

	/**
	 * Gets the payment method suggestions after validating the specs.
	 *
	 * @return array visible specs.
	 */
	public function get_suggestions() {
		if ( ! $this->allow_recommendations() ) {
			return array();
		}
		$suggestions = array();
		$specs       = $this->get_specs_from_data_sources();

		foreach ( $specs as $spec ) {
			$suggestion    = EvaluateSuggestion::evaluate( $spec );
			$suggestions[] = $suggestion;
		}

		return array_values(
			array_filter(
				$suggestions,
				function( $suggestion ) {
					return ! property_exists( $suggestion, 'is_visible' ) || $suggestion->is_visible;
				}
			)
		);
	}

	/**
	 * Should recommendations be displayed?
	 *
	 * @return bool
	 */
	private function allow_recommendations() {
		// Suggestions are only displayed if user can install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Suggestions may be disabled via a setting under Accounts & Privacy.
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return false;
		}

		// User can disabled all suggestions via filter.
		return apply_filters( 'woocommerce_allow_payment_recommendations', true );
	}
}


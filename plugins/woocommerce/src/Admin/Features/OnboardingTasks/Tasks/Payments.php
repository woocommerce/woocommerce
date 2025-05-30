<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments as PaymentsService;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;

/**
 * Payments Task
 */
class Payments extends Task {

	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'payments';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Set up payments', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Choose payment providers and enable payment methods at checkout.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '5 minutes', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( $this->is_complete_result === null ) {
			$this->is_complete_result = self::has_gateways_other_than_woopayments();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// The task is always visible.
		return true;
	}


	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return bool
	 */
	private function is_woopayments_active() {
		return class_exists( '\WC_Payments' );
	}
	/**
	 * Check if the store is in a WooPayments-supported geography.
	 *
	 * @param string $country_code Optional. Country code to check. If not provided, uses store base country.
	 * @return bool
	 */
	private function is_store_in_woopayments_supported_geo( $country_code = null ) {
		$country_code = PaymentsService::get_country_from_user_meta_or_fallback_to_base_country();

		if ( class_exists( '\WC_Payments_Utils' ) ) {
			$supported_countries = array_keys( \WC_Payments_Utils::supported_countries() );
			return in_array( $country_code, $supported_countries, true );
		} else {
			// WooPayments is not installed, use core's list of supported countries
			$supported_countries = DefaultPaymentGateways::get_wcpay_countries();
			return in_array( $country_code, $supported_countries, true );
		}
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function has_gateways() {
		$gateways         = WC()->payment_gateways()->payment_gateways;
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Check if the store has any enabled gateways other than WooPayments.
	 *
	 * @return bool
	 */
	public static function has_gateways_other_than_woopayments() {
		$gateways = WC()->payment_gateways()->payment_gateways;
		
		// Get all WooPayments gateway IDs if WooPayments is available
		$woopayments_gateway_ids = array();
		if ( class_exists( '\WC_Payments' ) && method_exists( '\WC_Payments', 'get_woopayments_gateway_ids' ) ) {
			$woopayments_gateway_ids = \WC_Payments::get_woopayments_gateway_ids();
		} else {
			// Fallback: WooPayments gateways follow the pattern 'woocommerce_payments' and 'woocommerce_payments_{payment_method}'
			$woopayments_gateway_ids = array( 'woocommerce_payments' );
		}

		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) use ( $woopayments_gateway_ids ) {
				if ( 'yes' !== $gateway->enabled ) {
					return false;
				}
				
				// Exclude all WooPayments gateways
				if ( in_array( $gateway->id, $woopayments_gateway_ids, true ) ) {
					return false;
				}
				
				// Also exclude gateways that start with 'woocommerce_payments_' as a fallback
				if ( strpos( $gateway->id, 'woocommerce_payments' ) === 0 ) {
					return false;
				}
				
				return true;
			}
		);

		return ! empty( $enabled_gateways );
	}
}

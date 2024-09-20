<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init as Suggestions;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init as WCPayPromotionInit;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;

/**
 * WooCommercePayments Task
 */
class WooCommercePayments extends Task {
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
		return 'woocommerce-payments';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Get paid with WooPayments', 'woocommerce' );
	}

	/**
	 * Badge.
	 *
	 * @return string
	 */
	public function get_badge() {
		/**
		 * Filter WooPayments onboarding task badge.
		 *
		 * @param string     $badge    Badge content.
		 * @since 8.2.0
		 */
		return apply_filters( 'woocommerce_admin_woopayments_onboarding_task_badge', '' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			"You're only one step away from getting paid. Verify your business details to start managing transactions with WooPayments.",
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Action label.
	 *
	 * @return string
	 */
	public function get_action_label() {
		return __( 'Finish setup', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( null === $this->is_complete_result ) {
			$this->is_complete_result = self::is_connected() && ! self::is_account_partially_onboarded();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		$payments = $this->task_list->get_task( 'payments' );

		return ! $payments->is_complete() && // Do not re-display the task if the general "Payments" task has already been completed.
			self::is_supported();
	}

	/**
	 * Check if the WooPayments plugin was requested during onboarding.
	 *
	 * @return bool
	 */
	public static function is_requested() {
		$profiler_data       = get_option( OnboardingProfile::DATA_OPTION, array() );
		$product_types       = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();
		$business_extensions = isset( $profiler_data['business_extensions'] ) ? $profiler_data['business_extensions'] : array();

		$subscriptions_and_us = in_array( 'subscriptions', $product_types, true ) && 'US' === WC()->countries->get_base_country();
		return in_array( 'woocommerce-payments', $business_extensions, true ) || $subscriptions_and_us;
	}

	/**
	 * Check if the WooPayments plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_installed() {
		$installed_plugins = PluginsHelper::get_installed_plugin_slugs();
		return in_array( 'woocommerce-payments', $installed_plugins, true );
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return bool
	 */
	public static function is_wcpay_active() {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Check if WooPayments is connected.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		if ( class_exists( '\WC_Payments' ) ) {
			$wc_payments_gateway = \WC_Payments::get_gateway();
			return method_exists( $wc_payments_gateway, 'is_connected' )
				? $wc_payments_gateway->is_connected()
				: false;
		}

		return false;
	}

	/**
	 * Check if WooPayments needs setup.
	 * Errored data or payments not enabled.
	 *
	 * @return bool
	 */
	public static function is_account_partially_onboarded() {
		if ( class_exists( '\WC_Payments' ) ) {
			$wc_payments_gateway = \WC_Payments::get_gateway();
			return method_exists( $wc_payments_gateway, 'is_account_partially_onboarded' )
				? $wc_payments_gateway->is_account_partially_onboarded()
				: false;
		}

		return false;
	}

	/**
	 * Check if the store is in a WooPayments supported country.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		$suggestions              = Suggestions::get_suggestions( DefaultPaymentGateways::get_all() );
		$suggestion_plugins       = array_merge(
			...array_filter(
				array_column( $suggestions, 'plugins' ),
				function ( $plugins ) {
					return is_array( $plugins );
				}
			)
		);
		$woocommerce_payments_ids = array_search( 'woocommerce-payments', $suggestion_plugins, true );
		if ( false !== $woocommerce_payments_ids ) {
			return true;
		}
		return false;
	}
}

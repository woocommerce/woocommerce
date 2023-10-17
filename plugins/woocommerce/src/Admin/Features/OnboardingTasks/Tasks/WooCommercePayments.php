<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init as Suggestions;
use Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init as WCPayPromotionInit;

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
		return __( 'Set up WooPayments', 'woocommerce' );
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
	 * Additional info.
	 *
	 * @return string
	 */
	public function get_additional_info() {
		if ( WCPayPromotionInit::is_woopay_eligible() ) {
			return __(
				'By using WooPayments you agree to be bound by our <a href="https://wordpress.com/tos/" target="_blank">Terms of Service</a> (including WooPay <a href="https://wordpress.com/tos/#more-woopay-specifically" target="_blank">merchant terms</a>) and acknowledge that you have read our <a href="https://automattic.com/privacy/" target="_blank">Privacy Policy</a>',
				'woocommerce'
			);
		}

		return __(
			'By using WooPayments you agree to be bound by our <a href="https://wordpress.com/tos/" target="_blank">Terms of Service</a> and acknowledge that you have read our <a href="https://automattic.com/privacy/" target="_blank">Privacy Policy</a>',
			'woocommerce'
		);
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

		return ! $payments->is_complete() && // Do not re-display the task if the "add payments" task has already been completed.
			self::is_installed() &&
			self::is_supported();
	}

	/**
	 * Check if the plugin was requested during onboarding.
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
	 * Check if the plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_installed() {
		$installed_plugins = PluginsHelper::get_installed_plugin_slugs();
		return in_array( 'woocommerce-payments', $installed_plugins, true );
	}

	/**
	 * Check if WooCommerce Payments is connected.
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
	 * Check if WooCommerce Payments needs setup.
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
	 * Check if the store is in a supported country.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		$suggestions              = Suggestions::get_suggestions();
		$suggestion_plugins       = array_merge(
			...array_filter(
				array_column( $suggestions, 'plugins' ),
				function( $plugins ) {
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

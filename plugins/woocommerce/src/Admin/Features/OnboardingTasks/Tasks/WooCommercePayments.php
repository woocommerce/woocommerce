<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init as Suggestions;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage;

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
	 * Additional data.
	 *
	 * @return mixed
	 */
	public function get_additional_data() {
		/**
		 * Filter WooPayments onboarding task additional data.
		 *
		 * @since 9.4.0
		 *
		 * @param ?array $additional_data The task additional data.
		 */
		return apply_filters( 'woocommerce_admin_woopayments_onboarding_task_additional_data', null );
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
			// This task is complete if there are other ecommerce gateways enabled (offline payment methods are excluded),
			// or if WooPayments is active and has a connected, fully onboarded account.
			$this->is_complete_result = self::has_other_ecommerce_gateways() || ( self::is_connected() && ! self::is_account_partially_onboarded() );
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return self::is_supported();
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
		if ( ! self::is_wcpay_active() ) {
			return false;
		}

		$wc_payments_gateway = self::get_gateway();
		if ( $wc_payments_gateway && method_exists( $wc_payments_gateway, 'is_connected' ) ) {
			return $wc_payments_gateway->is_connected();
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
		if ( ! self::is_wcpay_active() ) {
			return false;
		}

		$wc_payments_gateway = self::get_gateway();
		if ( $wc_payments_gateway && method_exists( $wc_payments_gateway, 'is_account_partially_onboarded' ) ) {
			return $wc_payments_gateway->is_account_partially_onboarded();
		}

		return false;
	}

	/**
	 * Get the WooPayments payment gateway suggestion.
	 *
	 * @return object|null The WooPayments suggestion, or null if none found.
	 */
	public static function get_suggestion() {
		$suggestions       = Suggestions::get_suggestions( DefaultPaymentGateways::get_all() );
		$wcpay_suggestions = array_filter(
			$suggestions,
			function ( $suggestion ) {
				if ( empty( $suggestion->plugins ) || ! is_array( $suggestion->plugins ) ) {
					return false;
				}

				return in_array( 'woocommerce-payments', $suggestion->plugins, true );
			}
		);

		if ( empty( $wcpay_suggestions ) ) {
			return null;
		}

		return reset( $wcpay_suggestions );
	}

	/**
	 * Check if the store location is in a WooPayments supported country.
	 *
	 * We infer this from the availability of a WooPayments payment gateways suggestion.
	 *
	 * @return bool True if the store location is in a WooPayments supported country, false otherwise.
	 */
	public static function is_supported() {
		return ! empty( self::get_suggestion() );
	}

	/**
	 * Get the WooPayments gateway.
	 *
	 * @return \WC_Payments|null
	 */
	private static function get_gateway() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		if ( isset( $payment_gateways['woocommerce_payments'] ) ) {
			return $payment_gateways['woocommerce_payments'];
		}
		return null;
	}

	/**
	 * Check if the store has any enabled ecommerce gateways, other than WooPayments.
	 *
	 * We exclude offline payment methods from this check.
	 *
	 * @return bool
	 */
	public static function has_other_ecommerce_gateways(): bool {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				// Filter out any WooPayments-related or offline gateways.
				return 'yes' === $gateway->enabled
					&& 0 !== strpos( $gateway->id, 'woocommerce_payments' )
					&& ! in_array( $gateway->id, array( 'bacs', 'cheque', 'cod' ), true );
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * The task action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		if ( self::is_supported() ) {
			// If WooPayments is active, point to the WooPayments client surfaces/flows.
			if ( self::is_wcpay_active() ) {
				// Point to a WooPayments connect link to let the WooPayments client figure out the proper
				// place to redirect the user to.
				return add_query_arg(
					array(
						'wcpay-connect' => '1',
						'from'          => 'WCADMIN_PAYMENT_TASK',
						'_wpnonce'      => wp_create_nonce( 'wcpay-connect' ),
					),
					admin_url( 'admin.php' )
				);
			}

			// Check if there is an active WooPayments incentive via the welcome page.
			if ( WcPayWelcomePage::instance()->is_incentive_visible() ) {
				// Point to the WooPayments welcome page.
				return add_query_arg( 'from', 'WCADMIN_PAYMENT_TASK', admin_url( 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' ) );
			}

			// WooPayments is not active.
			// Trigger the WooPayments plugin installation and/or activation by pointing to the task suggestion URL.
			return add_query_arg(
				array(
					'task' => $this->get_id(),
					'id'   => self::get_suggestion()->id,
				),
				admin_url( 'admin.php?page=wc-admin' )
			);
		}

		// Fall back to the WooPayments task page URL.
		return add_query_arg( 'task', $this->get_id(), admin_url( 'admin.php?page=wc-admin' ) );
	}
}

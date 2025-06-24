<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use WC_Abstract_Order;
use WC_Payment_Gateway;
use WooCommerce\Admin\Experimental_Abtest;

defined( 'ABSPATH' ) || exit;

/**
 * WooPayments payment gateway provider class.
 *
 * This class handles all the custom logic for the WooPayments payment gateway provider.
 */
class WooPayments extends PaymentGateway {

	const PREFIX = 'woocommerce_admin_settings_payments__woopayments__';

	/**
	 * Extract the payment gateway provider details from the object.
	 *
	 * @param WC_Payment_Gateway $gateway      The payment gateway object.
	 * @param int                $order        Optional. The order to assign.
	 *                                         Defaults to 0 if not provided.
	 * @param string             $country_code Optional. The country code for which the details are being gathered.
	 *                                         This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway provider details.
	 */
	public function get_details( WC_Payment_Gateway $gateway, int $order = 0, string $country_code = '' ): array {
		$details = parent::get_details( $gateway, $order, $country_code );

		// Switch the onboarding type to native.
		$details['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE;

		// Add the test [drive] account details to the onboarding state.
		$details['onboarding']['state']['test_drive_account'] = $this->has_test_drive_account();

		// Add WPCOM/Jetpack connection details to the onboarding state.
		$details['onboarding']['state'] = array_merge( $details['onboarding']['state'], $this->get_wpcom_connection_state() );

		// If the WooPayments installed version is less than minimum required version,
		// we can't use the in-context onboarding flows.
		if ( Constants::is_defined( 'WCPAY_VERSION_NUMBER' ) &&
			version_compare( Constants::get_constant( 'WCPAY_VERSION_NUMBER' ), PaymentsProviders\WooPayments\WooPaymentsService::EXTENSION_MINIMUM_VERSION, '<' ) ) {

			return $details;
		}

		// Switch the onboarding type to native in-context.
		$details['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE_IN_CONTEXT;

		// Provide the native, in-context onboarding URL instead of the external one.
		// This is a catch-all URL that should start or continue the onboarding process.
		$details['onboarding']['_links']['onboard'] = array(
			'href' => Utils::wc_payments_settings_url( '/woopayments/onboarding', array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ) ),
		);

		return $details;
	}

	/**
	 * Enhance this provider's payment extension suggestion with additional information.
	 *
	 * The details added do not require the payment extension to be active or a gateway instance.
	 *
	 * @param array $extension_suggestion The extension suggestion details.
	 *
	 * @return array The enhanced payment extension suggestion details.
	 */
	public function enhance_extension_suggestion( array $extension_suggestion ): array {
		$extension_suggestion = parent::enhance_extension_suggestion( $extension_suggestion );

		// If the extension is installed, we can get the plugin data and act upon it.
		if ( ! empty( $extension_suggestion['plugin']['file'] ) &&
			isset( $extension_suggestion['plugin']['status'] ) &&
			in_array( $extension_suggestion['plugin']['status'], array( PaymentsProviders::EXTENSION_INSTALLED, PaymentsProviders::EXTENSION_ACTIVE ), true ) ) {

			// Switch to the native in-context onboarding type if the WooPayments extension its version is compatible.
			// We need to put back the '.php' extension to construct the plugin filename.
			$plugin_data = PluginsHelper::get_plugin_data( $extension_suggestion['plugin']['file'] . '.php' );
			if ( $plugin_data && ! empty( $plugin_data['Version'] ) &&
				version_compare( $plugin_data['Version'], PaymentsProviders\WooPayments\WooPaymentsService::EXTENSION_MINIMUM_VERSION, '>=' ) ) {

				$extension_suggestion['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE_IN_CONTEXT;
			}
		} else {
			// We assume the latest version of the WooPayments extension will be installed.
			$extension_suggestion['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE_IN_CONTEXT;
		}

		// Add onboarding state.
		if ( ! isset( $extension_suggestion['onboarding']['state'] ) || ! is_array( $extension_suggestion['onboarding']['state'] ) ) {
			$extension_suggestion['onboarding']['state'] = array();
		}
		// Add the store's WPCOM/Jetpack connection state to the onboarding state.
		$extension_suggestion['onboarding']['state'] = array_merge(
			$extension_suggestion['onboarding']['state'],
			$this->get_wpcom_connection_state()
		);

		// Add onboarding links.
		if ( empty( $extension_suggestion['onboarding']['_links'] ) || ! is_array( $extension_suggestion['onboarding']['_links'] ) ) {
			$extension_suggestion['onboarding']['_links'] = array();
		}

		// We only add the preload link if we don't have a working WPCOM connection.
		// This is because WooPayments onboarding preloading focuses on hydrating the WPCOM connection.
		if ( ! $extension_suggestion['onboarding']['state']['wpcom_has_working_connection'] ) {
			try {
				/**
				 * The WooPayments REST controller instance.
				 *
				 * @var WooPaymentsRestController $rest_controller
				 */
				$rest_controller = wc_get_container()->get( WooPaymentsRestController::class );

				// Add the onboarding preload URL.
				$extension_suggestion['onboarding']['_links']['preload'] = array(
					'href' => rest_url( $rest_controller->get_rest_url_path( 'onboarding/preload' ) ),
				);
			} catch ( \Throwable $e ) {
				// If the REST controller is not available, we can't preload the onboarding data.
				// This is not a critical error, so we just ignore it.
				// Log so we can investigate.
				SafeGlobalFunctionProxy::wc_get_logger()->error(
					'Failed to get the WooPayments REST controller instance: ' . $e->getMessage(),
					array(
						'source' => 'settings-payments',
					)
				);
			}
		}

		return $extension_suggestion;
	}

	/**
	 * Get the current state of the store's WPCOM/Jetpack connection.
	 *
	 * @return array The store's WPCOM/Jetpack connection state.
	 */
	private function get_wpcom_connection_state(): array {
		$wpcom_connection_manager = new WPCOM_Connection_Manager( 'woocommerce' );
		$is_connected             = $wpcom_connection_manager->is_connected();
		$has_connected_owner      = $wpcom_connection_manager->has_connected_owner();

		return array(
			'wpcom_has_working_connection' => $is_connected && $has_connected_owner,
			'wpcom_is_store_connected'     => $is_connected,
			'wpcom_has_connected_owner'    => $has_connected_owner,
			'wpcom_is_connection_owner'    => $has_connected_owner && $wpcom_connection_manager->is_connection_owner(),
		);
	}

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		// No account means we need setup.
		if ( ! $this->is_account_connected( $payment_gateway ) ) {
			return true;
		}

		// Test-drive accounts don't need setup.
		if ( $this->has_test_drive_account() ) {
			return false;
		}

		return parent::needs_setup( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		if ( class_exists( '\WC_Payments' ) &&
			is_callable( '\WC_Payments::mode' ) ) {

			$woopayments_mode = \WC_Payments::mode();
			if ( is_callable( array( $woopayments_mode, 'is_test' ) ) ) {
				return $woopayments_mode->is_test();
			}
		}

		return parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in dev mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in dev mode, false otherwise.
	 */
	public function is_in_dev_mode( WC_Payment_Gateway $payment_gateway ): bool {
		if ( class_exists( '\WC_Payments' ) &&
			is_callable( '\WC_Payments::mode' ) ) {

			$woopayments_mode = \WC_Payments::mode();
			if ( is_callable( array( $woopayments_mode, 'is_dev' ) ) ) {
				return $woopayments_mode->is_dev();
			}
		}

		return parent::is_in_dev_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		if ( class_exists( '\WC_Payments' ) &&
			is_callable( '\WC_Payments::mode' ) ) {

			$woopayments_mode = \WC_Payments::mode();
			if ( is_callable( array( $woopayments_mode, 'is_test_mode_onboarding' ) ) ) {
				return $woopayments_mode->is_test_mode_onboarding();
			}
		}

		return parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Get the onboarding URL for the payment gateway.
	 *
	 * This URL should start or continue the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $return_url      Optional. The URL to return to after onboarding.
	 *                                            This will likely get attached to the onboarding URL.
	 *
	 * @return string The onboarding URL for the payment gateway.
	 */
	public function get_onboarding_url( WC_Payment_Gateway $payment_gateway, string $return_url = '' ): string {
		if ( class_exists( '\WC_Payments_Account' ) && is_callable( '\WC_Payments_Account::get_connect_url' ) ) {
			$connect_url = \WC_Payments_Account::get_connect_url();
		} else {
			$connect_url = parent::get_onboarding_url( $payment_gateway, $return_url );
		}

		$query = wp_parse_url( $connect_url, PHP_URL_QUERY );
		// We expect the URL to have a query string. Bail if it doesn't.
		if ( empty( $query ) ) {
			return $connect_url;
		}

		// Default URL params to set, regardless if they exist.
		$params = array(
			'from'                      => defined( '\WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS' ) ? \WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS : 'WCADMIN_PAYMENT_SETTINGS',
			'source'                    => defined( '\WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE' ) ? \WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE : 'wcadmin-settings-page',
			'redirect_to_settings_page' => 'true',
		);

		// First, sanity check to handle existing accounts.
		// Such accounts should keep their current onboarding mode.
		// Do not force things either way.
		if ( $this->is_account_connected( $payment_gateway ) ) {
			return add_query_arg( $params, $connect_url );
		}

		// We don't have an account yet, so the onboarding link is used to kickstart the process.

		// Default to test-account-first onboarding.
		$live_onboarding = false;

		/*
		 * Apply our routing logic to determine if we should do a live onboarding/account.
		 *
		 * For new stores (not yet launched aka in Coming Soon mode),
		 * based on the answers provided in the onboarding profile, we will do live onboarding if:
		 * - Merchant selected “I’m already selling” AND answered either:
		 *   - Yes, I’m selling online.
		 *   - I’m selling both online and offline.
		 *
		 * For launched stores, we will only consider live onboarding if all are true:
		 * - Store is at least 90 days old.
		 * - Store has an active payments gateway (other than WooPayments).
		 * - Store has processed a live electronic payment in the past 90 days (any gateway).
		 *
		 * @see plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx for the values.
		 */
		if ( filter_var( get_option( 'woocommerce_coming_soon' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$onboarding_profile = get_option( OnboardingProfile::DATA_OPTION, array() );
			if (
				isset( $onboarding_profile['business_choice'] ) && 'im_already_selling' === $onboarding_profile['business_choice'] &&
				isset( $onboarding_profile['selling_online_answer'] ) && (
					'yes_im_selling_online' === $onboarding_profile['selling_online_answer'] ||
					'im_selling_both_online_and_offline' === $onboarding_profile['selling_online_answer']
				)
			) {
				$live_onboarding = true;
			}
		} elseif (
			WCAdminHelper::is_wc_admin_active_for( 90 * DAY_IN_SECONDS ) &&
			$this->has_enabled_other_ecommerce_gateways() &&
			$this->has_orders()
		) {
			$live_onboarding = true;
		}

		// We run an experiment to determine the efficiency of test-account-first onboarding vs straight-to-live onboarding.
		// If the experiment is active and the store is in the treatment group, we will force live onboarding.
		// Otherwise, we will do test-account-first onboarding (control group).
		// Stores that are determined by our routing logic that they should do straight-to-live onboarding
		// will not be affected by the experiment.
		if ( ! $live_onboarding ) {
			$transient_key = 'wc_experiment_failure_woocommerce_payment_settings_onboarding_2025_v1';

			// Try to get cached result first.
			$cached_result = get_transient( $transient_key );

			// If we have a cache entry that indicates an error, don't enforce anything. Just let the routing logic decide.
			if ( 'error' !== $cached_result ) {
				try {
					if ( Experimental_Abtest::in_treatment( 'woocommerce_payment_settings_onboarding_2025_v1' ) ) {
						$live_onboarding = true;
					}
				} catch ( \Exception $e ) {
					// If the experiment fails, set a transient to avoid repeated failures.
					set_transient( $transient_key, 'error', HOUR_IN_SECONDS );
				}
			}
		}

		// If we are doing live onboarding, we don't need to add more to the URL.
		// But for test-drive/sandbox mode, we have work to do.
		if ( ! $live_onboarding ) {
			$params['test_drive']                       = 'true';
			$params['auto_start_test_drive_onboarding'] = 'true';
		}

		return add_query_arg( $params, $connect_url );
	}

	/**
	 * Check if the store has any paid orders.
	 *
	 * Currently, we look at the past 90 days and only consider orders
	 * with status `wc-completed`, `wc-processing`, or `wc-refunded`.
	 *
	 * @return boolean Whether the store has any paid orders.
	 */
	private function has_orders(): bool {
		$store_has_orders_transient_name = self::PREFIX . 'store_has_orders';

		// First, get the stored value, if it exists.
		// This way we avoid costly DB queries and API calls.
		$has_orders = get_transient( $store_has_orders_transient_name );
		if ( false !== $has_orders ) {
			return wc_string_to_bool( $has_orders );
		}

		// We need to determine the value.
		// Start with the assumption that the store doesn't have orders in the timeframe we look at.
		$has_orders = false;
		// By default, we will check for new orders every 6 hours.
		$expiration = 6 * HOUR_IN_SECONDS;

		// Get the latest completed, processing, or refunded order.
		$latest_order = wc_get_orders(
			array(
				'status'  => array( OrderInternalStatus::COMPLETED, OrderInternalStatus::PROCESSING, OrderInternalStatus::REFUNDED ),
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
		if ( ! empty( $latest_order ) ) {
			$latest_order = reset( $latest_order );
			// If the latest order is within the timeframe we look at, we consider the store to have orders.
			// Otherwise, it clearly doesn't have orders.
			if ( $latest_order instanceof WC_Abstract_Order
				&& strtotime( (string) $latest_order->get_date_created() ) >= strtotime( '-90 days' ) ) {

				$has_orders = true;

				// For ultimate efficiency, we will check again after 90 days from the latest order
				// because in all that time we will consider the store to have orders regardless of new orders.
				$expiration = strtotime( (string) $latest_order->get_date_created() ) + 90 * DAY_IN_SECONDS - time();
			}
		}

		// Store the value for future use.
		set_transient( $store_has_orders_transient_name, $has_orders ? 'yes' : 'no', $expiration );

		return $has_orders;
	}

	/**
	 * Check if the store has any other enabled ecommerce gateways.
	 *
	 * We exclude offline payment methods from this check.
	 *
	 * @return bool True if the store has any enabled ecommerce gateways, false otherwise.
	 */
	private function has_enabled_other_ecommerce_gateways(): bool {
		$gateways                 = WC()->payment_gateways()->payment_gateways;
		$other_ecommerce_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				// Filter out offline gateways and WooPayments.
				return 'yes' === $gateway->enabled &&
					! in_array(
						$gateway->id,
						array( 'woocommerce_payments', ...PaymentsProviders::OFFLINE_METHODS ),
						true
					);
			}
		);

		return ! empty( $other_ecommerce_gateways );
	}

	/**
	 * Determines if the current account is a test-drive account.
	 *
	 * @return bool True if the account is a test-drive account, false otherwise.
	 */
	private function has_test_drive_account(): bool {
		if ( function_exists( '\wcpay_get_container' ) && class_exists( '\WC_Payments_Account' ) ) {
			$account_service = \wcpay_get_container()->get( \WC_Payments_Account::class );
			if ( ! empty( $account_service ) && is_callable( array( $account_service, 'get_account_status_data' ) ) ) {
				$account_status = $account_service->get_account_status_data();

				return ! empty( $account_status['testDrive'] );
			}
		}

		return false;
	}
}

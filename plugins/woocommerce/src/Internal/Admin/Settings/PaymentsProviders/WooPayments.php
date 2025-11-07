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
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Abstract_Order;
use WC_Payment_Gateway;

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
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing -- We wrap the throw in a try/catch.
	 */
	public function get_details( WC_Payment_Gateway $gateway, int $order = 0, string $country_code = '' ): array {
		$details = parent::get_details( $gateway, $order, $country_code );

		$has_test_account    = $this->has_test_account();
		$has_sandbox_account = $this->has_sandbox_account();

		// Switch the onboarding type to native.
		$details['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE;

		// Add the test [drive] account details to the onboarding state.
		$details['onboarding']['state']['test_drive_account'] = $has_test_account;

		// Add WPCOM/Jetpack connection details to the onboarding state.
		$details['onboarding']['state'] = array_merge( $details['onboarding']['state'], $this->get_wpcom_connection_state() );

		// If the WooPayments installed version is less than minimum required version,
		// we can't use the in-context onboarding flows.
		if ( Constants::is_defined( 'WCPAY_VERSION_NUMBER' ) &&
			version_compare( Constants::get_constant( 'WCPAY_VERSION_NUMBER' ), WooPaymentsService::EXTENSION_MINIMUM_VERSION, '<' ) ) {

			return $details;
		}

		// Switch the onboarding type to native in-context.
		$details['onboarding']['type'] = self::ONBOARDING_TYPE_NATIVE_IN_CONTEXT;

		// Provide the native, in-context onboarding URL instead of the external one.
		// This is a catch-all URL that should start or continue the onboarding process.
		$details['onboarding']['_links']['onboard'] = array(
			'href' => Utils::wc_payments_settings_url( '/woopayments/onboarding', array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ) ),
		);

		try {
			/**
			 * The WooPayments REST controller instance.
			 *
			 * @var WooPaymentsRestController $rest_controller
			 */
			$rest_controller = wc_get_container()->get( WooPaymentsRestController::class );

			// Add disable test account URL to onboarding links, if the current account is a test or sandbox account.
			if ( $has_test_account || $has_sandbox_account ) {
				$details['onboarding']['_links']['disable_test_account'] = array(
					'href' => rest_url( $rest_controller->get_rest_url_path( 'onboarding/test_account/disable' ) ),
				);
			}

			// Add reset account/onboarding URL to onboarding links.
			$details['onboarding']['_links']['reset'] = array(
				'href' => rest_url( $rest_controller->get_rest_url_path( 'onboarding/reset' ) ),
			);
		} catch ( \Throwable $e ) {
			// If the REST controller is not available, we can't generate the REST API endpoint URLs.
			// This is not a critical error, so we just ignore it.
			// Log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->error(
				'Failed to get the WooPayments REST controller instance: ' . $e->getMessage(),
				array(
					'source' => 'settings-payments',
				)
			);
		}

		// Override the onboarding state with the entries provided by the WooPayments service.
		if ( ! empty( $country_code ) ) {
			try {
				/**
				 * The WooPayments service instance.
				 *
				 * @var WooPaymentsService $service
				 */
				$service = wc_get_container()->get( WooPaymentsService::class );

				// Ensure we have a valid rest_controller from the earlier try block.
				if ( ! isset( $rest_controller ) ) {
					throw new \RuntimeException( 'WooPayments REST controller not available' );
				}

				$onboarding_details = $service->get_onboarding_details( $country_code, $rest_controller->get_rest_url_path( 'onboarding' ) );
				// Merge the onboarding state with the one provided by the service.
				if ( ! empty( $onboarding_details['state'] ) && is_array( $onboarding_details['state'] ) ) {
					$details['onboarding']['state'] = array_merge(
						$details['onboarding']['state'],
						$onboarding_details['state']
					);
				}
				// Merge any messages provided by the service.
				if ( ! empty( $onboarding_details['messages'] ) && is_array( $onboarding_details['messages'] ) ) {
					if ( ! isset( $details['onboarding']['messages'] ) || ! is_array( $details['onboarding']['messages'] ) ) {
						$details['onboarding']['messages'] = array();
					}
					$details['onboarding']['messages'] = array_merge(
						$details['onboarding']['messages'],
						$onboarding_details['messages']
					);
				}
				// The steps provided by the service override any existing steps.
				if ( ! empty( $onboarding_details['steps'] ) && is_array( $onboarding_details['steps'] ) ) {
					$details['onboarding']['steps'] = $onboarding_details['steps'];
				}
				// Merge any context provided by the service.
				if ( ! empty( $onboarding_details['context'] ) && is_array( $onboarding_details['context'] ) ) {
					if ( ! isset( $details['onboarding']['context'] ) || ! is_array( $details['onboarding']['context'] ) ) {
						$details['onboarding']['context'] = array();
					}
					$details['onboarding']['context'] = array_merge(
						$details['onboarding']['context'],
						$onboarding_details['context']
					);
				}
			} catch ( \Throwable $e ) {
				// If the service is not available, we can't impose the more specific logic.
				// This is not a critical error, so we just ignore it.
				// Log so we can investigate.
				SafeGlobalFunctionProxy::wc_get_logger()->error(
					'Failed to get the WooPayments service instance: ' . $e->getMessage(),
					array(
						'source' => 'settings-payments',
					)
				);
			}
		}

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
			$plugin_data = $this->proxy->call_static( PluginsHelper::class, 'get_plugin_data', $extension_suggestion['plugin']['file'] . '.php' );
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
			} catch ( Throwable $e ) {
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
		if ( $this->has_test_account() ) {
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
		if ( $this->proxy->call_function( 'class_exists', 'WC_Payments' ) &&
			$this->proxy->call_function( 'is_callable', 'WC_Payments::mode' ) ) {

			$woopayments_mode = $this->proxy->call_static( 'WC_Payments', 'mode' );
			if ( $this->proxy->call_function( 'method_exists', $woopayments_mode, 'is_test' ) &&
				$this->proxy->call_function( 'is_callable', array( $woopayments_mode, 'is_test' ) ) ) {

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
		if ( $this->proxy->call_function( 'class_exists', 'WC_Payments' ) &&
			$this->proxy->call_function( 'is_callable', 'WC_Payments::mode' ) ) {

			$woopayments_mode = $this->proxy->call_static( 'WC_Payments', 'mode' );
			if ( $this->proxy->call_function( 'method_exists', $woopayments_mode, 'is_dev' ) &&
				$this->proxy->call_function( 'is_callable', array( $woopayments_mode, 'is_dev' ) ) ) {

				return $woopayments_mode->is_dev();
			}
		}

		return parent::is_in_dev_mode( $payment_gateway );
	}

	/**
	 * Check if the payment gateway supports the current store state for onboarding.
	 *
	 * Most of the time the current business location should be the main factor, but could also
	 * consider other store settings like currency.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $country_code    Optional. The country code for which to check.
	 *                                            This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool|null True if the payment gateway supports onboarding, false otherwise.
	 *                   If the payment gateway does not provide the information,
	 *                   we will return null to indicate that we don't know.
	 */
	public function is_onboarding_supported( WC_Payment_Gateway $payment_gateway, string $country_code = '' ): ?bool {
		$is_onboarding_supported = parent::is_onboarding_supported( $payment_gateway, $country_code );
		if ( ! is_null( $is_onboarding_supported ) ) {
			return $is_onboarding_supported;
		}

		// Without a country code to check against, we assume onboarding is supported to avoid blocking the user.
		if ( empty( $country_code ) ) {
			return true;
		}

		// Normalize the country code.
		$country_code = strtoupper( $country_code );

		// The payment gateway didn't provide the information. We will do it the hard way.
		$supported_country_codes = $this->get_supported_country_codes();
		// If we can't get the supported countries, we assume onboarding supported to avoid blocking the user.
		if ( is_null( $supported_country_codes ) ) {
			return true;
		}

		return in_array( $country_code, $supported_country_codes, true );
	}

	/**
	 * Get the message to show when the payment gateway does not support onboarding.
	 *
	 * @see self::is_onboarding_supported()
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $country_code    Optional. The country code for which to check.
	 *                                            This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return string|null The message to show when the payment gateway does not support onboarding,
	 *                     or null if no specific message should be provided.
	 */
	public function get_onboarding_not_supported_message( WC_Payment_Gateway $payment_gateway, string $country_code = '' ): ?string {
		$message = parent::get_onboarding_not_supported_message( $payment_gateway, $country_code );
		if ( ! is_null( $message ) ) {
			return $message;
		}

		return sprintf(
			/* translators: %s: WooPayments. */
			esc_html__( '%s is not supported in the selected business location.', 'woocommerce' ),
			'WooPayments'
		);
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
		if ( $this->proxy->call_function( 'class_exists', 'WC_Payments' ) &&
			$this->proxy->call_function( 'is_callable', 'WC_Payments::mode' ) ) {

			$woopayments_mode = $this->proxy->call_static( 'WC_Payments', 'mode' );
			if ( $this->proxy->call_function( 'method_exists', $woopayments_mode, 'is_test_mode_onboarding' ) &&
				$this->proxy->call_function( 'is_callable', array( $woopayments_mode, 'is_test_mode_onboarding' ) ) ) {

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
		if ( $this->proxy->call_function( 'class_exists', 'WC_Payments_Account' ) &&
			$this->proxy->call_function( 'is_callable', 'WC_Payments_Account::get_connect_url' ) ) {

			$connect_url = $this->proxy->call_static( 'WC_Payments_Account', 'get_connect_url' );
		} else {
			$connect_url = parent::get_onboarding_url( $payment_gateway, $return_url );
		}

		// Default URL params to set, regardless if they exist.
		$params = array(
			'from'                      => Constants::is_defined( 'WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS' ) ? (string) Constants::get_constant( 'WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS' ) : 'WCADMIN_PAYMENT_SETTINGS',
			'source'                    => Constants::is_defined( 'WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE' ) ? (string) Constants::get_constant( 'WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE' ) : 'wcadmin-settings-page',
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
	 * Determines if the current account is a test account.
	 *
	 * Test accounts are test-drive accounts.
	 * They are different from sandbox accounts (i.e. accounts onboarded in test mode).
	 *
	 * @return bool True if the account is a test account, false otherwise.
	 */
	private function has_test_account(): bool {
		if ( $this->proxy->call_function( 'function_exists', 'wcpay_get_container' ) &&
			$this->proxy->call_function( 'class_exists', 'WC_Payments_Account' ) ) {

			$woopayments_container = $this->proxy->call_function( 'wcpay_get_container' );
			$account_service       = $woopayments_container->get( 'WC_Payments_Account' );
			if ( ! empty( $account_service ) &&
				$this->proxy->call_function( 'method_exists', $account_service, 'get_account_status_data' ) &&
				$this->proxy->call_function( 'is_callable', array( $account_service, 'get_account_status_data' ) ) ) {

				$account_status = $account_service->get_account_status_data();

				return ! empty( $account_status['testDrive'] );
			}
		}

		return false;
	}

	/**
	 * Determines if the current account is a sandbox account.
	 *
	 * Sandbox accounts are accounts that were onboarded in test mode.
	 * They are different from test accounts (i.e. test-drive accounts).
	 *
	 * Sandbox accounts are generally created in development or staging environments when simulating live onboarding.
	 *
	 * @return bool True if the account is a sandbox account, false otherwise.
	 */
	private function has_sandbox_account(): bool {
		if ( $this->proxy->call_function( 'function_exists', 'wcpay_get_container' ) &&
			$this->proxy->call_function( 'class_exists', 'WC_Payments_Account' ) ) {

			$woopayments_container = $this->proxy->call_function( 'wcpay_get_container' );
			$account_service       = $woopayments_container->get( 'WC_Payments_Account' );
			if ( ! empty( $account_service ) &&
				$this->proxy->call_function( 'method_exists', $account_service, 'get_account_status_data' ) &&
				$this->proxy->call_function( 'is_callable', array( $account_service, 'get_account_status_data' ) ) ) {

				$account_status = $account_service->get_account_status_data();

				return empty( $account_status['isLive'] ) && empty( $account_status['testDrive'] );
			}
		}

		return false;
	}

	/**
	 * Get the list of supported country codes for WooPayments.
	 *
	 * @return array|null The list of supported countries as ISO 3166-1 alpha-2 country codes.
	 *                    The country codes are normalized in uppercase.
	 *                    If the list cannot be retrieved, null is returned.
	 */
	private function get_supported_country_codes(): ?array {
		try {
			if ( $this->proxy->call_function( 'class_exists', 'WC_Payments_Utils' ) &&
				$this->proxy->call_function( 'is_callable', 'WC_Payments_Utils::supported_countries' ) ) {

				$supported_country_codes = $this->proxy->call_static( 'WC_Payments_Utils', 'supported_countries' );
				if ( is_array( $supported_country_codes ) ) {
					return array_unique( array_map( 'strtoupper', array_keys( $supported_country_codes ) ) );
				}
			}
		} catch ( Throwable $e ) {
			// This is not a critical error, so we just ignore it.
			// Log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->error(
				'Failed to get the WooPayments supported country codes list: ' . $e->getMessage(),
				array(
					'source' => 'settings-payments',
				)
			);
		}

		return null;
	}

	/**
	 * Get the current state of the store's WPCOM/Jetpack connection.
	 *
	 * @return array The store's WPCOM/Jetpack connection state.
	 */
	private function get_wpcom_connection_state(): array {
		try {
			$wpcom_connection_manager = $this->proxy->get_instance_of( WPCOM_Connection_Manager::class, 'woocommerce' );
		} catch ( \Throwable $e ) {
			// Log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->error(
				'Failed to get the WPCOM/Jetpack Connection Manager instance: ' . $e->getMessage(),
				array(
					'source' => 'settings-payments',
				)
			);

			// Assume no connection.
			return array(
				'wpcom_has_working_connection' => false,
				'wpcom_is_store_connected'     => false,
				'wpcom_has_connected_owner'    => false,
				'wpcom_is_connection_owner'    => false,
			);
		}

		$is_connected        = $wpcom_connection_manager->is_connected();
		$has_connected_owner = $wpcom_connection_manager->has_connected_owner();

		return array(
			'wpcom_has_working_connection' => $is_connected && $has_connected_owner,
			'wpcom_is_store_connected'     => $is_connected,
			'wpcom_has_connected_owner'    => $has_connected_owner,
			'wpcom_is_connection_owner'    => $has_connected_owner && $wpcom_connection_manager->is_connection_owner(),
		);
	}
}

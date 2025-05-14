<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiArgumentException;
use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WP_Error;
use WP_Http;

defined( 'ABSPATH' ) || exit;
/**
 * WooPayments-specific Payments settings page service class.
 */
class WooPaymentsService {

	const GATEWAY_ID = 'woocommerce_payments';

	/**
	 * The minimum required version of the WooPayments extension.
	 */
	const EXTENSION_MINIMUM_VERSION = '9.3.0';

	const ONBOARDING_PATH_BASE = '/woopayments/onboarding';

	const ONBOARDING_STEP_PAYMENT_METHODS       = 'payment_methods';
	const ONBOARDING_STEP_WPCOM_CONNECTION      = 'wpcom_connection';
	const ONBOARDING_STEP_TEST_ACCOUNT          = 'test_account';
	const ONBOARDING_STEP_BUSINESS_VERIFICATION = 'business_verification';

	const ONBOARDING_STEP_STATUS_NOT_STARTED = 'not_started';
	const ONBOARDING_STEP_STATUS_STARTED     = 'started';
	const ONBOARDING_STEP_STATUS_COMPLETED   = 'completed';

	const ACTION_TYPE_REST     = 'REST';
	const ACTION_TYPE_REDIRECT = 'REDIRECT';

	const NOX_PROFILE_OPTION_KEY    = 'woocommerce_woopayments_nox_profile';
	const NOX_ONBOARDING_LOCKED_KEY = 'woocommerce_woopayments_nox_onboarding_locked';

	const FROM_PAYMENT_SETTINGS = 'WCADMIN_PAYMENT_SETTINGS';
	const FROM_NOX_IN_CONTEXT   = 'WCADMIN_NOX_IN_CONTEXT';

	/**
	 * The PaymentProviders instance.
	 *
	 * @var PaymentProviders
	 */
	private PaymentProviders $payment_providers;

	/**
	 * The LegacyProxy instance.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $proxy;

	/**
	 * The WPCOM connection manager instance.
	 *
	 * @var WPCOM_Connection_Manager|object
	 */
	private $wpcom_connection_manager;

	/**
	 * The WooPayments provider instance.
	 *
	 * @var PaymentProviders\PaymentGateway
	 */
	private PaymentProviders\PaymentGateway $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param PaymentProviders $payment_providers The PaymentProviders instance.
	 * @param LegacyProxy      $proxy             The LegacyProxy instance.
	 */
	final public function init( PaymentProviders $payment_providers, LegacyProxy $proxy ): void {
		$this->payment_providers = $payment_providers;
		$this->proxy             = $proxy;

		$this->wpcom_connection_manager = $this->proxy->get_instance_of( WPCOM_Connection_Manager::class, 'woocommerce' );
		$this->provider                 = $this->payment_providers->get_payment_gateway_provider_instance( self::GATEWAY_ID );
	}

	/**
	 * Get the onboarding details for the settings page.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array The onboarding details.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 * @throws Exception If there were errors when generating the onboarding details.
	 */
	public function get_onboarding_details( string $location, string $rest_path ): array {
		// Since getting the onboarding details is not idempotent, we will check it as an action.
		$this->check_if_onboarding_action_is_acceptable();

		return array(
			// This state is high-level data, independent of the type of onboarding flow.
			'state'   => array(
				'started'   => $this->provider->is_onboarding_started( $this->get_payment_gateway() ),
				'completed' => $this->provider->is_onboarding_completed( $this->get_payment_gateway() ),
				'test_mode' => $this->provider->is_in_test_mode_onboarding( $this->get_payment_gateway() ),
				'dev_mode'  => $this->provider->is_in_dev_mode( $this->get_payment_gateway() ),
			),
			'steps'   => $this->get_onboarding_steps( $location, trailingslashit( $rest_path ) . 'step' ),
			'context' => array(
				'urls' => array(
					'overview_page' => $this->get_overview_page_url(),
				),
			),
		);
	}

	/**
	 * Check if the given onboarding step ID is valid.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 *
	 * @return bool Whether the given onboarding step ID is valid.
	 */
	public function is_valid_onboarding_step_id( string $step_id ): bool {
		return in_array(
			$step_id,
			array(
				self::ONBOARDING_STEP_PAYMENT_METHODS,
				self::ONBOARDING_STEP_WPCOM_CONNECTION,
				self::ONBOARDING_STEP_TEST_ACCOUNT,
				self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			),
			true
		);
	}

	/**
	 * Get the status of an onboarding step.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return string The status of the onboarding step.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 */
	public function get_onboarding_step_status( string $step_id, string $location ): string {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new ApiArgumentException(
				'woocommerce_woopayments_onboarding_invalid_step_id',
				esc_html__( 'Invalid onboarding step ID.', 'woocommerce' ),
				(int) WP_Http::NOT_ACCEPTABLE
			);
		}

		$stored_statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		// First, we check the status of the onboarding step based on the current state of the store.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				// If there is already a valid account, report the step as completed
				// since allowing the user to configure payment methods won't have any effect.
				if ( $this->has_valid_account() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
			case self::ONBOARDING_STEP_WPCOM_CONNECTION:
				if ( $this->has_working_wpcom_connection() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				// If we only have a connected blog, but no master owner connected, we at least started the process.
				if ( $this->wpcom_connection_manager->is_connected() ) {
					return self::ONBOARDING_STEP_STATUS_STARTED;
				}

				// If there is no part of the connection set up, we check the stored status.
				if ( ! $this->wpcom_connection_manager->is_connected() && ! $this->wpcom_connection_manager->has_connected_owner() ) {
					// We respect the stored started status as it may be in progress.
					if ( ! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
						return self::ONBOARDING_STEP_STATUS_STARTED;
					}
				}

				// We definitely didn't start the onboarding step.
				return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
			case self::ONBOARDING_STEP_TEST_ACCOUNT:
				// The step can only be completed if the requirements are met.
				if ( $this->check_onboarding_step_requirements( self::ONBOARDING_STEP_TEST_ACCOUNT, $location ) ) {
					// If the account is a valid, working test account, the step is completed.
					if ( $this->has_test_account() && $this->has_valid_account() && $this->has_working_account() ) {
						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}

					// If there is a stored completed status, we respect that IF there is NO invalid test account.
					// This is the case when the user first creates a test account and then switches to live.
					if ( ! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) &&
						! ( $this->has_test_account() && ! $this->has_valid_account() )
					) {
						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}
				}

				// If we have a test account, we consider the step as started.
				if ( $this->has_test_account() ) {
					return self::ONBOARDING_STEP_STATUS_STARTED;
				}

				// We respect the stored started status.
				if ( ! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
					return self::ONBOARDING_STEP_STATUS_STARTED;
				}

				// We definitely didn't start the onboarding step.
				return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				// The step can only be completed if the requirements are met.
				// If the current account is fully onboarded and is a live account,
				// we consider the business verification step as completed.
				if ( $this->check_onboarding_step_requirements( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location ) &&
					$this->has_valid_account() &&
					$this->has_live_account() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}

				// If we have a live account, we consider the step as started.
				if ( $this->has_live_account() ) {
					return self::ONBOARDING_STEP_STATUS_STARTED;
				}

				// We respect the stored started status.
				if ( ! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
					return self::ONBOARDING_STEP_STATUS_STARTED;
				}

				// We definitely didn't start the onboarding step.
				return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
		}

		// Second, try to determine the status of the onboarding step based on the step's stored data.
		// We take a waterfall approach, where completed supersedes started.
		// For completed, we first check if the step requirements are met.
		if ( $this->check_onboarding_step_requirements( $step_id, $location ) &&
			! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) ) {
			return self::ONBOARDING_STEP_STATUS_COMPLETED;
		}
		if ( ! empty( $stored_statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
			return self::ONBOARDING_STEP_STATUS_STARTED;
		}

		// Finally, we default to not started.
		return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
	}

	/**
	 * Mark an onboarding step as started.
	 *
	 * @param string $step_id   The ID of the onboarding step.
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param bool   $overwrite Whether to overwrite the step status if it is already started and update the timestamp.
	 *
	 * @return bool Whether the onboarding step was marked as started.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function set_onboarding_step_started( string $step_id, string $location, bool $overwrite = false ): bool {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );
		if ( ! $overwrite && ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
			return true;
		}

		// Mark the step as started and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] = $this->proxy->call_function( 'time' );

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );
	}

	/**
	 * Mark an onboarding step as completed.
	 *
	 * @param string $step_id   The ID of the onboarding step.
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param bool   $overwrite Whether to overwrite the step status if it is already completed and update the timestamp.
	 *
	 * @return bool Whether the onboarding step was marked as completed.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function set_onboarding_step_completed( string $step_id, string $location, bool $overwrite = false ): bool {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );
		if ( ! $overwrite && ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) ) {
			return true;
		}

		// Mark the step as completed and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] = $this->proxy->call_function( 'time' );

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );
	}

	/**
	 * Save the data for an onboarding step.
	 *
	 * @param string $step_id      The ID of the onboarding step.
	 * @param string $location     The location for which we are onboarding.
	 *                             This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $request_data The entire data received in the request.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 * @throws ApiArgumentException If the given onboarding step ID or step data is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function onboarding_step_save( string $step_id, string $location, array $request_data ): bool {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		// Validate the received step data.
		// If we didn't receive any known data for the step, we consider it an invalid save operation.
		if ( ! $this->is_valid_onboarding_step_data( $step_id, $request_data ) ) {
			throw new ApiArgumentException(
				'woocommerce_woopayments_onboarding_invalid_step_data',
				esc_html__( 'Invalid onboarding step data.', 'woocommerce' ),
				(int) WP_Http::NOT_ACCEPTABLE
			);
		}

		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );
		if ( empty( $step_details['data'] ) ) {
			$step_details['data'] = array();
		}

		// Extract the data for the step.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				if ( isset( $request_data['payment_methods'] ) ) {
					$step_details['data']['payment_methods'] = $request_data['payment_methods'];
				}
				break;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				if ( isset( $request_data['self_assessment'] ) ) {
					$step_details['data']['self_assessment'] = $request_data['self_assessment'];
				}
				if ( isset( $request_data['sub_steps'] ) ) {
					$step_details['data']['sub_steps'] = $request_data['sub_steps'];
				}
				break;
			default:
				throw new ApiException(
					'woocommerce_woopayments_onboarding_step_action_not_supported',
					esc_html__( 'Save action not supported for the onboarding step ID.', 'woocommerce' ),
					(int) WP_Http::NOT_ACCEPTABLE
				);
		}

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step( $step_id, $location, $step_details );
	}

	/**
	 * Check if the given onboarding step data is valid.
	 *
	 * If we didn't receive any known data for the step, we consider it invalid.
	 *
	 * @param string $step_id      The ID of the onboarding step.
	 * @param array  $request_data The entire data received in the request.
	 *
	 * @return bool Whether the given onboarding step data is valid.
	 */
	private function is_valid_onboarding_step_data( string $step_id, array $request_data ): bool {
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				// Check that we have at least one piece of data.
				if ( ! isset( $request_data['payment_methods'] ) ) {
					return false;
				}

				// Check that the data is in the expected format.
				if ( ! is_array( $request_data['payment_methods'] ) ) {
					return false;
				}
				break;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				// Check that we have at least one piece of data.
				if ( ! isset( $request_data['self_assessment'] ) &&
					! isset( $request_data['sub_steps'] ) ) {
					return false;
				}

				// Check that the data is in the expected format.
				if ( isset( $request_data['self_assessment'] ) && ! is_array( $request_data['self_assessment'] ) ) {
					return false;
				}
				if ( isset( $request_data['sub_steps'] ) && ! is_array( $request_data['sub_steps'] ) ) {
					return false;
				}
				break;
			default:
				// If we don't know how to validate the data, we assume it is valid.
				return true;
		}

		return true;
	}

	/**
	 * Check an onboarding step's status/progress.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The check result.
	 * @throws ApiArgumentException If the given onboarding step ID or step data is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function onboarding_step_check( string $step_id, string $location ): array {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		// Just return the step status, for now.
		return array( 'status' => $this->get_onboarding_step_status( $step_id, $location ) );
	}

	/**
	 * Get the recommended payment methods details for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The recommended payment methods details.
	 */
	public function get_onboarding_recommended_payment_methods( string $location ): array {
		return $this->provider->get_recommended_payment_methods( $this->get_payment_gateway(), $location );
	}

	/**
	 * Initialize the test account for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param string $source   Optional. The source for the KYC session.
	 *                         If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The result of the test account initialization.
	 * @throws ApiArgumentException|ApiException If the given onboarding step ID or step data is invalid.
	 *                                           If the onboarding action can not be performed due to the current state
	 *                                           of the site or there was an error initializing the test account.
	 */
	public function onboarding_test_account_init( string $location, string $source = '' ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

		// Nothing to do if we already have a test account.
		if ( $this->has_test_account() ) {
			return array(
				'message' => esc_html__( 'A test account is already set up.', 'woocommerce' ),
			);
		}

		// Nothing to do if there is an account, but it is not a test account.
		if ( $this->has_account() ) {
			return array(
				'message' => esc_html__( 'An account is already set up. Reset the onboarding first.', 'woocommerce' ),
			);
		}

		$selected_payment_methods = $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'payment_methods', array() );

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		try {
			// Call the WooPayments API to initialize the test account.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/test_drive_account/init',
				array(
					'country'      => $location,
					'capabilities' => $selected_payment_methods,
					'source'       => ! empty( $source ) ? $source : self::FROM_NOX_IN_CONTEXT,
					'from'         => self::FROM_NOX_IN_CONTEXT,
				)
			);
		} catch ( Exception $e ) {
			// Catch any exceptions to allow for proper error handling and onboarding unlock.
			$response = new WP_Error(
				'woocommerce_woopayments_onboarding_client_api_exception',
				esc_html__( 'An unexpected error happened while initializing the test account.', 'woocommerce' ),
				array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace(),
				)
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		if ( is_wp_error( $response ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) || empty( $response['success'] ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to initialize the test account.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		return $response;
	}

	/**
	 * Get the onboarding KYC account session.
	 *
	 * @param string $location        The location for which we are onboarding.
	 *                                This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $self_assessment Optional. The self-assessment data.
	 *                                If not provided, the stored data will be used.
	 *
	 * @return array The KYC account session data.
	 * @throws ApiException If the extension is not active, step requirements are not met, or
	 *                      the KYC session data could not be retrieved.
	 */
	public function get_onboarding_kyc_session( string $location, array $self_assessment = array() ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		if ( empty( $self_assessment ) ) {
			// Get the stored self-assessment data.
			$self_assessment = (array) $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'self_assessment' );
		}

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		try {
			// Call the WooPayments API to get the KYC session.
			$account_session = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/kyc/session',
				array(
					'self_assessment' => $self_assessment,
				)
			);
		} catch ( Exception $e ) {
			// Catch any exceptions to allow for proper error handling and onboarding unlock.
			$response = new WP_Error(
				'woocommerce_woopayments_onboarding_client_api_exception',
				esc_html__( 'An unexpected error happened while creating the KYC session.', 'woocommerce' ),
				array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace(),
				)
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		if ( is_wp_error( $account_session ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $account_session->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $account_session->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $account_session ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to get the KYC session data.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// Add the user locale to the account session data to allow for localized KYC sessions.
		$account_session['locale'] = $this->proxy->call_function( 'get_user_locale' );

		return $account_session;
	}

	/**
	 * Finish the onboarding KYC account session.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param string $source   Optional. The source for the KYC session.
	 *                         If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If the extension is not active, step requirements are not met, or
	 *                      the KYC session could not be finished.
	 */
	public function finish_onboarding_kyc_session( string $location, string $source = '' ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		try {
			// Call the WooPayments API to finalize the KYC session.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/kyc/finalize',
				array(
					'source' => ! empty( $source ) ? $source : self::FROM_PAYMENT_SETTINGS,
					'from'   => self::FROM_NOX_IN_CONTEXT,
				)
			);
		} catch ( Exception $e ) {
			// Catch any exceptions to allow for proper error handling and onboarding unlock.
			$response = new WP_Error(
				'woocommerce_woopayments_onboarding_client_api_exception',
				esc_html__( 'An unexpected error happened while finalizing the KYC session.', 'woocommerce' ),
				array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace(),
				)
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		if ( is_wp_error( $response ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to finish the KYC session.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// Mark the business verification step as completed.
		$this->set_onboarding_step_completed( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		return $response;
	}

	/**
	 * Reset onboarding.
	 *
	 * @param string $from   Optional. Where in the UI the request is coming from.
	 *                       If not provided, it will identify the origin as the WC Admin Payments settings.
	 * @param string $source Optional. The source for the current onboarding flow.
	 *                       If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If we could not reset onboarding or there was an error.
	 */
	public function reset_onboarding( string $from = '', string $source = '' ): array {
		$this->check_if_onboarding_action_is_acceptable();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		try {
			// Call the WooPayments API to reset onboarding.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/reset',
				array(
					'from'   => ! empty( $from ) ? esc_attr( $from ) : self::FROM_PAYMENT_SETTINGS,
					'source' => ! empty( $source ) ? esc_attr( $source ) : self::FROM_PAYMENT_SETTINGS,
				)
			);
		} catch ( Exception $e ) {
			// Catch any exceptions to allow for proper error handling and onboarding unlock.
			$response = new WP_Error(
				'woocommerce_woopayments_onboarding_client_api_exception',
				esc_html__( 'An unexpected error happened while resetting onboarding.', 'woocommerce' ),
				array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace(),
				)
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		if ( is_wp_error( $response ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) || empty( $response['success'] ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to reset onboarding.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// Clean up any NOX-specific onboarding data.
		$this->proxy->call_function( 'delete_option', self::NOX_PROFILE_OPTION_KEY );

		return $response;
	}

	/**
	 * Disable test account during the switch-to-live onboarding flow.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param string $from     Optional. Where in the UI the request is coming from.
	 *                         If not provided, it will identify the origin as the WC Admin Payments settings.
	 * @param string $source   Optional. The source for the current onboarding flow.
	 *                         If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If we could not disable the test account or there was an error.
	 */
	public function disable_test_account( string $location, string $from = '', string $source = '' ): array {
		$this->check_if_onboarding_action_is_acceptable();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		try {
			// Call the WooPayments API to disable the test account and prepare for the switch to live.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/test_drive_account/disable',
				array(
					'from'   => ! empty( $from ) ? esc_attr( $from ) : self::FROM_PAYMENT_SETTINGS,
					'source' => ! empty( $source ) ? esc_attr( $source ) : self::FROM_PAYMENT_SETTINGS,
				)
			);
		} catch ( Exception $e ) {
			// Catch any exceptions to allow for proper error handling and onboarding unlock.
			$response = new WP_Error(
				'woocommerce_woopayments_onboarding_client_api_exception',
				esc_html__( 'An unexpected error happened while disabling the test account.', 'woocommerce' ),
				array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace(),
				)
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		if ( is_wp_error( $response ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) || empty( $response['success'] ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to disable the test account.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// For sanity, make sure the payment methods step is marked as completed.
		// This is to avoid the user being prompted to set up payment methods again.
		$this->set_onboarding_step_completed( self::ONBOARDING_STEP_PAYMENT_METHODS, $location );
		// For sanity, make sure the test account step is marked as completed.
		// After disabling a test account, the user should be prompted to set up a live account.
		$this->set_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

		return $response;
	}

	/**
	 * Check if an onboarding action should be allowed to be processed.
	 *
	 * @return void
	 * @throws ApiException If the extension is not active or onboarding is locked.
	 */
	private function check_if_onboarding_action_is_acceptable() {
		// If the WooPayments plugin is not active, we can't do anything.
		if ( ! $this->is_extension_active() ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_extension_not_active',
				/* translators: %s: WooPayments. */
				sprintf( esc_html__( 'The %s extension is not active.', 'woocommerce' ), 'WooPayments' ),
				(int) WP_Http::FORBIDDEN
			);
		}

		// If the WooPayments installed version is less than the minimum required version, we can't do anything.
		if ( defined( 'WCPAY_VERSION_NUMBER' ) && version_compare( WCPAY_VERSION_NUMBER, self::EXTENSION_MINIMUM_VERSION, '<' ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_extension_version',
				/* translators: %s: WooPayments. */
				sprintf( esc_html__( 'The %s extension is not up-to-date. Please update to the latest version and try again.', 'woocommerce' ), 'WooPayments' ),
				(int) WP_Http::FORBIDDEN
			);
		}

		// If the onboarding is locked, we shouldn't do anything.
		if ( $this->is_onboarding_locked() ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_locked',
				esc_html__( 'Another onboarding action is already in progress. Please wait for it to finish.', 'woocommerce' ),
				(int) WP_Http::CONFLICT
			);
		}
	}

	/**
	 * Check if an onboarding step action should be allowed to be processed.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return void
	 * @throws ApiArgumentException If the onboarding step ID is invalid.
	 * @throws ApiException If the extension is not active or step requirements are not met.
	 */
	private function check_if_onboarding_step_action_is_acceptable( string $step_id, string $location ): void {
		// First, check general onboarding actions.
		$this->check_if_onboarding_action_is_acceptable();

		// Second, do onboarding step specific checks.
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new ApiArgumentException(
				'woocommerce_woopayments_onboarding_invalid_step_id',
				esc_html__( 'Invalid onboarding step ID.', 'woocommerce' ),
				(int) WP_Http::BAD_REQUEST
			);
		}
		if ( ! $this->check_onboarding_step_requirements( $step_id, $location ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_step_requirements_not_met',
				esc_html__( 'Onboarding step requirements are not met.', 'woocommerce' ),
				(int) WP_Http::FORBIDDEN
			);
		}
	}

	/**
	 * Check if the onboarding is locked.
	 *
	 * @return bool Whether the onboarding is locked.
	 */
	private function is_onboarding_locked(): bool {
		return 'yes' === $this->proxy->call_function( 'get_option', self::NOX_ONBOARDING_LOCKED_KEY, 'no' );
	}

	/**
	 * Lock the onboarding.
	 *
	 * This will save a flag in the database to indicate that onboarding is locked.
	 * This is used to prevent certain onboarding actions to happen while others have not finished.
	 * This is especially important for actions that modify the account (initializing it, deleting it, etc.)
	 * These actions tend to be longer-running and we want to have backstops in place to prevent race conditions.
	 *
	 * @return void
	 */
	private function set_onboarding_lock(): void {
		$this->proxy->call_function( 'update_option', self::NOX_ONBOARDING_LOCKED_KEY, 'yes' );
	}

	/**
	 * Unlock the onboarding.
	 *
	 * @return void
	 */
	private function clear_onboarding_lock(): void {
		// We update rather than delete the option for performance reasons.
		$this->proxy->call_function( 'update_option', self::NOX_ONBOARDING_LOCKED_KEY, 'no' );
	}

	/**
	 * Get the onboarding details for each step.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array[] The list of onboarding steps details.
	 * @throws Exception If there was an error generating the onboarding steps details.
	 */
	private function get_onboarding_steps( string $location, string $rest_path ): array {
		$steps = array();

		// Add the payment methods onboarding step details.
		$steps[] = $this->standardize_onboarding_step_details(
			array(
				'id'      => self::ONBOARDING_STEP_PAYMENT_METHODS,
				'context' => array(
					'recommended_pms' => $this->get_onboarding_recommended_payment_methods( $location ),
					'pms_state'       => $this->get_onboarding_payment_methods_state( $location ),
				),
				'actions' => array(
					'start'  => array(
						'type' => self::ACTION_TYPE_REST,
						'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/start' ),
					),
					'save'   => array(
						'type' => self::ACTION_TYPE_REST,
						'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/save' ),
					),
					'finish' => array(
						'type' => self::ACTION_TYPE_REST,
						'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/finish' ),
					),
				),
			),
			$location
		);

		// Add the WPCOM connection onboarding step details.
		$wpcom_step = $this->standardize_onboarding_step_details(
			array(
				'id'      => self::ONBOARDING_STEP_WPCOM_CONNECTION,
				'context' => array(
					'connection_state' => $this->get_wpcom_connection_state(),
				),
			),
			$location
		);

		// If the WPCOM connection is already set up, we don't need to add anything more.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $wpcom_step['status'] ) {
			// Craft the return URL.
			// By default, we return to the onboarding modal.
			$return_url = $this->proxy->call_static(
				Utils::class,
				'wc_payments_settings_url',
				self::ONBOARDING_PATH_BASE,
				array(
					'wpcom_connection_return' => '1', // URL query flag so we can properly identify when the user returns.
				)
			);
			// Try to generate the authorization URL.
			$wpcom_connection = $this->get_wpcom_connection_authorization( $return_url );
			if ( ! $wpcom_connection['success'] ) {
				$wpcom_step['errors'] = array_values( $wpcom_connection['errors'] );
			}
			$wpcom_step['actions'] = array(
				'start' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_WPCOM_CONNECTION . '/start' ),
				),
				'auth'  => array(
					'type' => self::ACTION_TYPE_REDIRECT,
					'href' => $wpcom_connection['url'],
				),
			);
		}

		$steps[] = $wpcom_step;

		// Add the test account onboarding step details.
		$test_account_step = $this->standardize_onboarding_step_details(
			array(
				'id' => self::ONBOARDING_STEP_TEST_ACCOUNT,
			),
			$location
		);

		// If the step is not completed, we need to add the actions.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $test_account_step['status'] ) {
			$test_account_step['actions'] = array(
				'start'  => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/start' ),
				),
				'init'   => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/init' ),
				),
				'check'  => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/check' ),
				),
				'finish' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/finish' ),
				),
			);
		}

		$steps[] = $test_account_step;

		// Add the live account business verification onboarding step details.
		$business_verification_step = $this->standardize_onboarding_step_details(
			array(
				'id'      => self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				'context' => array(
					'fields'           => array(),
					'sub_steps'        => $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'sub_steps', array() ),
					'self_assessment'  => $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'self_assessment', array() ),
					'has_test_account' => $this->has_test_account(),
				),
			),
			$location
		);

		// Try to get the pre-KYC fields, but only if the required step is completed.
		// This is because WooPayments needs a working WPCOM connection to be able to fetch the fields.
		if ( $this->check_onboarding_step_requirements( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location ) ) {
			try {
				$business_verification_step['context']['fields'] = $this->get_onboarding_kyc_fields();
			} catch ( Exception $e ) {
				$business_verification_step['errors'][] = array(
					'code'    => 'fields_error',
					'message' => $e->getMessage(),
				);
			}
		}

		// If the step is not completed, we need to add the actions.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $business_verification_step['status'] ) {
			$business_verification_step['actions'] = array(
				'start'              => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/start' ),
				),
				'save'               => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/save' ),
				),
				'kyc_session'        => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session' ),
				),
				'kyc_session_finish' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session/finish' ),
				),
				'kyc_fallback'       => array(
					'type' => self::ACTION_TYPE_REDIRECT,
					'href' => $this->get_onboarding_kyc_fallback_url(),
				),
				'finish'             => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/finish' ),
				),
			);
		}

		$steps[] = $business_verification_step;

		// Do a complete list standardization, for safety.
		return $this->standardize_onboarding_steps_details( $steps, $location );
	}

	/**
	 * Standardize (and sanity check) the onboarding step details.
	 *
	 * @param array  $step_details The onboarding step details to standardize.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The standardized onboarding step details.
	 * @throws Exception If the onboarding step details are missing required entries or if the step ID is invalid.
	 */
	private function standardize_onboarding_step_details( array $step_details, string $location ): array {
		// If the required keys are not present, throw.
		if ( ! isset( $step_details['id'] ) ) {
			/* translators: %s: The required key that is missing. */
			throw new Exception( sprintf( esc_html__( 'The onboarding step is missing required entries: %s', 'woocommerce' ), 'id' ) );
		}
		// Validate the step ID.
		if ( ! $this->is_valid_onboarding_step_id( $step_details['id'] ) ) {
			/* translators: %s: The invalid step ID. */
			throw new Exception( sprintf( esc_html__( 'The onboarding step ID is invalid: %s', 'woocommerce' ), esc_attr( $step_details['id'] ) ) );
		}

		return array(
			'id'             => $step_details['id'],
			'path'           => $step_details['path'] ?? trailingslashit( self::ONBOARDING_PATH_BASE ) . $step_details['id'],
			'required_steps' => $step_details['required_steps'] ?? $this->get_onboarding_step_required_steps( $step_details['id'] ),
			'status'         => $step_details['status'] ?? $this->get_onboarding_step_status( $step_details['id'], $location ),
			'errors'         => $step_details['errors'] ?? array(),
			'actions'        => $step_details['actions'] ?? array(),
			'context'        => $step_details['context'] ?? array(),
		);
	}

	/**
	 * Standardize (and sanity check) the onboarding steps list.
	 *
	 * @param array  $steps The onboarding steps list to standardize.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The standardized onboarding steps list.
	 * @throws Exception If some onboarding steps are missing required entries or if invalid step IDs are present.
	 */
	private function standardize_onboarding_steps_details( array $steps, string $location ): array {
		$standardized_steps = array();
		foreach ( $steps as $step ) {
			$standardized_steps[] = $this->standardize_onboarding_step_details( $step, $location );
		}

		return $standardized_steps;
	}

	/**
	 * Get the entire stored NOX profile data
	 *
	 * @return array The stored NOX profile.
	 */
	private function get_nox_profile(): array {
		$nox_profile = $this->proxy->call_function( 'get_option', self::NOX_PROFILE_OPTION_KEY, array() );

		if ( empty( $nox_profile ) ) {
			$nox_profile = array();
		} else {
			$nox_profile = maybe_unserialize( $nox_profile );
		}

		return $nox_profile;
	}

	/**
	 * Get the onboarding step data from the NOX profile.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding step stored data from the NOX profile.
	 *               If the step data is not found, an empty array is returned.
	 */
	private function get_nox_profile_onboarding_step( string $step_id, string $location ): array {
		$nox_profile = $this->get_nox_profile();

		if ( empty( $nox_profile['onboarding'] ) ) {
			$nox_profile['onboarding'] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ] ) ) {
			$nox_profile['onboarding'][ $location ] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'][ $step_id ] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'][ $step_id ] = array();
		}

		return $nox_profile['onboarding'][ $location ]['steps'][ $step_id ];
	}

	/**
	 * Save the onboarding step data in the NOX profile.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $data     The onboarding step data to save in the profile.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 */
	private function save_nox_profile_onboarding_step( string $step_id, string $location, array $data ): bool {
		$nox_profile = $this->get_nox_profile();

		if ( empty( $nox_profile['onboarding'] ) ) {
			$nox_profile['onboarding'] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ] ) ) {
			$nox_profile['onboarding'][ $location ] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'] = array();
		}

		// Update the stored step data.
		$nox_profile['onboarding'][ $location ]['steps'][ $step_id ] = $data;

		return $this->proxy->call_function( 'update_option', self::NOX_PROFILE_OPTION_KEY, $nox_profile, false );
	}

	/**
	 * Get an entry from the NOX profile onboarding step details.
	 *
	 * @param string $step_id       The ID of the onboarding step.
	 * @param string $location      The location for which we are onboarding.
	 *                              This is a ISO 3166-1 alpha-2 country code.
	 * @param string $entry         The entry to get from the step data.
	 * @param mixed  $default_value The default value to return if the entry is not found.
	 *
	 * @return mixed The entry from the NOX profile step details. If the entry is not found, the default value is returned.
	 */
	private function get_nox_profile_onboarding_step_entry( string $step_id, string $location, string $entry, $default_value = array() ): array {
		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );

		if ( ! isset( $step_details[ $entry ] ) ) {
			return $default_value;
		}

		return $step_details[ $entry ];
	}

	/**
	 * Save an entry in the NOX profile onboarding step details.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param string $entry    The entry key under which to save in the step data.
	 * @param array  $data     The data to save in the step data.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 */
	private function save_nox_profile_onboarding_step_entry( string $step_id, string $location, string $entry, array $data ): bool {
		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );

		// Update the stored step data.
		$step_details[ $entry ] = $data;

		return $this->save_nox_profile_onboarding_step( $step_id, $location, $step_details );
	}

	/**
	 * Get a data entry from the NOX profile onboarding step details.
	 *
	 * @param string $step_id       The ID of the onboarding step.
	 * @param string $location      The location for which we are onboarding.
	 *                              This is a ISO 3166-1 alpha-2 country code.
	 * @param string $entry         The entry to get from the step `data`.
	 * @param mixed  $default_value The default value to return if the entry is not found.
	 *
	 * @return mixed The entry value from the NOX profile stored step data.
	 *               If the entry is not found, the default value is returned.
	 */
	private function get_nox_profile_onboarding_step_data_entry( string $step_id, string $location, string $entry, $default_value = false ) {
		$step_details_data = $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'data' );

		if ( ! isset( $step_details_data[ $entry ] ) ) {
			return $default_value;
		}

		return $step_details_data[ $entry ];
	}

	/**
	 * Save a data entry in the NOX profile onboarding step details.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param string $entry    The entry key under which to save in the step `data`.
	 * @param mixed  $data     The value to save.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 */
	private function save_nox_profile_onboarding_step_data_entry( string $step_id, string $location, string $entry, $data ): bool {
		$step_details_data = $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'data' );

		// Update the stored step data.
		$step_details_data[ $entry ] = $data;

		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'data', $step_details_data );
	}

	/**
	 * Get the IDs of the onboarding steps that are required for the given step.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 *
	 * @return array|string[] The IDs of the onboarding steps that are required for the given step.
	 */
	private function get_onboarding_step_required_steps( string $step_id ): array {
		switch ( $step_id ) {
			// Both the test account and business verification (live account) steps require a working WPCOM connection.
			case self::ONBOARDING_STEP_TEST_ACCOUNT:
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				return array(
					self::ONBOARDING_STEP_WPCOM_CONNECTION,
				);
			default:
				return array();
		}
	}

	/**
	 * Check if the requirements for an onboarding step are met.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step requirements are met.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 */
	private function check_onboarding_step_requirements( string $step_id, string $location ): bool {
		$requirements = $this->get_onboarding_step_required_steps( $step_id );

		foreach ( $requirements as $required_step_id ) {
			if ( $this->get_onboarding_step_status( $required_step_id, $location ) !== self::ONBOARDING_STEP_STATUS_COMPLETED ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the payment methods state for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding payment methods state.
	 */
	private function get_onboarding_payment_methods_state( string $location ): array {
		// First, get the recommended payment methods details from the provider.
		// We will use their enablement state as the default.
		// Note: The list is validated and standardized by the provider, so we don't need to do it here.
		$recommended_pms = $this->get_onboarding_recommended_payment_methods( $location );

		// Grab the stored payment methods state
		// (a key-value array of payment method IDs and if they should be automatically enabled or not).
		$step_pms_data = (array) $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'payment_methods' );

		$payment_methods_state = array();
		foreach ( $recommended_pms as $recommended_pm ) {
			$pm_id = $recommended_pm['id'];

			// Start with the recommended enabled state.
			$payment_methods_state[ $pm_id ] = $recommended_pm['enabled'];

			// Force enable if required.
			if ( $recommended_pm['required'] ) {
				$payment_methods_state[ $pm_id ] = true;
				continue;
			}

			// Check the stored state, if any.
			if ( isset( $step_pms_data[ $pm_id ] ) ) {
				$payment_methods_state[ $pm_id ] = filter_var( $step_pms_data[ $pm_id ], FILTER_VALIDATE_BOOLEAN );
			}
		}

		return $payment_methods_state;
	}

	/**
	 * Get the WPCOM (Jetpack) connection authorization details.
	 *
	 * @param string $return_url The URL to redirect to after the connection is set up.
	 *
	 * @return array The WPCOM connection authorization details.
	 */
	private function get_wpcom_connection_authorization( string $return_url ): array {
		return $this->proxy->call_static( Utils::class, 'get_wpcom_connection_authorization', $return_url );
	}

	/**
	 * Get the store's WPCOM (Jetpack) connection state.
	 *
	 * @return array The WPCOM connection state.
	 */
	private function get_wpcom_connection_state(): array {
		$is_connected        = $this->wpcom_connection_manager->is_connected();
		$has_connected_owner = $this->wpcom_connection_manager->has_connected_owner();

		return array(
			'has_working_connection' => $this->has_working_wpcom_connection(),
			'is_store_connected'     => $is_connected,
			'has_connected_owner'    => $has_connected_owner,
			'is_connection_owner'    => $has_connected_owner && $this->wpcom_connection_manager->is_connection_owner(),
		);
	}

	/**
	 * Check if the store has a working WPCOM connection.
	 *
	 * The store is considered to have a working WPCOM connection if:
	 * - The store is connected to WPCOM (blog ID and tokens are set).
	 * - The store connection has a connected owner (connection owner is set).
	 *
	 * @return bool Whether the store has a working WPCOM connection.
	 */
	private function has_working_wpcom_connection(): bool {
		return $this->wpcom_connection_manager->is_connected() && $this->wpcom_connection_manager->has_connected_owner();
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return boolean
	 */
	private function is_extension_active(): bool {
		return $this->proxy->call_function( 'class_exists', '\WC_Payments' );
	}

	/**
	 * Get the main payment gateway instance.
	 *
	 * @return \WC_Payment_Gateway The main payment gateway instance.
	 */
	private function get_payment_gateway(): \WC_Payment_Gateway {
		return $this->proxy->call_static( '\WC_Payments', 'get_gateway' );
	}

	/**
	 * Determine if WooPayments has an account set up.
	 *
	 * @return bool Whether WooPayments has an account set up.
	 */
	private function has_account(): bool {
		return $this->provider->is_account_connected( $this->get_payment_gateway() );
	}

	/**
	 * Determine if WooPayments has a valid, fully onboarded account set up.
	 *
	 * @return bool Whether WooPayments has a valid, fully onboarded account set up.
	 */
	private function has_valid_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_service = $this->proxy->call_static( '\WC_Payments', 'get_account_service' );

		return $account_service->is_stripe_account_valid();
	}

	/**
	 * Determine if WooPayments has a working account set up.
	 *
	 * This is a more specific check than has_valid_account() and checks if payments are enabled for the account.
	 *
	 * @return bool Whether WooPayments has a working account set up.
	 */
	private function has_working_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_service = $this->proxy->call_static( '\WC_Payments', 'get_account_service' );
		$account_status  = $account_service->get_account_status_data();

		return ! empty( $account_status['paymentsEnabled'] );
	}

	/**
	 * Determine if WooPayments has a test account set up.
	 *
	 * @return bool Whether WooPayments has a test account set up.
	 */
	private function has_test_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_service = $this->proxy->call_static( '\WC_Payments', 'get_account_service' );
		$account_status  = $account_service->get_account_status_data();

		return ! empty( $account_status['testDrive'] );
	}

	/**
	 * Determine if WooPayments has a live account set up.
	 *
	 * @return bool Whether WooPayments has a test account set up.
	 */
	private function has_live_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_service = $this->proxy->call_static( '\WC_Payments', 'get_account_service' );
		$account_status  = $account_service->get_account_status_data();

		return ! empty( $account_status['isLive'] );
	}

	/**
	 * Get the onboarding fields data for the KYC business verification.
	 *
	 * @return array The onboarding fields data.
	 * @throws Exception If the onboarding fields data could not be retrieved or there was an error.
	 */
	private function get_onboarding_kyc_fields(): array {
		// Call the WooPayments API to get the onboarding fields.
		$response = $this->proxy->call_static( Utils::class, 'rest_endpoint_get_request', '/wc/v3/payments/onboarding/fields' );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}

		if ( ! is_array( $response ) || ! isset( $response['data'] ) ) {
			throw new Exception( esc_html__( 'Failed to get onboarding fields data.', 'woocommerce' ) );
		}

		$fields = $response['data'];

		// If there is no available_countries entry, add it.
		if ( ! isset( $fields['available_countries'] ) && $this->proxy->call_function( 'is_callable', '\WC_Payments_Utils::supported_countries' ) ) {
			$fields['available_countries'] = $this->proxy->call_static( '\WC_Payments_Utils', 'supported_countries' );
		}

		return $fields;
	}

	/**
	 * Get the fallback URL for the embedded KYC flow.
	 *
	 * @return string The fallback URL for the embedded KYC flow.
	 */
	private function get_onboarding_kyc_fallback_url(): string {
		if ( $this->proxy->call_function( 'is_callable', '\WC_Payments_Account::get_connect_url' ) ) {
			return $this->proxy->call_static( '\WC_Payments_Account', 'get_connect_url', self::FROM_NOX_IN_CONTEXT );
		}

		// Fall back to the provider onboarding URL.
		return $this->provider->get_onboarding_url( $this->get_payment_gateway(), Utils::wc_payments_settings_url( self::ONBOARDING_PATH_BASE ) );
	}

	/**
	 * Get the WooPayments Overview page URL.
	 *
	 * @return string The WooPayments Overview page URL.
	 */
	private function get_overview_page_url(): string {
		if ( $this->proxy->call_function( 'is_callable', '\WC_Payments_Account::get_overview_page_url' ) ) {
			return add_query_arg(
				array(
					'from' => self::FROM_NOX_IN_CONTEXT,
				),
				$this->proxy->call_static( '\WC_Payments_Account', 'get_overview_page_url' )
			);
		}

		// Fall back to the known WooPayments Overview page URL.
		return add_query_arg(
			array(
				'page' => 'wc-admin',
				'path' => '/payments/overview',
				'from' => self::FROM_NOX_IN_CONTEXT,
			),
			admin_url( 'admin.php' )
		);
	}
}

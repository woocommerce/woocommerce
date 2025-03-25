<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments;

use Automattic\WooCommerce\Admin\API\OnboardingPlugins;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Exception;
use WC_Payments;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;
/**
 * WooPayments-specific Payments settings page service class.
 */
class WooPaymentsService {

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

	const NOX_PROFILE_OPTION_KEY = 'woocommerce_woopayments_nox_profile';

	const TRACKING_FROM = 'WCADMIN_PAYMENT_SETTINGS';

	/**
	 * The WooPayments provider instance.
	 *
	 * @var PaymentProviders\WooPayments
	 */
	private PaymentProviders\WooPayments $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->provider = new PaymentProviders\WooPayments();
	}

	/**
	 * Get the onboarding details for the settings page.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array The onboarding details.
	 * @throws Exception If the WooPayments plugin is not active or there was an error.
	 */
	public function get_onboarding_details( string $location, string $rest_path ): array {
		// If the WooPayments plugin is not active, we don't do onboarding.
		if ( ! $this->is_extension_active() ) {
			throw new Exception( 'WooPayments is not active.' );
		}

		return array(
			'state' => array(
				'started'   => $this->provider->is_onboarding_started( $this->get_payment_gateway() ),
				'completed' => $this->provider->is_onboarding_completed( $this->get_payment_gateway() ),
				'test_mode' => $this->provider->is_in_test_mode_onboarding( $this->get_payment_gateway() ),
				'dev_mode'  => $this->provider->is_in_dev_mode( $this->get_payment_gateway() ),
			),
			'steps' => $this->get_onboarding_steps_details( $location, trailingslashit( $rest_path ) . 'step' ),
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
	 * Get the onboarding steps details.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array[] The onboarding steps details.
	 * @throws Exception If there was an error generating the onboarding steps details.
	 */
	private function get_onboarding_steps_details( string $location, string $rest_path ): array {
		$details = array();

		// Add the payment methods onboarding step details.
		$details[] = array(
			'id'             => self::ONBOARDING_STEP_PAYMENT_METHODS,
			'path'           => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_PAYMENT_METHODS,
			'required_steps' => $this->get_onboarding_step_required_steps( self::ONBOARDING_STEP_PAYMENT_METHODS ),
			'status'         => $this->get_onboarding_step_status( self::ONBOARDING_STEP_PAYMENT_METHODS, $location ),
			'errors'         => array(),
			'actions'        => array(
				'start'    => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/start' ),
				),
				'save'     => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/save' ),
				),
				'finish' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/finish' ),
				),
			),
			'context'        => array(
				'payment_methods' => $this->get_onboarding_payment_methods( $location ),
			),
		);

		// Add the WPCOM connection onboarding step details.
		$wpcom_step_details = array(
			'id'             => self::ONBOARDING_STEP_WPCOM_CONNECTION,
			'path'           => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_WPCOM_CONNECTION,
			'required_steps' => $this->get_onboarding_step_required_steps( self::ONBOARDING_STEP_WPCOM_CONNECTION ),
			'status'         => $this->get_onboarding_step_status( self::ONBOARDING_STEP_WPCOM_CONNECTION, $location ),
			'errors'         => array(),
		);

		// If the WPCOM connection is already set up, we don't need to add anything more.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $wpcom_step_details['status'] ) {
			// Try to generate the authorization URL.
			$wpcom_connection = $this->get_wpcom_connection_authorization( Utils::wc_payments_settings_url( self::ONBOARDING_PATH_BASE ), 'woocommerce' );
			if ( ! $wpcom_connection['success'] ) {
				$wpcom_step_details['errors'] = $wpcom_connection['errors'];
			}
			$wpcom_step_details['actions'] = array(
				'start' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/start' ),
				),
				'auth'  => array(
					'type' => self::ACTION_TYPE_REDIRECT,
					'href' => $wpcom_connection['url'],
				),
			);
		}

		$details[] = $wpcom_step_details;

		// Add the test account onboarding step details.
		$test_account_step_details = array(
			'id'             => self::ONBOARDING_STEP_TEST_ACCOUNT,
			'path'           => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_TEST_ACCOUNT,
			'required_steps' => $this->get_onboarding_step_required_steps( self::ONBOARDING_STEP_TEST_ACCOUNT ),
			'status'         => $this->get_onboarding_step_status( self::ONBOARDING_STEP_TEST_ACCOUNT, $location ),
			'errors'         => array(),
		);

		// If the step is not completed, we need to add the actions.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $test_account_step_details['status'] ) {
			$test_account_step_details['actions'] = array(
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

		$details[] = $test_account_step_details;

		// Add the live account business verification onboarding step details.
		$business_verification_step_details = array(
			'id'             => self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			'path'           => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			'required_steps' => $this->get_onboarding_step_required_steps( self::ONBOARDING_STEP_BUSINESS_VERIFICATION ),
			'status'         => $this->get_onboarding_step_status( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location ),
			'errors'         => array(),
			'context'        => array(
				'fields' => $this->get_onboarding_kyc_fields(),
			),
		);

		// If the step is not completed, we need to add the actions.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $business_verification_step_details['status'] ) {
			$business_verification_step_details['actions'] = array(
				'start'         => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/start' ),
				),
				'save'          => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/save' ),
				),
				'session_start' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/session/start' ),
				),
				'finish'        => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/finish' ),
				),
			);
		}

		$details[] = $business_verification_step_details;

		return $details;
	}

	/**
	 * Get the status of an onboarding step.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return string The status of the onboarding step.
	 */
	public function get_onboarding_step_status( string $step_id, string $location ): string {
		// First, we check the status of the onboarding step based on the current state of the store.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				// No custom checks, for now.
				break;
			case self::ONBOARDING_STEP_WPCOM_CONNECTION:
				if ( Utils::store_has_wpcom_connection() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
			case self::ONBOARDING_STEP_TEST_ACCOUNT:
				if ( ! $this->has_account() ) {
					return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
				}

				// A valid, fully onboarded account that is a test account marks this step as complete.
				if ( $this->has_valid_account() && $this->has_test_account() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				// If no account or the current account is a test account,
				// then we didn't start the live account business verification.
				if ( ! $this->has_account() || $this->has_test_account() ) {
					return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
				}

				// If the current account is fully onboarded and is not a test account,
				// we consider the business verification step as completed.
				if ( $this->has_valid_account() ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
		}

		// Second, try to determine the status of the onboarding step based on the saved data.
		$nox_profile = get_option( self::NOX_PROFILE_OPTION_KEY, array() );
		if ( ! empty( $nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'] ) ) {
			// We take a waterfall approach, where completed supersedes started.
			$step_statuses = $nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['statuses'];
			if ( ! empty( $step_statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) ) {
				return self::ONBOARDING_STEP_STATUS_COMPLETED;
			}
			if ( ! empty( $step_statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
				return self::ONBOARDING_STEP_STATUS_STARTED;
			}
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
	 * @throws Exception If the given onboarding step ID is invalid.
	 */
	public function set_onboarding_step_started( string $step_id, string $location, bool $overwrite = false ): bool {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		// Check step requirements.
		if ( ! $this->check_onboarding_step_requirements( $step_id, $location ) ) {
			throw new Exception( 'Onboarding step can no be started because requirements are not met.' );
		}

		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );
		if ( empty( $step_details['statuses'] ) ) {
			$step_details['statuses'] = array();
		}

		if ( ! $overwrite && ! empty( $step_details['statuses'][ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
			return true;
		}

		// Mark the step as started and record the timestamp.
		$step_details['statuses'][ self::ONBOARDING_STEP_STATUS_STARTED ] = time();

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step( $step_id, $location, $step_details );
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
	 * @throws Exception If the given onboarding step ID is invalid.
	 */
	public function set_onboarding_step_completed( string $step_id, string $location, bool $overwrite = false ): bool {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		// Check step requirements.
		if ( ! $this->check_onboarding_step_requirements( $step_id, $location ) ) {
			throw new Exception( 'Onboarding step requirements are not met.' );
		}

		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );
		if ( empty( $step_details['statuses'] ) ) {
			$step_details['statuses'] = array();
		}

		if ( ! $overwrite && ! empty( $step_details['statuses'][ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) ) {
			return true;
		}

		// Mark the step as completed and record the timestamp.
		$step_details['statuses'][ self::ONBOARDING_STEP_STATUS_COMPLETED ] = time();

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step( $step_id, $location, $step_details );
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
	 * @throws Exception If the given onboarding step ID or data are invalid.
	 */
	public function onboarding_step_save( string $step_id, string $location, array $request_data ): bool {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		$step_details = $this->get_nox_profile_onboarding_step( $step_id, $location );
		if ( empty( $step_details['data'] ) ) {
			$step_details['data'] = array();
		}

		// We support save for only certain steps, each with its own data structure.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				if ( ! isset( $request_data['payment_methods'] ) || ! is_array( $request_data['payment_methods'] ) ) {
					throw new Exception( 'Invalid onboarding step data.' );
				}

				$step_details['data']['payment_methods'] = $request_data['payment_methods'];
				break;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				if ( ! isset( $request_data['self_assessment'] ) || ! is_array( $request_data['self_assessment'] ) ) {
					throw new Exception( 'Invalid onboarding step data.' );
				}

				$step_details['data']['self_assessment'] = $request_data['self_assessment'];
				break;
			default:
				throw new Exception( 'Invalid onboarding step ID.' );
		}

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step( $step_id, $location, $step_details );
	}

	/**
	 * Check an onboarding step's status/progress.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The check result.
	 * @throws Exception If the given onboarding step ID is invalid.
	 */
	public function onboarding_step_check( string $step_id, string $location ): array {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		// Check step requirements.
		if ( ! $this->check_onboarding_step_requirements( $step_id, $location ) ) {
			throw new Exception( 'Onboarding step requirements are not met.' );
		}

		// Just return the step status, for now.
		return array( 'status' => $this->get_onboarding_step_status( $step_id, $location ) );
	}

	/**
	 * Get the payment methods details for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding payment methods details.
	 */
	public function get_onboarding_payment_methods( string $location ): array {
		// First, get the recommended payment methods details from the provider.
		$payment_methods = $this->provider->get_recommended_payment_methods( $this->get_payment_gateway(), $location );

		// Grab the stored payment methods state
		// (a key-value array of payment method IDs and if they should be automatically enabled or not).
		$step_data = $this->get_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'data' );
		if ( ! empty( $step_data['payment_methods'] ) && is_array( $step_data['payment_methods'] ) ) {
			foreach ( $payment_methods as $key => $payment_method ) {
				// Force enable and skip required payment methods since these should always be enabled.
				if ( ! empty( $payment_method['required'] ) ) {
					$payment_methods[ $key ]['enabled'] = true;
					continue;
				}

				// Go through the recommended payment methods and overwrite their enabled status with the stored one.
				if ( isset( $step_data['payment_methods'][ $payment_method['id'] ] ) ) {
					$payment_methods[ $key ]['enabled'] = wc_string_to_bool( $step_data['payment_methods'][ $payment_method['id'] ] );
				}
			}
		}

		return $payment_methods;
	}

	/**
	 * Initialize the test account for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array|\WP_Error The result of the test account initialization.
	 * @throws Exception If there was an error initializing the test account.
	 */
	public function onboarding_test_account_init( string $location ) {
		// Nothing to do if we already have a valid test account.
		if ( $this->has_account() && $this->has_test_account() ) {
			return array(
				'message' => __( 'Test account is already set up.', 'woocommerce' ),
			);
		}

		// Check if the test account is already in progress.
		$step_data = $this->get_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_TEST_ACCOUNT, $location, 'data' );
		if ( ! empty( $step_data['in_progress'] ) ) {
			return array(
				'message' => __( 'Test account creation is already in progress.', 'woocommerce' ),
			);
		}

		// Nothing to do if there is an account, but it is not a test account.
		if ( $this->has_account() ) {
			return array(
				'message' => __( 'An account is already set up. Reset the onboarding first.', 'woocommerce' ),
			);
		}

		// Check step requirements.
		if ( ! $this->check_onboarding_step_requirements( self::ONBOARDING_STEP_TEST_ACCOUNT, $location ) ) {
			throw new Exception( 'Onboarding step requirements are not met.' );
		}

		// Mark the test account step as in progress.
		$step_data['in_progress'] = true;
		$this->save_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_TEST_ACCOUNT, $location, 'data', $step_data );

		// Call the WooPayments API to initialize the test account.
		$response = Utils::rest_endpoint_post_request(
			'/wc/v3/payments/onboarding/test_account/init',
			array(
				'capabilities' => ( ! empty( $step_data['payment_methods'] ) && is_array( $step_data['payment_methods'] ) ) ? $step_data['payment_methods'] : array(),
				'source'       => ! empty( $tracking_source ) ? $tracking_source : self::TRACKING_FROM,
				'from'         => self::TRACKING_FROM,
			)
		);

		// Remove the in progress flag.
		unset( $step_data['in_progress'] );
		$this->save_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_TEST_ACCOUNT, $location, 'data', $step_data );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ), esc_attr( $response->get_error_code() ) );
		}

		if ( ! is_array( $response ) || empty( $response['success'] ) ) {
			throw new Exception( esc_html__( 'Failed to initialize the test account.', 'woocommerce' ) );
		}

		// Mark the test account step as completed.
		$this->set_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

		return $response;
	}

	/**
	 * Get the onboarding KYC progressive onboarding (PO) eligibility.
	 *
	 * @param string $location        The location for which we are onboarding.
	 *                                This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $self_assessment Optional. The self-assessment data.
	 *                                If not provided, the stored data will be used.
	 *
	 * @return array The KYC PO eligibility data.
	 * @throws Exception If the eligibility could not be determined or there was an error.
	 */
	public function get_onboarding_kyc_po_eligible( string $location, array $self_assessment = array() ): array {
		if ( empty( $self_assessment ) ) {
			// Get the stored self-assessment data.
			$step_data = $this->get_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'data' );
			if ( ! empty( $step_data['self_assessment'] ) && is_array( $step_data['self_assessment'] ) ) {
				$self_assessment = $step_data['self_assessment'];
			}
		}

		// Prepare the needed details.
		$request_payload = array(
			'business' => array(
				'country' => $self_assessment['country'] ?? $location,
				'type'    => $self_assessment['business_type'] ?? '',
				'mcc'     => $self_assessment['mcc'] ?? '',
			),
			'store'    => array(
				'annual_revenue'    => $self_assessment['annual_revenue'] ?? '',
				'go_live_timeframe' => $self_assessment['go_live_timeframe'] ?? '',
			),
		);

		// Call the WooPayments API to determine PO eligibility.
		$response_data = Utils::rest_endpoint_post_request( '/wc/v3/payments/onboarding/router/po_eligible', $request_payload );

		if ( is_wp_error( $response_data ) ) {
			throw new Exception( esc_html( $response_data->get_error_message() ), esc_attr( $response_data->get_error_code() ) );
		}

		if ( ! is_array( $response_data ) || ! isset( $response_data['result'] ) ) {
			throw new Exception( esc_html__( 'Failed to determine KYC progressive onboarding eligibility.', 'woocommerce' ) );
		}

		return array(
			'eligible' => ( 'eligible' === $response_data['result'] ),
			'context'  => $response_data['context'] ?? array(),
		);
	}

	/**
	 * Get the onboarding KYC account session.
	 *
	 * @param string $location        The location for which we are onboarding.
	 *                                This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $self_assessment Optional. The self-assessment data.
	 *                                If not provided, the stored data will be used.
	 * @param bool   $progressive     Optional. Whether the KYC session is for progressive onboarding.
	 *                                Default is to get the KYC session for regular onboarding.
	 *
	 * @return array The KYC account session data.
	 * @throws Exception If the KYC session data could not be retrieved or there was an error.
	 */
	public function get_onboarding_kyc_session( string $location, array $self_assessment = array(), bool $progressive = false ): array {
		if ( empty( $self_assessment ) ) {
			// Get the stored self-assessment data.
			$step_data = $this->get_nox_profile_onboarding_step_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'data' );
			if ( ! empty( $step_data['self_assessment'] ) && is_array( $step_data['self_assessment'] ) ) {
				$self_assessment = $step_data['self_assessment'];
			}
		}

		// Call the WooPayments API to get the KYC session.
		$account_session = Utils::rest_endpoint_get_request(
			'/wc/v3/payments/onboarding/kyc/session',
			array(
				'progressive'     => $progressive,
				'self_assessment' => $self_assessment,
			)
		);

		if ( is_wp_error( $account_session ) ) {
			throw new Exception( esc_html( $account_session->get_error_message() ), esc_attr( $account_session->get_error_code() ) );
		}

		if ( ! is_array( $account_session ) ) {
			throw new Exception( esc_html__( 'Failed to get KYC session data.', 'woocommerce' ) );
		}

		// Add the user locale to the account session data to allow for localized KYC sessions.
		$account_session['locale'] = get_user_locale();

		return $account_session;
	}

	/**
	 * Finish the onboarding KYC account session.
	 *
	 * @param string $location        The location for which we are onboarding.
	 *                                This is a ISO 3166-1 alpha-2 country code.
	 * @param string $tracking_source Optional. The tracking source for the KYC session.
	 *                                If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws Exception If the KYC session could not be finished or there was an error.
	 */
	public function finish_onboarding_kyc_session( string $location, string $tracking_source = '' ): array {
		// Call the WooPayments API to finalize the KYC session.
		$response = Utils::rest_endpoint_post_request(
			'/wc/v3/payments/onboarding/kyc/finalize',
			array(
				'source' => ! empty( $tracking_source ) ? $tracking_source : self::TRACKING_FROM,
				'from'   => self::TRACKING_FROM,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ), esc_attr( $response->get_error_code() ) );
		}

		if ( ! is_array( $response ) ) {
			throw new Exception( esc_html__( 'Failed to finish the KYC session.', 'woocommerce' ) );
		}

		// Mark the business verification step as completed.
		$this->set_onboarding_step_completed( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		return $response;
	}

	/**
	 * Get the entire stored NOX profile data
	 *
	 * @return array The stored NOX profile.
	 */
	private function get_nox_profile(): array {
		$nox_profile = get_option( self::NOX_PROFILE_OPTION_KEY, array() );

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

		return update_option( self::NOX_PROFILE_OPTION_KEY, $nox_profile, false );
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
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step requirements are met.
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
	 * Get the WPCOM (Jetpack) connection authorization details.
	 *
	 * @param string $redirect_url The URL to redirect to after the connection is set up.
	 * @param string $from         The source of the connection setup.
	 *
	 * @return array The WPCOM connection authorization details.
	 */
	private function get_wpcom_connection_authorization( string $redirect_url, string $from ): array {
		$plugin_onboarding = new OnboardingPlugins();

		$request = new WP_REST_Request();
		$request->set_param( 'redirect_url', $redirect_url );
		$request->set_param( 'from', $from );

		return $plugin_onboarding->get_jetpack_authorization_url( $request );
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return boolean
	 */
	private function is_extension_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Get the main payment gateway instance.
	 *
	 * @return \WC_Payment_Gateway_WCPay The main payment gateway instance.
	 */
	private function get_payment_gateway(): \WC_Payment_Gateway_WCPay {
		return \WC_Payments::get_gateway();
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

		return WC_Payments::get_account_service()->is_stripe_account_valid();
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

		$account_status = WC_Payments::get_account_service()->get_account_status_data();

		return ! empty( $account_status['testDrive'] );
	}

	/**
	 * Get the onboarding fields data for the KYC business verification.
	 *
	 * @return array The onboarding fields data.
	 * @throws Exception If the onboarding fields data could not be retrieved or there was an error.
	 */
	private function get_onboarding_kyc_fields(): array {
		// Call the WooPayments API to get the onboarding fields.
		$response = Utils::rest_endpoint_get_request( '/wc/v3/payments/onboarding/fields' );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ), esc_attr( $response->get_error_code() ) );
		}

		if ( ! is_array( $response ) || ! isset( $response['data'] ) ) {
			throw new Exception( esc_html__( 'Failed to get onboarding fields data.', 'woocommerce' ) );
		}

		$fields = $response['data'];

		// If there is no available_countries entry, add it.
		if ( ! isset( $fields['available_countries'] ) && is_callable( '\WC_Payments_Utils::supported_countries' ) ) {
			$fields['available_countries'] = \WC_Payments_Utils::supported_countries();
		}

		return $fields;
	}
}

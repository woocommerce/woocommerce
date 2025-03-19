<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments;

use Automattic\WooCommerce\Admin\API\OnboardingPlugins;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Utilities\RestApiUtil;
use Exception;
use WC_Payments;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;
/**
 * WooPayments-specific Payments settings page service class.
 */
class WooPaymentsService {

	const ONBOARDING_PATH_BASE = '/woopayments/onboarding';

	const ONBOARDING_STEP_PAYMENT_METHODS = 'payment_methods';
	const ONBOARDING_STEP_WPCOM_CONNECTION = 'wpcom_connection';
	const ONBOARDING_STEP_TEST_ACCOUNT = 'test_account';
	const ONBOARDING_STEP_BUSINESS_VERIFICATION = 'business_verification';

	const ONBOARDING_STEP_STATUS_NOT_STARTED = 'not_started';
	const ONBOARDING_STEP_STATUS_STARTED = 'started';
	const ONBOARDING_STEP_STATUS_COMPLETED = 'completed';
	const ONBOARDING_STEP_STATUS_ERROR = 'error';

	const ACTION_TYPE_REST = 'REST';
	const ACTION_TYPE_REDIRECT = 'REDIRECT';

	const NOX_PROFILE_OPTION_KEY = 'woocommerce_woopayments_nox_profile';

	/**
	 * The payments settings page service.
	 *
	 * @var RestApiUtil
	 */
	private RestApiUtil $rest_api_util;

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
	final public function init( RestApiUtil $rest_api_util ): void {
		$this->rest_api_util = $rest_api_util;
		$this->provider      = new PaymentProviders\WooPayments();
	}

	/**
	 * Get the onboarding details for the settings page.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array The onboarding details.
	 * @throws Exception If the WooPayments plugin is not active.
	 */
	public function get_onboarding_details( string $location, string $rest_path ): array {
		// If the WooPayments plugin is not active, we don't do onboarding.
		if ( ! $this->is_woopayments_active() ) {
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
		return in_array( $step_id, array(
			self::ONBOARDING_STEP_PAYMENT_METHODS,
			self::ONBOARDING_STEP_WPCOM_CONNECTION,
			self::ONBOARDING_STEP_TEST_ACCOUNT,
			self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
		), true );
	}

	/**
	 * Get the onboarding steps details.
	 *
	 * @param string $location  The location for which we are onboarding.
	 *                          This is a ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array[] The onboarding steps details.
	 */
	private function get_onboarding_steps_details( string $location, string $rest_path ): array {
		$details = array();

		// Add the payment methods onboarding step details.
		$details[] = array(
			'id'                          => self::ONBOARDING_STEP_PAYMENT_METHODS,
			'path'                        => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_PAYMENT_METHODS,
			'required_steps'              => array(),
			'status'                      => $this->get_onboarding_step_status( self::ONBOARDING_STEP_PAYMENT_METHODS, $location ),
			'errors'                      => array(),
			'actions'                     => array(
				'start'    => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/start' ),
				),
				'save'    => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/save' ),
				),
				'complete' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_PAYMENT_METHODS . '/complete' ),
				),
			),
			'data'                        => array(
				'recommended_payment_methods' => $this->provider->get_recommended_payment_methods( $this->get_payment_gateway(), $location ),
			),
		);

		// Add the WPCOM connection onboarding step details.
		$wpcom_step_details = array(
			'id'              => self::ONBOARDING_STEP_WPCOM_CONNECTION,
			'path'            => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_WPCOM_CONNECTION,
			'required_steps'  => array(),
			'status'          => $this->get_onboarding_step_status( self::ONBOARDING_STEP_WPCOM_CONNECTION, $location ),
			'errors'          => array(),
		);

		// If the WPCOM connection is already set up, we don't need to add anything more.
		if ( $wpcom_step_details['status'] !== self::ONBOARDING_STEP_STATUS_COMPLETED ) {
			// Try to generate the authorization URL.
			$wpcom_connection = $this->get_wpcom_connection_authorization( Utils::wc_payments_settings_url( self::ONBOARDING_PATH_BASE ), 'woocommerce' );
			if ( ! $wpcom_connection['success'] ) {
				$wpcom_step_details['status'] = self::ONBOARDING_STEP_STATUS_ERROR;
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
			'id'              => self::ONBOARDING_STEP_TEST_ACCOUNT,
			'path'            => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_TEST_ACCOUNT,
			'required_steps'  => array( self::ONBOARDING_STEP_PAYMENT_METHODS, self::ONBOARDING_STEP_WPCOM_CONNECTION ),
			'status'          => $this->get_onboarding_step_status( self::ONBOARDING_STEP_TEST_ACCOUNT, $location ),
			'errors'          => array(),
		);

		// If the step is not completed, we need to add the actions.
		if ( $test_account_step_details['status'] !== self::ONBOARDING_STEP_STATUS_COMPLETED ) {
			$test_account_step_details['actions'] = array(
				'start' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/start' ),
				),
				'check' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/check' ),
				),
			);
		}

		$details[] = $test_account_step_details;

		// Add the live account business verification onboarding step details.
		$business_verification_step_details = array(
			'id'              => self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			'path'            => trailingslashit( self::ONBOARDING_PATH_BASE ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			'required_steps'  => array( self::ONBOARDING_STEP_PAYMENT_METHODS, self::ONBOARDING_STEP_WPCOM_CONNECTION ),
			'status'          => $this->get_onboarding_step_status( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location ),
			'errors'          => array(),
		);

		// If the step is not completed, we need to add the actions.
		if ( $business_verification_step_details['status'] !== self::ONBOARDING_STEP_STATUS_COMPLETED ) {
			$business_verification_step_details['actions'] = array(
				'start' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/start' ),
				),
				'save' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/save' ),
				),
				'kyc_session' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session' ),
				),
				'complete' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/complete' ),
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
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				// @todo Implement the logic to check the status of the payment methods onboarding step.
				return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
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

		// We default to not started.
		return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
	}

	/**
	 * Mark an onboarding step as started.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step was marked as started.
	 * @throws Exception If the given onboarding step ID is invalid.
	 */
	public function set_onboarding_step_started( string $step_id, string $location ): bool {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		$nox_profile = get_option( self::NOX_PROFILE_OPTION_KEY, array() );

		if ( empty( $nox_profile ) ) {
			$nox_profile = array();
		} else {
			$nox_profile = maybe_unserialize( $nox_profile );
		}

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
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'] = array();
		}

		// Mark the step as started and record the timestamp.
		$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'][ self::ONBOARDING_STEP_STATUS_STARTED ] = time() ;

		return update_option( self::NOX_PROFILE_OPTION_KEY, $nox_profile, false );
	}

	/**
	 * Mark an onboarding step as completed.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step was marked as completed.
	 * @throws Exception If the given onboarding step ID is invalid.
	 */
	public function set_onboarding_step_completed( string $step_id, string $location ): bool {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		$nox_profile = get_option( self::NOX_PROFILE_OPTION_KEY, array() );

		if ( empty( $nox_profile ) ) {
			$nox_profile = array();
		} else {
			$nox_profile = maybe_unserialize( $nox_profile );
		}

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
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'] = array();
		}

		// Mark the step as completed and record the timestamp.
		$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['status'][ self::ONBOARDING_STEP_STATUS_COMPLETED ] = time() ;

		return update_option( self::NOX_PROFILE_OPTION_KEY, $nox_profile, false );
	}

	/**
	 * Save the data for an onboarding step.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 * @param array  $request_data The entire data received in the request.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 * @throws Exception If the given onboarding step ID or data are invalid.
	 */
	public function onboarding_step_save( string $step_id, string $location, array $request_data ) {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new Exception( 'Invalid onboarding step ID.' );
		}

		$nox_profile = get_option( self::NOX_PROFILE_OPTION_KEY, array() );

		if ( empty( $nox_profile ) ) {
			$nox_profile = array();
		} else {
			$nox_profile = maybe_unserialize( $nox_profile );
		}

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
		if ( empty( $nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['data'] ) ) {
			$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['data'] = array();
		}

		// We support save for only certain steps.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
				if ( ! isset( $request_data['payment_methods'] ) || ! is_array( $request_data['payment_methods'] ) ) {
					throw new Exception( 'Invalid onboarding step data.' );
				}

				$nox_profile['onboarding'][ $location ]['steps'][ $step_id ]['data']['payment_methods'] = $request_data['payment_methods'];
				break;
			default:
				return false;
		}

		return update_option( self::NOX_PROFILE_OPTION_KEY, $nox_profile, false );
	}

	/**
	 * Get the WPCOM (Jetpack) connection authorization details.
	 *
	 * @param string $redirect_url The URL to redirect to after the connection is set up.
	 * @param string $from
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
	private function is_woopayments_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Get the main payment gateway instance.
	 *
	 * @return \WC_Payment_Gateway_WCPay
	 */
	private function get_payment_gateway(): \WC_Payment_Gateway_WCPay {
		return \WC_Payments::get_gateway();
	}

	/**
	 * Determine if WooPayments has an account set up.
	 *
	 * @return bool
	 */
	private function has_account(): bool {
		return $this->provider->is_account_connected( $this->get_payment_gateway() );
	}

	/**
	 * Determine if WooPayments has a valid, fully onboarded account set up.
	 *
	 * @return bool
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
	 * @return bool
	 */
	private function has_test_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_status = WC_Payments::get_account_service()->get_account_status_data();

		return ! empty( $account_status['testDrive'] );
	}
}

<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\Jetpack\Connection\Manager as WPCOM_Connection_Manager;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiArgumentException;
use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WP_Error;
use WP_Http;

defined( 'ABSPATH' ) || exit;
/**
 * WooPayments-specific Payments settings page service class.
 *
 * @internal
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

	/**
	 * A step is not started if the user has not interacted with it yet.
	 */
	const ONBOARDING_STEP_STATUS_NOT_STARTED = 'not_started';

	/**
	 * A step should be considered started if the user has interacted with it.
	 * There will be cases where a step may be auto-started based on the current state of the store.
	 */
	const ONBOARDING_STEP_STATUS_STARTED = 'started';

	/**
	 * A step is completed if the user has successfully completed it.
	 * This is the final state of a step.
	 */
	const ONBOARDING_STEP_STATUS_COMPLETED = 'completed';

	/**
	 * Failure generally refers to some error that occurred during a step action.
	 * Retrying the action should be possible and lead to a different step status.
	 */
	const ONBOARDING_STEP_STATUS_FAILED = 'failed';

	/**
	 * Blocked generally refers to a step can't progress to a completed state due to some technical requirements
	 * that are beyond the purview of the Payments Settings page or the WooPayments extension.
	 * Most of the time, the reasons will be environment-related.
	 * For example, the store may not use HTTPS, or live onboarding might be prevented due to environment settings.
	 */
	const ONBOARDING_STEP_STATUS_BLOCKED = 'blocked';

	const ACTION_TYPE_REST     = 'REST';
	const ACTION_TYPE_REDIRECT = 'REDIRECT';

	const NOX_PROFILE_OPTION_KEY    = 'woocommerce_woopayments_nox_profile';
	const NOX_ONBOARDING_LOCKED_KEY = 'woocommerce_woopayments_nox_onboarding_locked';

	const SESSION_ENTRY_DEFAULT = 'settings_payments';
	const SESSION_ENTRY_LYS     = 'lys';

	const FROM_PAYMENT_SETTINGS = 'WCADMIN_PAYMENT_SETTINGS';
	const FROM_NOX_IN_CONTEXT   = 'WCADMIN_NOX_IN_CONTEXT';
	const FROM_KYC              = 'KYC';
	const FROM_WPCOM            = 'WPCOM';

	const WPCOM_CONNECTION_RETURN_PARAM = 'wpcom_connection_return';

	const EVENT_PREFIX = 'settings_payments_woopayments_';

	/**
	 * The PaymentsProviders instance.
	 *
	 * @var PaymentsProviders
	 */
	private PaymentsProviders $payments_providers;

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
	 * @var PaymentsProviders\PaymentGateway
	 */
	private PaymentsProviders\PaymentGateway $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @param PaymentsProviders $payment_providers The PaymentsProviders instance.
	 * @param LegacyProxy       $proxy             The LegacyProxy instance.
	 *
	 * @internal
	 */
	final public function init( PaymentsProviders $payment_providers, LegacyProxy $proxy ): void {
		$this->payments_providers = $payment_providers;
		$this->proxy              = $proxy;

		$this->wpcom_connection_manager = $this->proxy->get_instance_of( WPCOM_Connection_Manager::class, 'woocommerce' );
		$this->provider                 = $this->payments_providers->get_payment_gateway_provider_instance( self::GATEWAY_ID );
	}

	/**
	 * Get the onboarding details for the settings page.
	 *
	 * @param string      $location  The location for which we are onboarding.
	 *                               This is an ISO 3166-1 alpha-2 country code.
	 * @param string      $rest_path The REST API path to use for constructing REST API URLs.
	 * @param string|null $source    Optional. The source for the onboarding flow.
	 *
	 * @return array The onboarding details.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 * @throws Exception If there were errors when generating the onboarding details.
	 */
	public function get_onboarding_details( string $location, string $rest_path, ?string $source = null ): array {
		// Since getting the onboarding details is not idempotent, we will check it as an action.
		$this->check_if_onboarding_action_is_acceptable();

		$source = $this->validate_onboarding_source( $source );

		$onboarding_started = $this->provider->is_onboarding_started( $this->get_payment_gateway() );
		if ( ! $onboarding_started && ! empty( $this->get_nox_profile_onboarding( $location ) ) ) {
			// If the onboarding profile is stored, we consider the onboarding started.
			$onboarding_started = true;
		}

		return array(
			// This state is high-level data, independent of the type of onboarding flow.
			'state'   => array(
				'started'   => $onboarding_started,
				'completed' => $this->provider->is_onboarding_completed( $this->get_payment_gateway() ),
				'test_mode' => $this->provider->is_in_test_mode_onboarding( $this->get_payment_gateway() ),
				'dev_mode'  => $this->provider->is_in_dev_mode( $this->get_payment_gateway() ),
			),
			'steps'   => $this->get_onboarding_steps( $location, trailingslashit( $rest_path ) . 'step', $source ),
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return string The status of the onboarding step.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 */
	public function get_onboarding_step_status( string $step_id, string $location ): string {
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new ApiArgumentException(
				'woocommerce_woopayments_onboarding_invalid_step_id',
				esc_html__( 'Invalid onboarding step ID.', 'woocommerce' ),
				(int) WP_Http::BAD_REQUEST
			);
		}

		$meets_requirements = $this->check_onboarding_step_requirements( $step_id, $location );

		// First, determine if the step should be reported as completed based on the current state of the store.
		// The step can only be auto-completed if the requirements are met.
		if ( $meets_requirements ) {
			switch ( $step_id ) {
				case self::ONBOARDING_STEP_PAYMENT_METHODS:
					// If there is already a valid account, report the step as completed
					// since allowing the user to configure payment methods won't have any effect.
					if ( $this->has_valid_account() ) {
						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}
					break;
				case self::ONBOARDING_STEP_WPCOM_CONNECTION:
					// If we have a working WPCOM connection, report the step as completed.
					// The step can only be auto-completed if the requirements are met.
					if ( $this->has_working_wpcom_connection() ) {
						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}
					break;
				case self::ONBOARDING_STEP_TEST_ACCOUNT:
					// If the account is a valid, working test or sandbox account, the step is completed.
					if ( ( $this->has_test_account() || $this->has_sandbox_account() ) && $this->has_valid_account() && $this->has_working_account() ) {
						// Since it takes a while for the account to be fully working after the test account initialization,
						// we will force mark the step as completed here, if it is not already.
						// This is a fail-safe to guard against the case when the frontend doesn't mark the step as completed.
						// The step has no reason to be blocked or failed.
						$this->clear_onboarding_step_failed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );
						$this->clear_onboarding_step_blocked( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );
						$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}
					break;
				case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
					// The step can only be auto-completed if the requirements are met.
					// If the current account is fully onboarded and is a live account,
					// we report the business verification step as completed.
					if ( $this->has_valid_account() && $this->has_live_account() ) {
						return self::ONBOARDING_STEP_STATUS_COMPLETED;
					}
					break;
			}
		}

		// Second, try to determine the status of the onboarding step based on the step's stored statuses.
		// We take a waterfall approach: completed > blocked > failed > started > not started.
		// Reporting a completed status involves additional logic.
		switch ( $step_id ) {
			case self::ONBOARDING_STEP_WPCOM_CONNECTION:
				// Ignore any completed stored statuses because of the critical nature of the WPCOM connection.
				break;
			case self::ONBOARDING_STEP_TEST_ACCOUNT:
				// If there is a stored completed status, we respect that IF there is NO invalid test account.
				// This is the case when the user first creates a test account and then switches to live.
				// The step can only be completed if the requirements are met.
				if ( $meets_requirements &&
					$this->was_onboarding_step_marked_completed( $step_id, $location ) &&
					! ( $this->has_test_account() && ! $this->has_valid_account() )
				) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
			case self::ONBOARDING_STEP_BUSINESS_VERIFICATION:
				// The step can only be completed if the requirements are met. Otherwise, ignore the stored completed status.
				// Sanity check: we only report the completed status if there is a live account and the account is valid (i.e. completed KYC).
				if ( $meets_requirements &&
					$this->was_onboarding_step_marked_completed( $step_id, $location ) &&
					$this->has_valid_account() &&
					( $this->has_live_account() || $this->has_sandbox_account() )
				) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}
				break;
			case self::ONBOARDING_STEP_PAYMENT_METHODS:
			default:
				// The step can only be completed if the requirements are met. Otherwise, ignore the stored completed status.
				if ( $meets_requirements && $this->was_onboarding_step_marked_completed( $step_id, $location ) ) {
					return self::ONBOARDING_STEP_STATUS_COMPLETED;
				}

				break;
		}
		// Blocked and failed statuses are only reported if the step's requirements are met.
		if ( $meets_requirements ) {
			if ( $this->is_onboarding_step_blocked( $step_id, $location ) ) {
				return self::ONBOARDING_STEP_STATUS_BLOCKED;
			}
			if ( $this->is_onboarding_step_failed( $step_id, $location ) ) {
				return self::ONBOARDING_STEP_STATUS_FAILED;
			}
		}
		if ( $this->was_onboarding_step_marked_started( $step_id, $location ) ) {
			// Special treatment for the test account step:
			// If the step was marked as started more than 1 minutes ago (plenty of time for the slowest of webhooks to
			// come through) and it is obviously not completed, and there is no account connected,
			// we will unmark it as started (aka clean its progress). Something went wrong with the step!
			// This is an auto-healing measure to prevent the step from being stuck in a started state indefinitely.
			if ( self::ONBOARDING_STEP_TEST_ACCOUNT === $step_id && ! $this->has_account() ) {
				$statuses          = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );
				$started_timestamp = ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] )
					? (int) $statuses[ self::ONBOARDING_STEP_STATUS_STARTED ]
					: 0;
				if ( $started_timestamp &&
					( $this->proxy->call_function( 'time' ) - $started_timestamp ) > 60 // 1 minute.
				) {
					$this->clean_onboarding_step_progress( $step_id, $location );

					// Record an event for the step being cleaned due to timeout.
					$this->record_event(
						self::EVENT_PREFIX . 'onboarding_step_progress_reset_due_to_timeout',
						$location,
						array(
							'step_id' => $step_id,
						)
					);

					return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
				}
			}

			return self::ONBOARDING_STEP_STATUS_STARTED;
		}

		// Finally, we default to not started.
		return self::ONBOARDING_STEP_STATUS_NOT_STARTED;
	}

	/**
	 * Check if an onboarding step has been marked as started.
	 *
	 * This means that, at some point, the step was marked/recorded as started in the DB.
	 * This doesn't mean that the current reported status is started. The step status might be different now.
	 *
	 * @see get_onboarding_step_status() for that.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step has been marked as started.
	 */
	private function was_onboarding_step_marked_started( string $step_id, string $location ): bool {
		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		return ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] );
	}

	/**
	 * Mark an onboarding step as started.
	 *
	 * @param string      $step_id   The ID of the onboarding step.
	 * @param string      $location  The location for which we are onboarding.
	 *                               This is an ISO 3166-1 alpha-2 country code.
	 * @param bool        $overwrite Whether to overwrite the step status if it is already started and update the timestamp.
	 * @param string|null $source    Optional. The source for the current onboarding flow.
	 *                               If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return bool Whether the onboarding step was marked as started.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function mark_onboarding_step_started( string $step_id, string $location, bool $overwrite = false, ?string $source = self::SESSION_ENTRY_DEFAULT ): bool {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		// Clear possible failed status for the step.
		$this->clear_onboarding_step_failed( $step_id, $location );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );
		if ( ! $overwrite && ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] ) ) {
			return true;
		}

		// Mark the step as started and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_STARTED ] = $this->proxy->call_function( 'time' );

		// Store the updated step data.
		$result = $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );

		if ( $result ) {
			$source = $this->validate_onboarding_source( $source );

			// Record an event for the step being started.
			$this->record_event(
				self::EVENT_PREFIX . 'onboarding_step_started',
				$location,
				array(
					'step_id' => $step_id,
					'source'  => $source,
				)
			);
		}

		return $result;
	}

	/**
	 * Check if the onboarding step has a completed status.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step is completed.
	 * @throws ApiException On invalid step ID.
	 */
	private function is_onboarding_step_completed( string $step_id, string $location ): bool {
		return self::ONBOARDING_STEP_STATUS_COMPLETED === $this->get_onboarding_step_status( $step_id, $location );
	}

	/**
	 * Check if an onboarding step has been marked as completed.
	 *
	 * This means that, at some point, the step was marked/recorded as completed in the DB.
	 * This doesn't mean that the current reported status is completed. The step status might be different now.
	 *
	 * @see get_onboarding_step_status() for that.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step has been marked as completed.
	 */
	private function was_onboarding_step_marked_completed( string $step_id, string $location ): bool {
		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		return ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] );
	}

	/**
	 * Mark an onboarding step as completed.
	 *
	 * @param string      $step_id   The ID of the onboarding step.
	 * @param string      $location  The location for which we are onboarding.
	 *                               This is an ISO 3166-1 alpha-2 country code.
	 * @param bool        $overwrite Whether to overwrite the step status if it is already completed and update the timestamp.
	 * @param string|null $source    Optional. The source for the current onboarding flow.
	 *                               If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return bool Whether the onboarding step was marked as completed.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function mark_onboarding_step_completed( string $step_id, string $location, bool $overwrite = false, ?string $source = self::SESSION_ENTRY_DEFAULT ): bool {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		// Clear possible failed status for the step.
		$this->clear_onboarding_step_failed( $step_id, $location );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );
		if ( ! $overwrite && ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] ) ) {
			return true;
		}

		// Mark the step as completed and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_COMPLETED ] = $this->proxy->call_function( 'time' );

		// Store the updated step data.
		$result = $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );

		if ( $result ) {
			$source = $this->validate_onboarding_source( $source );

			// Record an event for the step being completed.
			$this->record_event(
				self::EVENT_PREFIX . 'onboarding_step_completed',
				$location,
				array(
					'step_id' => $step_id,
					'source'  => $source,
				)
			);
		}

		return $result;
	}

	/**
	 * Cleans an onboarding step progress.
	 *
	 * @param string $step_id   The ID of the onboarding step.
	 * @param string $location  The location for which we are onboarding.
	 *                          This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step was cleaned.
	 * @throws ApiArgumentException If the given onboarding step ID is invalid.
	 */
	public function clean_onboarding_step_progress( string $step_id, string $location ): bool {
		// We need to do reduced acceptance checks here because this is a cleanup action.
		// First, check general if the onboarding action is acceptable.
		$this->check_if_onboarding_action_is_acceptable();
		// Second, check if the step ID is valid.
		if ( ! $this->is_valid_onboarding_step_id( $step_id ) ) {
			throw new ApiArgumentException(
				'woocommerce_woopayments_onboarding_invalid_step_id',
				esc_html__( 'Invalid onboarding step ID.', 'woocommerce' ),
				(int) WP_Http::BAD_REQUEST
			);
		}

		// Clear possible failed or blocked status for the step.
		$this->clear_onboarding_step_failed( $step_id, $location );
		$this->clear_onboarding_step_blocked( $step_id, $location );

		// Reset the stored step statuses.
		$result = $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', array() );

		if ( $result ) {
			// Record an event for the step being cleaned.
			$this->record_event(
				self::EVENT_PREFIX . 'onboarding_step_progress_reset',
				$location,
				array(
					'step_id' => $step_id,
				)
			);
		}

		return $result;
	}

	/**
	 * Check if an onboarding step has a failed status.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step is failed.
	 */
	private function is_onboarding_step_failed( string $step_id, string $location ): bool {
		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		return ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_FAILED ] );
	}

	/**
	 * Mark an onboarding step as failed.
	 *
	 * This is for internal use only as a failed step status should not be the result of a user action.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 * @param array  $error    Optional. An error to be stored for the step to provide context to API consumers.
	 *                         The error should be an associative array with the following keys:
	 *                         - 'code': A string representing the error code.
	 *                         - 'message': A string representing the error message.
	 *                         - 'context': Optional. An array of additional data related to the error.
	 *
	 * @return bool Whether the onboarding step was marked as failed.
	 */
	private function mark_onboarding_step_failed( string $step_id, string $location, array $error = array() ): bool {
		// There is no need to do onboarding checks because setting a step as failed should be possible at any time.

		// Record the error for the step, even if it is empty.
		// This will ensure we only store the most recent error.
		$this->save_nox_profile_onboarding_step_data_entry( $step_id, $location, 'error', $this->sanitize_onboarding_step_error( $error ) );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		// Mark the step as failed and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_FAILED ] = $this->proxy->call_function( 'time' );

		// Make sure we clear the blocked status if it was set since blocked and failed should be mutually exclusive.
		unset( $statuses[ self::ONBOARDING_STEP_STATUS_BLOCKED ] );

		// Store the updated step data.
		$result = $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );

		if ( $result ) {
			// Record an event for the step being failed.
			$this->record_event(
				self::EVENT_PREFIX . 'onboarding_step_failed',
				$location,
				array(
					'step_id'    => $step_id,
					'error_code' => ! empty( $error['code'] ) ? $error['code'] : '',
				)
			);
		}

		return $result;
	}

	/**
	 * Clear the failed status of an onboarding step.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step was cleared from failed status.
	 *              Returns false if the step was not failed.
	 */
	private function clear_onboarding_step_failed( string $step_id, string $location ): bool {
		if ( ! $this->is_onboarding_step_failed( $step_id, $location ) ) {
			return false;
		}

		// Clear any error for the step.
		$this->save_nox_profile_onboarding_step_data_entry( $step_id, $location, 'error', array() );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		// Clear the failed status.
		unset( $statuses[ self::ONBOARDING_STEP_STATUS_FAILED ] );

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );
	}

	/**
	 * Check if an onboarding step has a blocked status.
	 *
	 * @param string $step_id The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step is blocked.
	 */
	private function is_onboarding_step_blocked( string $step_id, string $location ): bool {
		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		return ! empty( $statuses[ self::ONBOARDING_STEP_STATUS_BLOCKED ] );
	}

	/**
	 * Mark an onboarding step as blocked.
	 *
	 * This is for internal use only as a blocked step status should not be the result of a user action.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 * @param array  $errors   Optional. A list of errors to be stored for the step to provide context to API consumers.
	 *
	 * @return bool Whether the onboarding step was marked as blocked.
	 */
	private function mark_onboarding_step_blocked( string $step_id, string $location, array $errors = array() ): bool {
		// There is no need to do onboarding checks because setting a step as blocked should be possible at any time.

		// Record the error for the step, even if it is empty.
		// This will ensure we only store the most recent error.
		$this->save_nox_profile_onboarding_step_data_entry( $step_id, $location, 'error', $this->sanitize_onboarding_step_error( $errors ) );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		// Mark the step as blocked and record the timestamp.
		$statuses[ self::ONBOARDING_STEP_STATUS_BLOCKED ] = $this->proxy->call_function( 'time' );

		// Make sure we clear the failed status if it was set since blocked and failed should be mutually exclusive.
		unset( $statuses[ self::ONBOARDING_STEP_STATUS_FAILED ] );

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );
	}

	/**
	 * Clear the blocked status of an onboarding step.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool Whether the onboarding step was cleared from blocked status.
	 *              Returns false if the step was not blocked.
	 */
	private function clear_onboarding_step_blocked( string $step_id, string $location ): bool {
		if ( ! $this->is_onboarding_step_blocked( $step_id, $location ) ) {
			return false;
		}

		// Clear any error for the step.
		$this->save_nox_profile_onboarding_step_data_entry( $step_id, $location, 'error', array() );

		$statuses = (array) $this->get_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses' );

		// Clear the blocked status.
		unset( $statuses[ self::ONBOARDING_STEP_STATUS_BLOCKED ] );

		// Store the updated step data.
		return $this->save_nox_profile_onboarding_step_entry( $step_id, $location, 'statuses', $statuses );
	}

	/**
	 * Get the current stored error for an onboarding step.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The error for the onboarding step.
	 */
	private function get_onboarding_step_error( string $step_id, string $location ): array {
		return (array) $this->get_nox_profile_onboarding_step_data_entry( $step_id, $location, 'error', array() );
	}

	/**
	 * Sanitize an error for an onboarding step.
	 *
	 * @param array $error The error to sanitize.
	 *
	 * @return array The sanitized error.
	 */
	private function sanitize_onboarding_step_error( array $error ): array {
		$sanitized_error = array(
			'code'    => isset( $error['code'] ) ? sanitize_text_field( $error['code'] ) : '',
			'message' => isset( $error['message'] ) ? sanitize_text_field( $error['message'] ) : '',
			'context' => array(),
		);

		if ( isset( $error['context'] ) && ( is_array( $error['context'] ) || is_object( $error['context'] ) ) ) {
			// Make sure we are dealing with an array.
			$sanitized_error['context'] = json_decode( wp_json_encode( $error['context'] ), true );
			if ( ! is_array( $sanitized_error['context'] ) ) {
				$sanitized_error['context'] = array();
			}

			// Sanitize the context data.
			// It can only contain strings or arrays of strings.
			// Scalar values will be converted to strings. Other types will be ignored.
			foreach ( $sanitized_error['context'] as $key => $value ) {
				if ( is_string( $value ) ) {
					$sanitized_error['context'][ $key ] = sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					// Arrays can only contain strings.
					$sanitized_error['context'][ $key ] = array_map(
						function ( $item ) {
							if ( is_string( $item ) ) {
								return sanitize_text_field( $item );
							} elseif ( is_scalar( $item ) ) {
								return sanitize_text_field( (string) $item );
							} else {
								return '';
							}
						},
						$value
					);
					// Remove any empty values from the array.
					$sanitized_error['context'][ $key ] = array_filter(
						$sanitized_error['context'][ $key ],
						function ( $item ) {
							return '' !== $item;
						}
					);
				} else {
					unset( $sanitized_error['context'][ $key ] );
				}
			}
		}

		return $sanitized_error;
	}

	/**
	 * Save the data for an onboarding step.
	 *
	 * @param string $step_id      The ID of the onboarding step.
	 * @param string $location     The location for which we are onboarding.
	 *                             This is an ISO 3166-1 alpha-2 country code.
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
				(int) WP_Http::BAD_REQUEST
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The check result.
	 * @throws ApiArgumentException If the given onboarding step ID or step data is invalid.
	 * @throws ApiException If the onboarding action can not be performed due to the current state of the site.
	 */
	public function onboarding_step_check( string $step_id, string $location ): array {
		$this->check_if_onboarding_step_action_is_acceptable( $step_id, $location );

		return array(
			'status' => $this->get_onboarding_step_status( $step_id, $location ),
			'error'  => $this->get_onboarding_step_error( $step_id, $location ),
		);
	}

	/**
	 * Get the recommended payment methods details for onboarding.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The recommended payment methods details.
	 */
	public function get_onboarding_recommended_payment_methods( string $location ): array {
		return $this->provider->get_recommended_payment_methods( $this->get_payment_gateway(), $location );
	}

	/**
	 * Initialize the test account for onboarding.
	 *
	 * @param string      $location The location for which we are onboarding.
	 *                              This is an ISO 3166-1 alpha-2 country code.
	 * @param string|null $source   Optional. The source for the current onboarding flow.
	 *                              If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The result of the test account initialization.
	 * @throws ApiException If the given onboarding step ID or step data is invalid.
	 *                                           If the onboarding action can not be performed due to the current state
	 *                                           of the site or there was an error initializing the test account.
	 */
	public function onboarding_test_account_init( string $location, ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

		// Nothing to do if we already have a connected test account.
		if ( $this->has_test_account() ) {
			throw new ApiException(
				'woocommerce_woopayments_test_account_already_exists',
				esc_html__( 'A test account is already set up.', 'woocommerce' ),
				(int) WP_Http::FORBIDDEN
			);
		}

		// Nothing to do if there is a connected account, but it is not a test account.
		if ( $this->has_account() ) {
			// Mark the onboarding step as completed, if it is not already.
			$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

			throw new ApiException(
				'woocommerce_woopayments_onboarding_action_error',
				esc_html__( 'An account is already set up. Reset the onboarding first.', 'woocommerce' ),
				(int) WP_Http::FORBIDDEN
			);
		}

		// Clear any previous failed status for the step.
		$this->clear_onboarding_step_failed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );

		$configured_payment_methods = $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'payment_methods', array() );

		// Ensure the payment gateways logic is initialized in case actions need to be taken on payment gateway changes.
		WC()->payment_gateways();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		$source = $this->validate_onboarding_source( $source );

		try {
			// Call the WooPayments API to initialize the test account.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/test_drive_account/init',
				array(
					'country'      => $location,
					'capabilities' => $configured_payment_methods,
					'source'       => $source,
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
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_TEST_ACCOUNT,
				$location,
				array(
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message(),
					'context' => $response->get_error_data(),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) || empty( $response['success'] ) ) {
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_TEST_ACCOUNT,
				$location,
				array(
					'code'    => 'malformed_response',
					'message' => esc_html__( 'Received an unexpected response from the platform.', 'woocommerce' ),
					'context' => array(
						'response' => $response,
					),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to initialize the test account.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// Record an event for the test account being initialized.
		$payment_methods_enabled  = array();
		$payment_methods_disabled = array();
		if ( ! empty( $configured_payment_methods ) && is_array( $configured_payment_methods ) ) {
			foreach ( $configured_payment_methods as $pm_id => $enabled ) {
				if ( ! is_string( $pm_id ) || ! is_bool( $enabled ) ) {
					continue; // Skip invalid entries.
				}

				if ( $enabled ) {
					$payment_methods_enabled[] = sanitize_key( $pm_id );
				} else {
					$payment_methods_disabled[] = sanitize_key( $pm_id );
				}
			}
		}
		$payment_methods_enabled  = array_unique( $payment_methods_enabled );
		$payment_methods_disabled = array_unique( $payment_methods_disabled );

		$event_props = array(
			'payment_methods_enabled'  => implode( ', ', $payment_methods_enabled ),
			'payment_methods_disabled' => implode( ', ', $payment_methods_disabled ),
			'source'                   => $source,
		);
		$this->record_event(
			self::EVENT_PREFIX . 'onboarding_test_account_init',
			$location,
			$event_props
		);

		return $response;
	}

	/**
	 * Get the onboarding KYC account session.
	 *
	 * @param string      $location        The location for which we are onboarding.
	 *                                     This is an ISO 3166-1 alpha-2 country code.
	 * @param array       $self_assessment Optional. The self-assessment data.
	 *                                     If not provided, the stored data will be used.
	 * @param string|null $source          Optional. The source for the current onboarding flow.
	 *                                     If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The KYC account session data.
	 * @throws ApiException If the extension is not active, step requirements are not met, or
	 *                      the KYC session data could not be retrieved.
	 */
	public function get_onboarding_kyc_session( string $location, array $self_assessment = array(), ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		if ( empty( $self_assessment ) ) {
			// Get the stored self-assessment data.
			$self_assessment = (array) $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'self_assessment' );
		}

		// Clear any previous failed status for the step.
		$this->clear_onboarding_step_failed( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		// Get the selected payment methods from the NOX profile.
		$selected_payment_methods = $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'payment_methods', array() );

		// Ensure the payment gateways logic is initialized in case actions need to be taken on payment gateway changes.
		WC()->payment_gateways();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		$source = $this->validate_onboarding_source( $source );

		try {
			// Call the WooPayments API to get the KYC session.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/kyc/session',
				array(
					'self_assessment' => $self_assessment,
					'capabilities'    => $selected_payment_methods,
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

		if ( is_wp_error( $response ) ) {
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				$location,
				array(
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message(),
					'context' => $response->get_error_data(),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) ) {
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				$location,
				array(
					'code'    => 'malformed_response',
					'message' => esc_html__( 'Received an unexpected response from the platform.', 'woocommerce' ),
					'context' => array(
						'response' => $response,
					),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to get the KYC session data.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// Add the user locale to the account session data to allow for localized KYC sessions.
		$response['locale'] = $this->proxy->call_function( 'get_user_locale' );

		// For sanity, make sure the test account step is completed if not already,
		// since we are doing live account KYC.
		if ( ! $this->is_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location ) ) {
			$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location, );
		}

		// Record an event for the KYC session being created.
		$event_props = array(
			'new_account_created' => $response['accountCreated'] ?? false,
			'account_mode'        => ( $response['isLive'] ?? false ) ? 'live' : 'test',
			'source'              => $source,
		);
		$this->record_event(
			self::EVENT_PREFIX . 'onboarding_kyc_session_created',
			$location,
			$event_props
		);

		return $response;
	}

	/**
	 * Finish the onboarding KYC account session.
	 *
	 * @param string      $location The location for which we are onboarding.
	 *                              This is an ISO 3166-1 alpha-2 country code.
	 * @param string|null $source   Optional. The source for the current onboarding flow.
	 *                              If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If the extension is not active, step requirements are not met, or
	 *                      the KYC session could not be finished.
	 */
	public function finish_onboarding_kyc_session( string $location, ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$this->check_if_onboarding_step_action_is_acceptable( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location );

		// Ensure the payment gateways logic is initialized in case actions need to be taken on payment gateway changes.
		WC()->payment_gateways();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		$source = $this->validate_onboarding_source( $source );

		try {
			// Call the WooPayments API to finalize the KYC session.
			$response = $this->proxy->call_static(
				Utils::class,
				'rest_endpoint_post_request',
				'/wc/v3/payments/onboarding/kyc/finalize',
				array(
					'source' => $source,
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
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				$location,
				array(
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message(),
					'context' => $response->get_error_data(),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html( $response->get_error_message() ),
				(int) WP_Http::FAILED_DEPENDENCY,
				map_deep( (array) $response->get_error_data(), 'esc_html' )
			);
		}

		if ( ! is_array( $response ) ) {
			// Mark the onboarding step as failed.
			$this->mark_onboarding_step_failed(
				self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				$location,
				array(
					'code'    => 'malformed_response',
					'message' => esc_html__( 'Received an unexpected response from the platform.', 'woocommerce' ),
					'context' => array(
						'response' => $response,
					),
				)
			);

			throw new ApiException(
				'woocommerce_woopayments_onboarding_client_api_error',
				esc_html__( 'Failed to finish the KYC session.', 'woocommerce' ),
				(int) WP_Http::FAILED_DEPENDENCY
			);
		}

		// For sanity, make sure the test account step is marked as completed, if not already,
		// since we are doing live account KYC.
		$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location, false, $source );

		// Record an event for the KYC session being finished.
		$event_props = array(
			'successful_kyc'    => filter_var( $response['success'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false,
			'account_mode'      => ( 'live' === ( $response['mode'] ?? false ) ) ? 'live' : 'test',
			'details_submitted' => filter_var( $response['details_submitted'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false,
			'promotion_id'      => $response['promotion_id'] ?? 'none',
			'source'            => $source,
		);
		$this->record_event(
			self::EVENT_PREFIX . 'onboarding_kyc_session_finished',
			$location,
			$event_props
		);

		// Mark the business verification step as completed.
		$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, false, $source );

		return $response;
	}

	/**
	 * Preload the onboarding process.
	 *
	 * This method is used to run the heavier logic required for onboarding ahead of time,
	 * so that we can be quicker to respond to the user when they start the onboarding process.
	 *
	 * @return array An array containing the success status and any errors encountered during the preload.
	 *               'success' => true if the preload was successful, false otherwise.
	 *               'errors'  => An array of error messages if any errors occurred, empty if no errors.
	 * @throws ApiException If the onboarding preload failed or the onboarding is locked.
	 */
	public function onboarding_preload(): array {
		// If the onboarding is locked, we shouldn't do anything.
		if ( $this->is_onboarding_locked() ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_locked',
				esc_html__( 'Another onboarding action is already in progress. Please wait for it to finish.', 'woocommerce' ),
				(int) WP_Http::CONFLICT
			);
		}

		$result = true;

		// Register the site to WPCOM if it is not already registered.
		// This sets up the site for connection. For new sites, this tends to take a while.
		// It is a prerequisite to generating the WPCOM/Jetpack authorization URL.
		if ( ! $this->wpcom_connection_manager->is_connected() ) {
			$result = $this->wpcom_connection_manager->try_registration();
			if ( is_wp_error( $result ) ) {
				throw new ApiException(
					'woocommerce_woopayments_onboarding_action_error',
					esc_html( $result->get_error_message() ),
					(int) WP_Http::INTERNAL_SERVER_ERROR,
					map_deep( (array) $result->get_error_data(), 'esc_html' )
				);
			}
		}

		return array(
			'success' => $result,
		);
	}

	/**
	 * Reset onboarding.
	 *
	 * @param string      $location The location for which we are onboarding.
	 *                              This is an ISO 3166-1 alpha-2 country code.
	 * @param string      $from     Optional. Where in the UI the request is coming from.
	 *                              If not provided, it will identify the origin as the WC Admin Payments settings.
	 * @param string|null $source   Optional. The source for the current onboarding flow.
	 *                              If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If we could not reset onboarding or there was an error.
	 */
	public function reset_onboarding( string $location, string $from = '', ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$this->check_if_onboarding_action_is_acceptable();

		// Ensure the payment gateways logic is initialized in case actions need to be taken on payment gateway changes.
		WC()->payment_gateways();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		$source = $this->validate_onboarding_source( $source );

		// Before resetting the onboarding, record its details for tracking purposes.
		$event_props = array(
			'has_account'  => $this->has_account(),
			'account_mode' => $this->has_account() ? ( $this->has_live_account() ? 'live' : 'test' ) : 'none',
			'test_account' => $this->has_test_account(),
			'source'       => $source,
		);

		if ( $this->has_account() ) {
			try {
				// Call the WooPayments API to reset onboarding.
				$response = $this->proxy->call_static(
					Utils::class,
					'rest_endpoint_post_request',
					'/wc/v3/payments/onboarding/reset',
					array(
						'from'   => ! empty( $from ) ? esc_attr( $from ) : self::FROM_PAYMENT_SETTINGS,
						'source' => $source,
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
		} else {
			// If there is no account to reset, we can just use a success response.
			$response = array(
				'success' => true,
			);
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		// Clean up any NOX-specific onboarding data, regardless of the API response.
		$this->proxy->call_function( 'delete_option', self::NOX_PROFILE_OPTION_KEY );

		// Make sure the onboarding mode is reset.
		if ( class_exists( 'WC_Payments_Onboarding_Service' ) && defined( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ) ) {
			$this->proxy->call_function( 'update_option', Constants::get_constant( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ), 'no' );
		}

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

		// Record an event for the onboarding reset.
		$this->record_event(
			self::EVENT_PREFIX . 'onboarding_reset',
			$location,
			$event_props
		);

		return $response;
	}

	/**
	 * Disable a test account during the switch-to-live onboarding flow.
	 *
	 * @param string      $location The location for which we are onboarding.
	 *                              This is an ISO 3166-1 alpha-2 country code.
	 * @param string      $from     Optional. Where in the UI the request is coming from.
	 *                              If not provided, it will identify the origin as the WC Admin Payments settings.
	 * @param string|null $source   Optional. The source for the current onboarding flow.
	 *                              If not provided, it will identify the source as the WC Admin Payments settings.
	 *
	 * @return array The response from the WooPayments API.
	 * @throws ApiException If we could not disable the test account or there was an error.
	 */
	public function disable_test_account( string $location, string $from = '', ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$this->check_if_onboarding_action_is_acceptable();

		// Ensure the payment gateways logic is initialized in case actions need to be taken on payment gateway changes.
		WC()->payment_gateways();

		$has_test_account    = $this->has_test_account();
		$has_sandbox_account = $this->has_sandbox_account();

		// Lock the onboarding to prevent concurrent actions.
		$this->set_onboarding_lock();

		$source = $this->validate_onboarding_source( $source );

		$response = array(
			'success' => true,
		);

		// First, check if we have a test account to disable.
		if ( $has_test_account ) {
			try {
				// Call the WooPayments API to disable the test account and prepare for the switch to live.
				$response = $this->proxy->call_static(
					Utils::class,
					'rest_endpoint_post_request',
					'/wc/v3/payments/onboarding/test_drive_account/disable',
					array(
						'from'   => ! empty( $from ) ? esc_attr( $from ) : self::FROM_PAYMENT_SETTINGS,
						'source' => $source,
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
		} elseif ( $has_sandbox_account ) {
			try {
				// Call the WooPayments API to reset onboarding.
				$response = $this->proxy->call_static(
					Utils::class,
					'rest_endpoint_post_request',
					'/wc/v3/payments/onboarding/reset',
					array(
						'from'   => ! empty( $from ) ? esc_attr( $from ) : self::FROM_PAYMENT_SETTINGS,
						'source' => $source,
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
		}

		// Unlock the onboarding after the API call finished or errored.
		$this->clear_onboarding_lock();

		// Make sure the onboarding mode is reset.
		if ( class_exists( 'WC_Payments_Onboarding_Service' ) && defined( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ) ) {
			$this->proxy->call_function( 'update_option', Constants::get_constant( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ), 'no' );
		}

		// Track the failure to disable the test account.
		if ( is_wp_error( $response ) || ! is_array( $response ) || empty( $response['success'] ) ) {
			$this->record_event(
				self::EVENT_PREFIX . 'onboarding_test_account_disable_error',
				$location,
				array(
					'source' => $source,
				)
			);
		}

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
		$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_PAYMENT_METHODS, $location );
		// For sanity, make sure the test account step is marked as completed and not blocked or failed.
		// After disabling a test account, the user should be prompted to set up a live account.
		$this->mark_onboarding_step_completed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );
		$this->clear_onboarding_step_blocked( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );
		$this->clear_onboarding_step_failed( self::ONBOARDING_STEP_TEST_ACCOUNT, $location );
		// Clear the NOX profile data for the business verification step sub-step data.
		// This way the user will be prompted to complete ALL the business verification sub-steps.
		$business_verification_sub_step_data = $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'sub_steps', array() );
		if ( ! empty( $business_verification_sub_step_data ) ) {
			$this->save_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'sub_steps', array() );
		}

		// Record an event for the test account being disabled.
		$this->record_event(
			self::EVENT_PREFIX . 'onboarding_test_account_disabled',
			$location,
			array(
				'account_type' => $has_test_account ? 'test_drive' : ( $has_sandbox_account ? 'sandbox' : 'unknown' ),
				'source'       => $source,
			)
		);

		return $response;
	}

	/**
	 * Send a Tracks event.
	 *
	 * By default, Woo adds `url`, `blog_lang`, `blog_id`, `store_id`, `products_count`, and `wc_version`
	 * properties to every event.
	 *
	 * @param string $name              The event name.
	 *                                  If it is not prefixed with self::EVENT_PREFIX, it will be prefixed with it.
	 * @param string $business_country  The business registration country code as set in the WooCommerce Payments settings.
	 *                                  This is an ISO 3166-1 alpha-2 country code.
	 * @param array  $properties        Optional. The event custom properties.
	 *                                  These properties will be merged with the default properties.
	 *                                  Default properties values take precedence over the provided ones.
	 *
	 * @return void
	 */
	public function record_event( string $name, string $business_country, array $properties = array() ) {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) ) {
			return;
		}

		// If the event name is empty, we don't record it.
		if ( empty( $name ) ) {
			return;
		}

		// If the event name is not prefixed with `settings_payments_`, we prefix it.
		if ( ! str_starts_with( $name, self::EVENT_PREFIX ) ) {
			$name = self::EVENT_PREFIX . $name;
		}

		// Add default properties to every event and overwrite custom properties with the same keys.
		$properties = array_merge(
			$properties,
			array(
				'business_country' => $business_country,
			),
		);

		wc_admin_record_tracks_event( $name, $properties );
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
		if ( Constants::is_defined( 'WCPAY_VERSION_NUMBER' ) &&
			version_compare( Constants::get_constant( 'WCPAY_VERSION_NUMBER' ), self::EXTENSION_MINIMUM_VERSION, '<' ) ) {
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
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
		if ( $this->is_onboarding_step_blocked( $step_id, $location ) ) {
			throw new ApiException(
				'woocommerce_woopayments_onboarding_step_blocked',
				esc_html__( 'There are environment or store setup issues which are blocking progress. Please resolve them to proceed.', 'woocommerce' ),
				(int) WP_Http::FORBIDDEN,
				array(
					'error' => map_deep( $this->get_onboarding_step_error( $step_id, $location ), 'esc_html' ),
				),
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
	 * @param string      $location  The location for which we are onboarding.
	 *                               This is an ISO 3166-1 alpha-2 country code.
	 * @param string      $rest_path The REST API path to use for constructing REST API URLs.
	 * @param string|null $source    Optional. The source for the onboarding flow.
	 *
	 * @return array[] The list of onboarding steps details.
	 * @throws Exception If there was an error generating the onboarding steps details.
	 */
	private function get_onboarding_steps( string $location, string $rest_path, ?string $source = self::SESSION_ENTRY_DEFAULT ): array {
		$steps = array();

		// Add the payment methods onboarding step details, but only if we have recommended payment methods.
		$recommended_pms = $this->get_onboarding_recommended_payment_methods( $location );
		if ( ! empty( $recommended_pms ) ) {
			$steps[] = $this->standardize_onboarding_step_details(
				array(
					'id'      => self::ONBOARDING_STEP_PAYMENT_METHODS,
					'context' => array(
						'recommended_pms' => $recommended_pms,
						'pms_state'       => $this->get_onboarding_payment_methods_state( $location, $recommended_pms ),
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
				$location,
				$rest_path
			);
		}

		// Add the WPCOM connection onboarding step details.
		$wpcom_step = $this->standardize_onboarding_step_details(
			array(
				'id'      => self::ONBOARDING_STEP_WPCOM_CONNECTION,
				'context' => array(
					'connection_state' => $this->get_wpcom_connection_state(),
				),
			),
			$location,
			$rest_path
		);

		// If the WPCOM connection is already set up, we don't need to add anything more.
		if ( self::ONBOARDING_STEP_STATUS_COMPLETED !== $wpcom_step['status'] ) {
			// Craft the return URL.
			switch ( $source ) {
				case self::SESSION_ENTRY_LYS:
					// If the source is LYS, we return the user to the Launch Your Store flow.
					$return_url = $this->proxy->call_function(
						'admin_url',
						'admin.php?page=wc-admin&path=/launch-your-store' . self::ONBOARDING_PATH_BASE . '&sidebar=hub&content=payments'
					);
					break;
				default:
					// By default, we return the user to the onboarding modal in the Settings > Payments page.
					$return_url = $this->proxy->call_static(
						Utils::class,
						'wc_payments_settings_url',
						self::ONBOARDING_PATH_BASE
					);
					break;
			}

			// Add standardized query arguments to the return URL.
			$return_url = add_query_arg(
				array(
					// URL query flag so we can properly identify when the user returns
					// either by accepting or rejecting the WPCOM connection.
					self::WPCOM_CONNECTION_RETURN_PARAM => '1',
					// Keep the source.
					'source'                            => $source,
					// Attach the `from` parameter to more easily identify where the return request is coming from.
					'from'                              => self::FROM_WPCOM,
				),
				$return_url
			);

			// Try to generate the authorization URL.
			$wpcom_connection = $this->get_wpcom_connection_authorization( $return_url );
			if ( ! $wpcom_connection['success'] ) {
				// In case of errors, make sure we work with a list of error messages.
				$wpcom_step['errors'] = array_values( (array) ( $wpcom_connection['errors'] ?? array() ) );
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

		// Test account onboarding step is unavailable in UAE and Singapore.
		if ( ! in_array( $location, array( 'AE', 'SG' ), true ) ) {
			$test_account_step = $this->standardize_onboarding_step_details(
				array(
					'id' => self::ONBOARDING_STEP_TEST_ACCOUNT,
				),
				$location,
				$rest_path
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
					'finish' => array(
						'type' => self::ACTION_TYPE_REST,
						'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/finish' ),
					),
				);
			}

			$test_account_step['actions']['reset'] = array(
				'type' => self::ACTION_TYPE_REST,
				'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_TEST_ACCOUNT . '/reset' ),
			);

			$steps[] = $test_account_step;
		}

		// Add the live account business verification onboarding step details.
		$business_verification_step_sub_steps = $this->get_nox_profile_onboarding_step_data_entry(
			self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
			$location,
			'sub_steps',
			array()
		);
		// Sanity check: If there is no account connected, the sub-steps details should be forced empty.
		// This way we allow for the Transact Platform account reset to take effect and
		// allow the user to restart the business verification process, including the self-assessment business step.
		if ( ! $this->has_account() ) {
			$business_verification_step_sub_steps = array();
		}
		$business_verification_step = $this->standardize_onboarding_step_details(
			array(
				'id'      => self::ONBOARDING_STEP_BUSINESS_VERIFICATION,
				'context' => array(
					'fields'              => array(),
					'sub_steps'           => $business_verification_step_sub_steps,
					'self_assessment'     => $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location, 'self_assessment', array() ),
					'has_test_account'    => $this->has_test_account(),
					'has_sandbox_account' => $this->has_sandbox_account(),
				),
			),
			$location,
			$rest_path
		);

		// Try to get the pre-KYC fields, but only if the required step is completed.
		// This is because WooPayments needs a working WPCOM connection to be able to fetch the fields.
		if ( $this->check_onboarding_step_requirements( self::ONBOARDING_STEP_BUSINESS_VERIFICATION, $location ) ) {
			try {
				$business_verification_step['context']['fields'] = $this->get_onboarding_kyc_fields( $location );
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
				'start'                => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/start' ),
				),
				'save'                 => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/save' ),
				),
				'kyc_session'          => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session' ),
				),
				'kyc_session_finish'   => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session/finish' ),
				),
				'kyc_fallback'         => array(
					'type' => self::ACTION_TYPE_REDIRECT,
					'href' => $this->get_onboarding_kyc_fallback_url(),
				),
				'finish'               => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/finish' ),
				),
				'test_account_disable' => array(
					'type' => self::ACTION_TYPE_REST,
					'href' => rest_url( trailingslashit( $rest_path ) . self::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/test_account/disable' ),
				),
			);
		}

		$steps[] = $business_verification_step;

		// Do a complete list standardization, for safety.
		return $this->standardize_onboarding_steps_details( $steps, $location, $rest_path );
	}

	/**
	 * Standardize (and sanity check) the onboarding step details.
	 *
	 * @param array  $step_details The onboarding step details to standardize.
	 * @param string $location     The location for which we are onboarding.
	 *                             This is an ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path    The REST API path to use for constructing REST API URLs.
	 *
	 * @return array The standardized onboarding step details.
	 * @throws Exception If the onboarding step details are missing required entries or if the step ID is invalid.
	 */
	private function standardize_onboarding_step_details( array $step_details, string $location, string $rest_path ): array {
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

		if ( empty( $step_details['status'] ) ) {
			$step_details['status'] = $this->get_onboarding_step_status( $step_details['id'], $location );
		}

		if ( empty( $step_details['errors'] ) ) {
			$step_details['errors'] = array();

			// For blocked or failed steps, we include any stored error.
			if ( in_array( $step_details['status'], array( self::ONBOARDING_STEP_STATUS_BLOCKED, self::ONBOARDING_STEP_STATUS_FAILED ), true ) ) {
				$stored_error = $this->get_onboarding_step_error( $step_details['id'], $location );
				if ( ! empty( $stored_error ) ) {
					$step_details['errors'] = array( $stored_error );
				}
			}
		}

		// Ensure that any step has the general actions.
		if ( empty( $step_details['actions'] ) ) {
			$step_details['actions'] = array();
		}
		// Any step can be checked for its status.
		if ( empty( $step_details['actions']['check'] ) ) {
			$step_details['actions']['check'] = array(
				'type' => self::ACTION_TYPE_REST,
				'href' => rest_url( trailingslashit( $rest_path ) . $step_details['id'] . '/check' ),
			);
		}
		// Any step can be cleaned of its progress.
		if ( empty( $step_details['actions']['clean'] ) ) {
			$step_details['actions']['clean'] = array(
				'type' => self::ACTION_TYPE_REST,
				'href' => rest_url( trailingslashit( $rest_path ) . $step_details['id'] . '/clean' ),
			);
		}

		return array(
			'id'             => $step_details['id'],
			'path'           => $step_details['path'] ?? trailingslashit( self::ONBOARDING_PATH_BASE ) . $step_details['id'],
			'required_steps' => $step_details['required_steps'] ?? $this->get_onboarding_step_required_steps( $step_details['id'] ),
			'status'         => $step_details['status'],
			'errors'         => $step_details['errors'],
			'actions'        => $step_details['actions'],
			'context'        => $step_details['context'] ?? array(),
		);
	}

	/**
	 * Standardize (and sanity check) the onboarding steps list.
	 *
	 * @param array  $steps The onboarding steps list to standardize.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 * @param string $rest_path The REST API path to use for constructing REST API URLs.
	 *
	 * @return array The standardized onboarding steps list.
	 * @throws Exception If some onboarding steps are missing required entries or if invalid step IDs are present.
	 */
	private function standardize_onboarding_steps_details( array $steps, string $location, string $rest_path ): array {
		$standardized_steps = array();
		foreach ( $steps as $step ) {
			$standardized_steps[] = $this->standardize_onboarding_step_details( $step, $location, $rest_path );
		}

		return $standardized_steps;
	}

	/**
	 * Get the entire stored NOX profile data.
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
	 * Save the NOX profile data.
	 *
	 * @param array $data The data to save in the profile.
	 *
	 * @return bool Whether the data was saved.
	 */
	private function save_nox_profile( array $data ): bool {
		return $this->proxy->call_function( 'update_option', self::NOX_PROFILE_OPTION_KEY, $data, false );
	}

	/**
	 * Get the onboarding data from the NOX profile.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding stored data from the NOX profile.
	 *               If the step data is not found, an empty array is returned.
	 */
	private function get_nox_profile_onboarding( string $location ): array {
		$nox_profile = $this->get_nox_profile();

		if ( empty( $nox_profile['onboarding'] ) ) {
			$nox_profile['onboarding'] = array();
		}
		if ( empty( $nox_profile['onboarding'][ $location ] ) ) {
			$nox_profile['onboarding'][ $location ] = array();
		}

		return $nox_profile['onboarding'][ $location ];
	}

	/**
	 * Save the onboarding data in the NOX profile.
	 *
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 * @param array  $data     The onboarding step data to save in the profile.
	 *
	 * @return bool Whether the onboarding data was saved.
	 */
	private function save_nox_profile_onboarding( string $location, array $data ): bool {
		$nox_profile = $this->get_nox_profile();

		if ( empty( $nox_profile['onboarding'] ) ) {
			$nox_profile['onboarding'] = array();
		}

		// Update the stored data.
		$nox_profile['onboarding'][ $location ] = $data;

		return $this->save_nox_profile( $nox_profile );
	}

	/**
	 * Get the onboarding step data from the NOX profile.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding step stored data from the NOX profile.
	 *               If the step data is not found, an empty array is returned.
	 */
	private function get_nox_profile_onboarding_step( string $step_id, string $location ): array {
		$nox_profile_onboarding = $this->get_nox_profile_onboarding( $location );

		if ( empty( $nox_profile_onboarding['steps'] ) ) {
			$nox_profile_onboarding['steps'] = array();
		}
		if ( empty( $nox_profile_onboarding['steps'][ $step_id ] ) ) {
			$nox_profile_onboarding['steps'][ $step_id ] = array();
		}

		return $nox_profile_onboarding['steps'][ $step_id ];
	}

	/**
	 * Save the onboarding step data in the NOX profile.
	 *
	 * @param string $step_id  The ID of the onboarding step.
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 * @param array  $data     The onboarding step data to save in the profile.
	 *
	 * @return bool Whether the onboarding step data was saved.
	 */
	private function save_nox_profile_onboarding_step( string $step_id, string $location, array $data ): bool {
		$nox_profile_onboarding = $this->get_nox_profile_onboarding( $location );

		if ( empty( $nox_profile_onboarding['steps'] ) ) {
			$nox_profile_onboarding['steps'] = array();
		}

		// Update the stored step data.
		$nox_profile_onboarding['steps'][ $step_id ] = $data;

		return $this->save_nox_profile_onboarding( $location, $nox_profile_onboarding );
	}

	/**
	 * Get an entry from the NOX profile onboarding step details.
	 *
	 * @param string $step_id       The ID of the onboarding step.
	 * @param string $location      The location for which we are onboarding.
	 *                              This is an ISO 3166-1 alpha-2 country code.
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
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
	 *                              This is an ISO 3166-1 alpha-2 country code.
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
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
	 *                         This is an ISO 3166-1 alpha-2 country code.
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
	 * @param string     $location        The location for which we are onboarding.
	 *                                    This is an ISO 3166-1 alpha-2 country code.
	 * @param array|null $recommended_pms Optional. The recommended payment methods to use.
	 *
	 * @return array The onboarding payment methods state.
	 */
	private function get_onboarding_payment_methods_state( string $location, ?array $recommended_pms ): array {
		// First, get the recommended payment methods details from the provider.
		// We will use their enablement state as the default.
		// Note: The list is validated and standardized by the provider, so we don't need to do it here.
		if ( null === $recommended_pms ) {
			$recommended_pms = $this->get_onboarding_recommended_payment_methods( $location );
		}
		if ( empty( $recommended_pms ) ) {
			// If there are no recommended payment methods, return an empty array.
			return array();
		}

		// Grab the stored payment methods state
		// (a key-value array of payment method IDs and if they should be automatically enabled or not).
		$step_pms_data = (array) $this->get_nox_profile_onboarding_step_data_entry( self::ONBOARDING_STEP_PAYMENT_METHODS, $location, 'payment_methods' );

		$payment_methods_state = array();
		$apple_pay_enabled     = false;
		$google_pay_enabled    = false;

		foreach ( $recommended_pms as $recommended_pm ) {
			$pm_id = $recommended_pm['id'];

			/**
			 * We need to handle Apple Pay and Google Pay separately.
			 * They are not stored in the same way as the other payment methods.
			 */
			if ( 'apple_pay' === $pm_id ) {
				$apple_pay_enabled = $recommended_pm['enabled'];
				continue;
			}

			if ( 'google_pay' === $pm_id ) {
				$google_pay_enabled = $recommended_pm['enabled'];
				continue;
			}

			// Start with the recommended enabled state.
			$payment_methods_state[ $pm_id ] = $recommended_pm['enabled'];

			// Force enable if required.
			if ( $recommended_pm['required'] ) {
				$payment_methods_state[ $pm_id ] = true;
				continue;
			}

			// Check the stored state, if any.
			if ( isset( $step_pms_data[ $pm_id ] ) ) {
				$payment_methods_state[ $pm_id ] = wc_string_to_bool( $step_pms_data[ $pm_id ] );
			}
		}

		// Combine Apple Pay and Google Pay into a single `apple_google` entry.
		$apple_google_enabled = $apple_pay_enabled || $google_pay_enabled;

		// Optionally also respect stored state or forced requirements if needed here.
		$payment_methods_state['apple_google'] = $apple_google_enabled;

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
	 * Determine if WooPayments has a sandbox account set up.
	 *
	 * @return bool Whether WooPayments has a sandbox account set up.
	 */
	private function has_sandbox_account(): bool {
		if ( ! $this->has_account() ) {
			return false;
		}

		$account_service = $this->proxy->call_static( '\WC_Payments', 'get_account_service' );
		$account_status  = $account_service->get_account_status_data();

		return empty( $account_status['isLive'] ) && empty( $account_status['testDrive'] );
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
	 * @param string $location The location for which we are onboarding.
	 *                         This is an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The onboarding fields data.
	 * @throws Exception If the onboarding fields data could not be retrieved or there was an error.
	 */
	private function get_onboarding_kyc_fields( string $location ): array {
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

		$fields['location'] = $location;

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
		return $this->provider->get_onboarding_url(
			$this->get_payment_gateway(),
			Utils::wc_payments_settings_url( self::ONBOARDING_PATH_BASE, array( 'from' => self::FROM_KYC ) )
		);
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

	/**
	 * Check the onboarding source and ensure it is a valid value.
	 *
	 * @param string|null $source The source of the onboarding request.
	 *
	 * @return string The validated onboarding source.
	 */
	private function validate_onboarding_source( ?string $source ): string {
		if ( empty( $source ) ) {
			return self::SESSION_ENTRY_DEFAULT;
		}

		$valid_sources = array(
			self::SESSION_ENTRY_DEFAULT,
			self::SESSION_ENTRY_LYS,
		);

		return in_array( $source, $valid_sources, true ) ? $source : self::SESSION_ENTRY_DEFAULT;
	}
}

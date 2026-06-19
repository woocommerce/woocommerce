<?php
/**
 * WooPaymentsOverviewService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsSettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the native WooPayments Overview action shell projection.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native WooPayments admin runtime.
 */
class WooPaymentsOverviewService {

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 */
	final public function init( WooPaymentsAccountService $account_service ): void {
		$this->account_service = $account_service;
	}

	/**
	 * Get the Overview action shell projection.
	 *
	 * @return array<string,mixed>
	 */
	public function get_overview(): array {
		$this->prime_overview_option_caches();

		$account_data = $this->account_service->get_preserved_account_data_snapshot();

		return array(
			'account'                               => $this->get_account_projection( $account_data ),
			'account_status'                        => $this->get_account_status_projection( $account_data ),
			'show_update_details_task'              => $this->should_show_update_business_details_task( $account_data ),
			'overview_tasks_visibility'             => $this->get_overview_tasks_visibility(),
			'is_connection_success_modal_dismissed' => $this->is_truthy( get_option( 'wcpay_connection_success_modal_dismissed', false ) ),
			'disputes_awaiting_response_count'      => $this->get_cached_disputes_awaiting_response_count(),
			'account_details'                       => $this->get_account_details_projection( $account_data ),
			'account_fees'                          => $this->get_active_account_fees( $account_data ),
			'feature_flags'                         => $this->get_feature_flags_projection(),
			'account_loans'                         => $this->get_account_loans_projection( $account_data ),
			'wpcom_reconnect_url'                   => '',
			'urls'                                  => array(
				'overview_page' => Utils::wc_payments_settings_url( '/woopayments/overview' ),
				'settings'      => Utils::wc_payments_settings_url( '/woopayments/settings' ),
				'onboarding'    => Utils::wc_payments_settings_url( '/woopayments/onboarding' ),
				'setup'         => Utils::wc_payments_settings_url( '/woopayments/onboarding' ),
			),
		);
	}

	/**
	 * Get safe account fields needed by the Overview shell.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @return array<string,mixed>
	 */
	private function get_account_projection( array $account_data ): array {
		$account_id = $this->get_account_id( $account_data );
		$test_mode  = $this->account_service->is_test_mode_enabled();

		return array(
			'id'                   => $account_id,
			'mode'                 => $test_mode ? 'test' : 'live',
			'connected'            => '' !== $account_id,
			'working'              => '' !== $account_id && $this->is_truthy( $account_data['payments_enabled'] ?? false ),
			'can_process_payments' => $this->can_snapshot_process_payments( $account_data, $test_mode ),
			'details_submitted'    => $this->is_truthy( $account_data['details_submitted'] ?? false ),
			'test_mode'            => $test_mode,
			'test_mode_onboarding' => in_array( get_option( 'wcpay_onboarding_test_mode', 'no' ), array( 'yes', '1' ), true ),
			'dev_mode'             => $this->account_service->is_dev_mode_enabled(),
			'test_drive'           => '' !== $account_id && $this->is_truthy( $account_data['is_test_drive'] ?? false ),
			'sandbox'              => '' !== $account_id
				&& array_key_exists( 'is_live', $account_data )
				&& ! $this->is_truthy( $account_data['is_live'] )
				&& ! $this->is_truthy( $account_data['is_test_drive'] ?? false ),
			'live'                 => '' !== $account_id && $this->is_truthy( $account_data['is_live'] ?? false ),
		);
	}

	/**
	 * Get safe account status fields needed by Overview tasks.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return array<string,mixed>
	 */
	private function get_account_status_projection( array $account_data ): array {
		return array(
			'status'            => $this->get_account_status( $account_data ),
			'current_deadline'  => $this->get_current_deadline( $account_data ),
			'past_due'          => $this->has_past_due_requirements( $account_data ),
			'account_link'      => $this->get_scalar( $account_data['account_link'] ?? $account_data['accountLink'] ?? '' ),
			'requirements'      => array(
				'errors' => $this->get_requirement_errors( $account_data ),
			),
			'details_submitted' => $this->is_truthy( $account_data['details_submitted'] ?? false ),
			'payments_enabled'  => $this->is_truthy( $account_data['payments_enabled'] ?? false ),
			'deposits_enabled'  => $this->is_truthy( $account_data['deposits_enabled'] ?? $account_data['payouts_enabled'] ?? false ),
		);
	}

	/**
	 * Get a reference-compatible account status from cached account data.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return string
	 */
	private function get_account_status( array $account_data ): string {
		if ( array() === $account_data ) {
			return 'not_connected';
		}

		$explicit_status = $this->get_scalar( $account_data['status'] ?? '' );
		if ( '' !== $explicit_status ) {
			return $explicit_status;
		}

		if ( ! $this->is_truthy( $account_data['details_submitted'] ?? false ) ) {
			return 'restricted';
		}

		$requirements    = $this->get_requirements( $account_data );
		$disabled_reason = $this->get_scalar( $requirements['disabled_reason'] ?? '' );

		if ( 'requirements.pending_verification' === $disabled_reason ) {
			return 'pending_verification';
		}

		if ( 'requirements.fields_needed' === $disabled_reason ) {
			return 'restricted_partially';
		}

		if ( str_starts_with( $disabled_reason, 'rejected' ) ) {
			return $disabled_reason;
		}

		if ( '' !== $disabled_reason ) {
			return 'restricted';
		}

		if ( $this->has_past_due_requirements( $account_data ) ) {
			return 'restricted';
		}

		if ( $this->has_non_empty_array( $requirements['currently_due'] ?? array() ) && null !== $this->get_current_deadline( $account_data ) ) {
			return 'restricted_soon';
		}

		if ( $this->has_non_empty_array( $requirements['eventually_due'] ?? array() ) && null === $this->get_current_deadline( $account_data ) ) {
			return 'enabled';
		}

		return 'complete';
	}

	/**
	 * Get the current requirements deadline.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return int|null
	 */
	private function get_current_deadline( array $account_data ): ?int {
		$requirements = $this->get_requirements( $account_data );
		$deadline     = $requirements['current_deadline'] ?? $account_data['current_deadline'] ?? null;

		if ( ! is_numeric( $deadline ) ) {
			return null;
		}

		$deadline = (int) $deadline;

		return $deadline > 0 ? $deadline : null;
	}

	/**
	 * Tell whether cached account data has past-due requirements.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return bool
	 */
	private function has_past_due_requirements( array $account_data ): bool {
		$requirements = $this->get_requirements( $account_data );

		return $this->has_non_empty_array( $requirements['past_due'] ?? array() )
			|| $this->is_truthy( $account_data['has_overdue_requirements'] ?? false );
	}

	/**
	 * Tell whether Overview should show the update-business-details task.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return bool
	 */
	private function should_show_update_business_details_task( array $account_data ): bool {
		$status = $this->get_account_status( $account_data );

		return ( ! empty( $account_data ) && ! $this->is_truthy( $account_data['details_submitted'] ?? false ) )
			|| ( 'restricted_soon' === $status && null !== $this->get_current_deadline( $account_data ) )
			|| ( 'restricted' === $status && $this->has_past_due_requirements( $account_data ) );
	}

	/**
	 * Get task visibility options in the Overview response shape.
	 *
	 * @return array<string,mixed>
	 */
	private function get_overview_tasks_visibility(): array {
		return array(
			'dismissed_todo_tasks'       => $this->get_string_list_option( 'woocommerce_dismissed_todo_tasks' ),
			'deleted_todo_tasks'         => $this->get_string_list_option( 'woocommerce_deleted_todo_tasks' ),
			'remind_me_later_todo_tasks' => $this->get_remind_me_later_tasks_option(),
		);
	}

	/**
	 * Prime caches for the fixed option set used by the Overview projection.
	 *
	 * @return void
	 */
	private function prime_overview_option_caches(): void {
		// Prime caches to reduce future queries.
		wp_prime_option_caches(
			array(
				'wcpay_account_data',
				'woocommerce_woocommerce_payments_settings',
				'wcpay_onboarding_test_mode',
				'wcpay_dispute_status_counts_cache',
				'wcpay_test_dispute_status_counts_cache',
				'woocommerce_dismissed_todo_tasks',
				'woocommerce_deleted_todo_tasks',
				'woocommerce_remind_me_later_todo_tasks',
				'wcpay_connection_success_modal_dismissed',
				'_wcpay_feature_dispute_readiness_overview',
			)
		);
	}

	/**
	 * Get AccountDetails card data from the preserved account snapshot.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @return array<string,mixed>|null
	 */
	private function get_account_details_projection( array $account_data ): ?array {
		$account_details = $account_data['account_details'] ?? null;

		if (
			! is_array( $account_details )
			|| ! is_array( $account_details['account_status'] ?? null )
			|| ! is_array( $account_details['payout_status'] ?? null )
			|| ! array_key_exists( 'banner', $account_details )
		) {
			return null;
		}

		return array(
			'account_status' => $this->sanitize_scalar_array( $account_details['account_status'] ),
			'payout_status'  => $this->sanitize_scalar_array( $account_details['payout_status'] ),
			'banner'         => is_array( $account_details['banner'] ) ? $this->sanitize_scalar_array( $account_details['banner'] ) : null,
		);
	}

	/**
	 * Get active discounted account fee rows for enabled payment methods.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @return array<int,array{payment_method:string,fee:array<string,mixed>}>
	 */
	private function get_active_account_fees( array $account_data ): array {
		$fees        = is_array( $account_data['fees'] ?? null ) ? $account_data['fees'] : array();
		$enabled_ids = $this->get_enabled_payment_method_ids();
		$active_fees = array();

		foreach ( $enabled_ids as $payment_method_id ) {
			if ( ! in_array( $payment_method_id, WooPaymentsSettingsService::get_supported_payment_method_ids(), true ) || ! is_array( $fees[ $payment_method_id ] ?? null ) ) {
				continue;
			}

			$fee_structure = $fees[ $payment_method_id ];
			if ( ! $this->has_non_empty_array( $fee_structure['discount'] ?? array() ) ) {
				continue;
			}

			$active_fees[] = array(
				'payment_method' => $payment_method_id,
				'fee'            => $this->sanitize_scalar_array( $fee_structure ),
			);
		}

		return $active_fees;
	}

	/**
	 * Get enabled WooPayments method IDs from gateway settings.
	 *
	 * @return string[]
	 */
	private function get_enabled_payment_method_ids(): array {
		$settings    = get_option( 'woocommerce_woocommerce_payments_settings', array() );
		$enabled_ids = is_array( $settings ) && is_array( $settings['upe_enabled_payment_method_ids'] ?? null )
			? $settings['upe_enabled_payment_method_ids']
			: ( is_array( $settings ) && is_array( $settings['enabled_payment_method_ids'] ?? null ) ? $settings['enabled_payment_method_ids'] : array( 'card' ) );

		$sanitized_ids = array();
		foreach ( $enabled_ids as $enabled_id ) {
			$enabled_id = $this->get_scalar( $enabled_id );
			if ( '' !== $enabled_id ) {
				$sanitized_ids[] = $enabled_id;
			}
		}

		return array_values( array_unique( $sanitized_ids ) );
	}

	/**
	 * Get Overview feature flags.
	 *
	 * @return array<string,bool>
	 */
	private function get_feature_flags_projection(): array {
		return array(
			'dispute_readiness_overview' => $this->is_truthy( get_option( '_wcpay_feature_dispute_readiness_overview', '1' ) ),
		);
	}

	/**
	 * Get native Overview account-loan flags.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @return array<string,bool>
	 */
	private function get_account_loans_projection( array $account_data ): array {
		$account_loans = $account_data['capital'] ?? $account_data['account_loans'] ?? $account_data['accountLoans'] ?? array();

		return array(
			'has_active_loan' => is_array( $account_loans )
				&& (
					$this->is_truthy( $account_loans['has_active_loan'] ?? false )
					|| $this->is_truthy( $account_loans['hasActiveLoan'] ?? false )
				),
		);
	}

	/**
	 * Get the account ID from a preserved account snapshot.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @return string
	 */
	private function get_account_id( array $account_data ): string {
		return $this->get_scalar( $account_data['account_id'] ?? $account_data['id'] ?? '' );
	}

	/**
	 * Tell whether the preserved snapshot has enough fields to process payments.
	 *
	 * @param array<string,mixed> $account_data Preserved account data snapshot.
	 * @param bool                $test_mode    Whether WooPayments is currently in test mode.
	 * @return bool
	 */
	private function can_snapshot_process_payments( array $account_data, bool $test_mode ): bool {
		$key_name        = $test_mode ? 'test_publishable_key' : 'live_publishable_key';
		$publishable_key = $this->get_scalar( $account_data[ $key_name ] ?? '' );

		return '' !== $this->get_account_id( $account_data )
			&& '' !== $publishable_key
			&& $this->is_truthy( $account_data['payments_enabled'] ?? false )
			&& $this->is_truthy( $account_data['details_submitted'] ?? false );
	}

	/**
	 * Get the cached actionable dispute count without refreshing the platform-backed cache.
	 *
	 * @return int|null
	 */
	private function get_cached_disputes_awaiting_response_count(): ?int {
		$cache_key      = $this->account_service->is_test_mode_enabled()
			? 'wcpay_test_dispute_status_counts_cache'
			: 'wcpay_dispute_status_counts_cache';
		$cache_contents = get_option( $cache_key, false );
		$counts         = is_array( $cache_contents ) && is_array( $cache_contents['data'] ?? null )
			? $cache_contents['data']
			: null;

		if ( ! is_array( $counts ) ) {
			return null;
		}

		return max(
			0,
			(int) ( $counts['needs_response'] ?? 0 ) + (int) ( $counts['warning_needs_response'] ?? 0 )
		);
	}

	/**
	 * Get normalized requirement errors from cached account data.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return array<int,array<string,string>>
	 */
	private function get_requirement_errors( array $account_data ): array {
		$requirements = $this->get_requirements( $account_data );
		$errors       = is_array( $requirements['errors'] ?? null ) ? $requirements['errors'] : array();
		$normalized   = array();

		foreach ( $errors as $error ) {
			if ( ! is_array( $error ) ) {
				continue;
			}

			$normalized_error = array();
			foreach ( array( 'code', 'reason', 'requirement' ) as $key ) {
				$value = $this->get_scalar( $error[ $key ] ?? '' );
				if ( '' !== $value ) {
					$normalized_error[ $key ] = $value;
				}
			}

			if ( ! empty( $normalized_error ) ) {
				$normalized[] = $normalized_error;
			}
		}

		return $normalized;
	}

	/**
	 * Get account requirements from cached account data.
	 *
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return array<string,mixed>
	 */
	private function get_requirements( array $account_data ): array {
		return is_array( $account_data['requirements'] ?? null ) ? $account_data['requirements'] : array();
	}

	/**
	 * Get a string-list option.
	 *
	 * @param string $option_name Option name.
	 * @return array<int,string>
	 */
	private function get_string_list_option( string $option_name ): array {
		$value = get_option( $option_name, array() );
		if ( ! is_array( $value ) ) {
			return array();
		}

		$strings = array();
		foreach ( $value as $item ) {
			if ( is_scalar( $item ) ) {
				$strings[] = (string) $item;
			}
		}

		return $strings;
	}

	/**
	 * Get the remind-me-later task timestamp map.
	 *
	 * @return array<string,int>
	 */
	private function get_remind_me_later_tasks_option(): array {
		$value = get_option( 'woocommerce_remind_me_later_todo_tasks', array() );
		if ( ! is_array( $value ) ) {
			return array();
		}

		$tasks = array();
		foreach ( $value as $task_key => $timestamp ) {
			if ( ! is_numeric( $timestamp ) ) {
				continue;
			}

			$tasks[ (string) $task_key ] = (int) $timestamp;
		}

		return $tasks;
	}

	/**
	 * Tell whether a value is a non-empty array.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function has_non_empty_array( $value ): bool {
		return is_array( $value ) && ! empty( $value );
	}

	/**
	 * Get a scalar value as a string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function get_scalar( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Recursively keep scalar/null array values only.
	 *
	 * @param array<mixed> $value Raw array.
	 * @return array<mixed>
	 */
	private function sanitize_scalar_array( array $value ): array {
		$sanitized = array();

		foreach ( $value as $key => $item ) {
			if ( is_array( $item ) ) {
				$sanitized[ $key ] = $this->sanitize_scalar_array( $item );
				continue;
			}

			if ( is_scalar( $item ) || null === $item ) {
				$sanitized[ $key ] = $item;
			}
		}

		return $sanitized;
	}

	/**
	 * Normalize boolean-like values.
	 *
	 * @param mixed $value Raw boolean-like value.
	 * @return bool
	 */
	private function is_truthy( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}
}

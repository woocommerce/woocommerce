<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;

defined( 'ABSPATH' ) || exit;

/**
 * WooPayments provider controller class.
 *
 * Use this class for hooks and actions related to the WooPayments provider as it relates to the Payments settings page.
 *
 * @internal
 */
class WooPaymentsController {

	/**
	 * The payments settings page service.
	 *
	 * @var Payments
	 */
	private Payments $payments;

	/**
	 * The WooPayments-specific Payments settings page service.
	 *
	 * @var WooPaymentsService
	 */
	private WooPaymentsService $woopayments;

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_returns_from_wpcom' ) );
	}

	/**
	 * Initialize the class instance.
	 *
	 * @param Payments           $payments The general payments settings page service.
	 * @param WooPaymentsService $woopayments The WooPayments-specific Payments settings page service.
	 *
	 * @internal
	 */
	final public function init( Payments $payments, WooPaymentsService $woopayments ): void {
		$this->payments    = $payments;
		$this->woopayments = $woopayments;
	}

	/**
	 * Handle returns from WordPress.com after the user has accepted or declined the WPCOM connection.
	 *
	 * @internal
	 */
	public function handle_returns_from_wpcom(): void {
		// Handle the return from WPCOM after the user has accepted or declined the WordPress.com connection.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET[ WooPaymentsService::WPCOM_CONNECTION_RETURN_PARAM ] ) ) {
			// We are only interested in connection flows that are initiated from NOX session entry points.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['source'] ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$source = sanitize_text_field( wp_unslash( $_GET['source'] ) );
			if ( ! in_array( $source, array( WooPaymentsService::SESSION_ENTRY_DEFAULT, WooPaymentsService::SESSION_ENTRY_LYS ), true ) ) {
				return;
			}

			$location = $this->payments->get_country();

			// Determine the connection state by querying the WPCOM connection onboarding step status.
			$wpcom_connected = WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED === $this->woopayments->get_onboarding_step_status( WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION, $location );

			// Track the connection attempt result.
			$event_props = array(
				'step_id' => WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION,
				'source'  => $source,
			);
			$this->woopayments->record_event(
				$wpcom_connected ? 'wpcom_connection_success' : 'wpcom_connection_failure',
				$location,
				$event_props
			);

			// On successful connection, mark the onboarding step as completed, if not already.
			if ( $wpcom_connected ) {
				$this->woopayments->mark_onboarding_step_completed( WooPaymentsService::ONBOARDING_STEP_WPCOM_CONNECTION, $location );
			}
		}
	}
}

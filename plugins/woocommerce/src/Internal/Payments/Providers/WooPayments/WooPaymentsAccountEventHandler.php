<?php
/**
 * WooPaymentsAccountEventHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use InvalidArgumentException;
use RuntimeException;

/**
 * Handles WooPayments account lifecycle provider events.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAccountEventHandler {

	/**
	 * Account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Token service.
	 *
	 * @var WooPaymentsTokenService
	 */
	private WooPaymentsTokenService $token_service;

	/**
	 * Initialize the handler.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService $account_service Account service.
	 * @param WooPaymentsTokenService   $token_service   Token service.
	 */
	final public function init( WooPaymentsAccountService $account_service, WooPaymentsTokenService $token_service ): void {
		$this->account_service = $account_service;
		$this->token_service   = $token_service;
	}

	/**
	 * Tell whether the event is handled by this handler.
	 *
	 * @param string $event_type Event type.
	 * @return bool
	 */
	public function is_supported_event( string $event_type ): bool {
		return in_array( $event_type, array( 'account.updated', 'account.deleted' ), true );
	}

	/**
	 * Process an account lifecycle event.
	 *
	 * @param string              $event_type   Event type.
	 * @param array<string,mixed> $event_object Account event object.
	 * @return void
	 * @throws InvalidArgumentException When the event type or event object is unsupported.
	 */
	public function process( string $event_type, array $event_object ): void {
		$event_account_id = $this->get_event_account_id( $event_object );

		switch ( $event_type ) {
			case 'account.updated':
				$this->account_service->refresh_account_data_strict();
				$this->token_service->clear_all_cached_payment_methods();
				return;
			case 'account.deleted':
				if ( ! $this->should_process_deleted_account( $event_account_id ) ) {
					return;
				}

				$this->account_service->mark_account_deletion_pending( $event_account_id );
				$this->account_service->cleanup_after_account_reset();
				$this->account_service->refresh_account_data_strict();
				$this->token_service->clear_all_cached_payment_methods();
				$this->account_service->clear_pending_account_deletion();
				return;
		}

		throw new InvalidArgumentException( 'Unsupported WooPayments account event type.' );
	}

	/**
	 * Get the account ID from the webhook object.
	 *
	 * @param array<string,mixed> $event_object Account event object.
	 * @return string
	 * @throws InvalidArgumentException When the event object is missing the account ID.
	 */
	private function get_event_account_id( array $event_object ): string {
		$account_id = $event_object['id'] ?? '';
		if ( ! is_scalar( $account_id ) || '' === trim( (string) $account_id ) ) {
			throw new InvalidArgumentException( 'WooPayments account event is missing an account ID.' );
		}

		return (string) $account_id;
	}

	/**
	 * Tell whether an account.deleted event applies to the current preserved account.
	 *
	 * @param string $event_account_id Account ID from the event.
	 * @return bool
	 * @throws RuntimeException When local account identity is missing while the gateway appears enabled.
	 */
	private function should_process_deleted_account( string $event_account_id ): bool {
		$pending_account_id = $this->account_service->get_pending_account_deletion_id();
		$current_account_id = $this->account_service->get_preserved_account_id();

		if ( $event_account_id === $pending_account_id ) {
			return '' === $current_account_id || $event_account_id === $current_account_id;
		}

		if ( '' === $current_account_id ) {
			if ( 'yes' === $this->account_service->get_gateway_setting( 'enabled', 'no' ) ) {
				throw new RuntimeException( 'Cannot verify WooPayments account deletion against the current account.' );
			}

			return false;
		}

		return $event_account_id === $current_account_id;
	}
}

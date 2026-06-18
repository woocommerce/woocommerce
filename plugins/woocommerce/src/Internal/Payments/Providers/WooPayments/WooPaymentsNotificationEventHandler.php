<?php
/**
 * WooPaymentsNotificationEventHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use InvalidArgumentException;

/**
 * Handles WooPayments remote notification provider events.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsNotificationEventHandler {

	/**
	 * Remote note service.
	 *
	 * @var WooPaymentsRemoteNoteService
	 */
	private WooPaymentsRemoteNoteService $remote_note_service;

	/**
	 * Initialize the handler.
	 *
	 * @internal
	 *
	 * @param WooPaymentsRemoteNoteService $remote_note_service Remote note service.
	 */
	final public function init( WooPaymentsRemoteNoteService $remote_note_service ): void {
		$this->remote_note_service = $remote_note_service;
	}

	/**
	 * Tell whether the event is handled by this handler.
	 *
	 * @param string $event_type Event type.
	 * @return bool
	 */
	public function is_supported_event( string $event_type ): bool {
		return 'wcpay.notification' === $event_type;
	}

	/**
	 * Process a remote notification event.
	 *
	 * @param array<string,mixed> $event Event payload.
	 * @return void
	 * @throws InvalidArgumentException When the event is missing note data.
	 */
	public function process( array $event ): void {
		$note_data = $event['data'] ?? null;
		if ( ! is_array( $note_data ) ) {
			throw new InvalidArgumentException( 'WooPayments notification event is missing note data.' );
		}

		$this->remote_note_service->put_note( $note_data );
	}
}

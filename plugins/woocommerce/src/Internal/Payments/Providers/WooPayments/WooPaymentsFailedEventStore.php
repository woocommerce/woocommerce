<?php
/**
 * WooPaymentsFailedEventStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Stores failed WooPayments webhook event payloads in preserved transients.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFailedEventStore {

	/**
	 * WooPayments-compatible failed-event transient prefix.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'wcpay_failed_event_';

	/**
	 * Failed-event transient TTL in seconds.
	 *
	 * @var int
	 */
	const TRANSIENT_TTL_SECONDS = DAY_IN_SECONDS;

	/**
	 * Get the transient name for an event ID.
	 *
	 * @since 11.0.0
	 *
	 * @param string $event_id Event ID.
	 * @return string Transient name.
	 */
	public function get_transient_name( string $event_id ): string {
		return self::TRANSIENT_PREFIX . md5( $event_id );
	}

	/**
	 * Persist a failed event payload.
	 *
	 * @since 11.0.0
	 *
	 * @param string              $event_id Event ID.
	 * @param array<string,mixed> $event    Event payload.
	 */
	public function set_event( string $event_id, array $event ): void {
		set_transient( $this->get_transient_name( $event_id ), $event, self::TRANSIENT_TTL_SECONDS );
	}

	/**
	 * Read a failed event payload.
	 *
	 * @since 11.0.0
	 *
	 * @param string $event_id Event ID.
	 * @return array<string,mixed>|null Event payload, or null when absent.
	 */
	public function get_event( string $event_id ): ?array {
		$event = get_transient( $this->get_transient_name( $event_id ) );

		return is_array( $event ) ? $event : null;
	}

	/**
	 * Delete a failed event payload.
	 *
	 * @since 11.0.0
	 *
	 * @param string $event_id Event ID.
	 */
	public function delete_event( string $event_id ): void {
		delete_transient( $this->get_transient_name( $event_id ) );
	}
}

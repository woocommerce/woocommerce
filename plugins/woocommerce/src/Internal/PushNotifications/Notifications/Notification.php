<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for push notifications.
 *
 * Each notification type (e.g. new order, new review) extends this class
 * and implements `to_payload()` with its own title, message, icon, and meta.
 *
 * @since 10.7.0
 */
abstract class Notification {
	/**
	 * Map of notification type identifiers to their corresponding subclass.
	 *
	 * @var array<string, class-string<Notification>>
	 */
	const NOTIFICATION_CLASSES = array(
		'store_order'  => NewOrderNotification::class,
		'store_review' => NewReviewNotification::class,
		'store_stock'  => StockNotification::class,
	);

	/**
	 * The ID of the resource this notification is about (e.g. order ID, comment
	 * ID).
	 *
	 * @var int
	 */
	private int $resource_id;

	/**
	 * Unix timestamp of the moment the store event fired, when known.
	 *
	 * Set by {@see PendingNotificationStore::add()} at trigger time and read by
	 * {@see PendingNotificationStore::dispatch_all()}, which persists it on
	 * shutdown. Null on any instance rebuilt in a later request (loopback,
	 * safety net, retry); those read the persisted value instead.
	 *
	 * @var int|null
	 */
	private ?int $triggered_at = null;

	/**
	 * Creates a new Notification instance.
	 *
	 * @param int $resource_id The resource ID.
	 *
	 * @throws InvalidArgumentException If the resource ID is invalid.
	 *
	 * @since 10.7.0
	 */
	public function __construct( int $resource_id ) {
		if ( $resource_id <= 0 ) {
			throw new InvalidArgumentException( 'Notification resource_id must be positive.' );
		}

		$this->resource_id = $resource_id;
	}

	/**
	 * Returns the notification type identifier, this should match the subtype
	 * or type (if there isn't a subtype) values attributed to notes in
	 * WordPress.com.
	 *
	 * @return string
	 *
	 * @since 10.7.0
	 */
	abstract public function get_type(): string;

	/**
	 * Returns the WPCOM-ready payload for this notification.
	 *
	 * Returns null if the underlying resource no longer exists.
	 *
	 * @return array|null
	 *
	 * @since 10.7.0
	 */
	abstract public function to_payload(): ?array;

	/**
	 * Checks whether a meta key exists for this notification's resource.
	 *
	 * @param string $key The meta key.
	 * @return bool
	 *
	 * @since 10.7.0
	 */
	abstract public function has_meta( string $key ): bool;

	/**
	 * Writes a meta key with a timestamp to this notification's resource.
	 *
	 * @param string   $key       The meta key.
	 * @param int|null $timestamp Unix timestamp to record. Defaults to the current time.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	abstract public function write_meta( string $key, ?int $timestamp = null ): void;

	/**
	 * Reads a meta value from this notification's resource.
	 *
	 * Returns an empty string when the key is absent or the resource no longer
	 * exists.
	 *
	 * @param string $key The meta key.
	 * @return string
	 *
	 * @since 11.2.0
	 */
	abstract public function read_meta( string $key ): string;

	/**
	 * Deletes a meta key from this notification's resource.
	 *
	 * @param string $key The meta key.
	 * @return void
	 *
	 * @since 10.8.0
	 */
	abstract public function delete_meta( string $key ): void;

	/**
	 * Clears the in-progress bookkeeping meta from this notification's resource.
	 *
	 * Call this on every terminal path (success, no recipients, and each of the
	 * retry handler's give-up branches) so a notification that has finished
	 * leaves nothing behind. The sent marker is deliberately not cleared: it is
	 * the idempotency guard that stops {@see NotificationProcessor::process()}
	 * sending the same notification twice, and only
	 * {@see StockNotificationRecoveryHandler} clears it, to re-arm a stock
	 * notification after a restock.
	 *
	 * Leaving the claimed marker behind is not only a storage cost. A stock
	 * notification that exhausted its retries would keep it forever, and
	 * {@see NotificationProcessor::process()} returns early when it is present,
	 * so that product could never send that notification again.
	 *
	 * @return void
	 *
	 * @since 11.2.0
	 */
	public function reset_processing_meta(): void {
		$this->delete_meta( NotificationProcessor::CLAIMED_META_KEY );
		$this->delete_meta( NotificationProcessor::TRIGGERED_META_KEY );
	}

	/**
	 * Records the moment the store event fired.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return void
	 *
	 * @since 11.2.0
	 */
	public function set_triggered_at( int $timestamp ): void {
		$this->triggered_at = $timestamp;
	}

	/**
	 * Returns the trigger time captured in this request, or null when this
	 * instance was rebuilt in a later one.
	 *
	 * @return int|null
	 *
	 * @since 11.2.0
	 */
	public function get_triggered_at(): ?int {
		return $this->triggered_at;
	}

	/**
	 * Returns the notification data as an array.
	 *
	 * @return array{type: string, resource_id: int}
	 *
	 * @since 10.7.0
	 */
	public function to_array(): array {
		return array(
			'type'        => $this->get_type(),
			'resource_id' => $this->resource_id,
		);
	}

	/**
	 * Reconstructs a Notification subclass from a serialized array.
	 *
	 * `type` and `resource_id` are required. Any further keys are passed to the
	 * subclass's `hydrate()` method, which decides what it recognises; see
	 * {@see StockNotification::hydrate()}.
	 *
	 * @param array<string, mixed> $data The notification data.
	 * @return self
	 *
	 * @throws InvalidArgumentException If the type is unknown.
	 *
	 * @since 10.7.0
	 */
	public static function from_array( array $data ): self {
		$type        = $data['type'] ?? '';
		$resource_id = (int) ( $data['resource_id'] ?? 0 );

		$class = self::NOTIFICATION_CLASSES[ $type ] ?? null;

		if ( ! $class ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new InvalidArgumentException( sprintf( 'Unknown notification type: %s', $type ) );
		}

		$instance = new $class( $resource_id );

		if ( method_exists( $instance, 'hydrate' ) ) {
			$instance->hydrate( $data );
		}

		return $instance;
	}

	/**
	 * Returns the ISO 8601 timestamp of the moment this notification was
	 * triggered, for the payload's `timestamp` field.
	 *
	 * Reads the trigger time recorded on the resource when the store event
	 * fired (see {@see NotificationProcessor::TRIGGERED_META_KEY}). Previously
	 * the payload was stamped with the current time at send, so any delay
	 * between the event and the send (safety net, retries) was invisible to
	 * delivery-age monitoring. Falls back to the current time when no trigger
	 * time was recorded (e.g. notifications already in flight when this shipped).
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_triggered_timestamp(): string {
		return $this->format_triggered_timestamp( $this->read_meta( NotificationProcessor::TRIGGERED_META_KEY ) );
	}

	/**
	 * Formats a recorded trigger time for the payload's `timestamp` field.
	 *
	 * Subclasses call this directly from `to_payload()` when they already hold
	 * the loaded resource, which avoids {@see self::get_triggered_timestamp()}
	 * fetching it a second time.
	 *
	 * @param string $recorded_at The raw meta value, or an empty string when absent.
	 * @return string
	 *
	 * @since 11.2.0
	 */
	protected function format_triggered_timestamp( string $recorded_at ): string {
		$triggered_at = $this->triggered_at ?? (int) $recorded_at;

		if ( $triggered_at <= 0 ) {
			$triggered_at = time();
		}

		return gmdate( 'c', $triggered_at );
	}

	/**
	 * Returns a unique identifier for this notification, used for
	 * deduplication.
	 *
	 * @return string
	 *
	 * @since 10.7.0
	 */
	public function get_identifier(): string {
		return sprintf( '%s_%s_%s', get_current_blog_id(), $this->get_type(), $this->resource_id );
	}

	/**
	 * Gets the resource ID.
	 *
	 * @return int
	 *
	 * @since 10.7.0
	 */
	public function get_resource_id(): int {
		return $this->resource_id;
	}

	/**
	 * Canonical positional ActionScheduler arguments for the safety-net job.
	 *
	 * Single source of truth shared by the scheduler (and its dedupe guard) and
	 * the cancel path so the serialized args always match. Action Scheduler
	 * matches the stored args by exact equality, so any divergence between the
	 * schedule-side and cancel-side shapes silently breaks cancellation.
	 *
	 * The args are keyed on the notification's *identity* — the minimal data
	 * needed to uniquely identify and reconstruct the notification — mirroring
	 * {@see self::get_identifier()}. Volatile payload fields (e.g. a stock
	 * snapshot captured at trigger time) must not be included: they are not part
	 * of the identity and may differ between schedule and cancel.
	 *
	 * @return array<int, mixed>
	 *
	 * @since 10.9.0
	 */
	public function get_safety_net_args(): array {
		return array( $this->get_type(), $this->get_resource_id() );
	}

	/**
	 * Decide whether this notification should be delivered to a user given
	 * their stored preference value for {@see static::get_type()}.
	 *
	 * `$pref_value` is whatever the user has stored under this notification
	 * type's preference key, or `null` if they have nothing stored. The
	 * {@see NotificationPreferencesService} stores each preference as an
	 * object so future sub-fields (thresholds, sub-toggles) can be added
	 * without bumping the schema version — today's shape is
	 * `['enabled' => bool]`, future shapes might add e.g.
	 * `['enabled' => true, 'min_value' => 500]` for an order threshold.
	 *
	 * Default: read the universal `enabled` sub-field, defaulting to `true`
	 * when the value is missing or has no `enabled` key (so newly-added
	 * notification types are opt-in by default). Subclasses override to
	 * read richer sub-fields and to consult their own resource (e.g.
	 * compare an order total to the user's `min_value`).
	 *
	 * Subclasses must keep this side-effect-free — the {@see NotificationProcessor}
	 * may call it once per recipient user per notification.
	 *
	 * @param mixed $pref_value The user's stored preference value, or null.
	 * @return bool True if this notification should be sent to that user.
	 *
	 * @since 10.9.0
	 */
	public function should_send_to_user( $pref_value ): bool {
		if ( null === $pref_value ) {
			return true;
		}

		if ( is_array( $pref_value ) ) {
			return (bool) ( $pref_value['enabled'] ?? true );
		}

		// Defensive fallback for unexpected scalar values; the service
		// always normalises stored prefs to the array shape above.
		return (bool) $pref_value;
	}
}

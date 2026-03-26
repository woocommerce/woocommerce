<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

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
	);

	/**
	 * The ID of the resource this notification is about (e.g. order ID, comment
	 * ID).
	 *
	 * @var int
	 */
	private int $resource_id;

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
	 * @param string $key The meta key.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	abstract public function write_meta( string $key ): void;

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
	 * @param array{type: string, resource_id: int} $data The notification data.
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

		return new $class( $resource_id );
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
}

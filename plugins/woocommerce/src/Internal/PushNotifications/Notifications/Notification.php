<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

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
	 * The notification type.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * The ID of the resource this notification is about (e.g. order ID, comment
	 * ID).
	 *
	 * @var int
	 */
	private int $resource_id;

	/**
	 * The blog ID for the site this notification originates from.
	 *
	 * @var int
	 */
	private int $blog_id;

	/**
	 * Creates a new Notification instance.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @param int    $blog_id     The blog ID.
	 *
	 * @since 10.7.0
	 */
	public function __construct( string $type, int $resource_id, int $blog_id ) {
		$this->type        = $type;
		$this->resource_id = $resource_id;
		$this->blog_id     = $blog_id;
	}

	/**
	 * Returns the WPCOM-ready payload for this notification.
	 *
	 * @return array
	 *
	 * @since 10.7.0
	 */
	abstract public function to_payload(): array;

	/**
	 * Returns the notification data as an array.
	 *
	 * @return array{type: string, resource_id: int, blog_id: int}
	 *
	 * @since 10.7.0
	 */
	public function to_array(): array {
		return array(
			'type'        => $this->type,
			'resource_id' => $this->resource_id,
			'blog_id'     => $this->blog_id,
		);
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
		return $this->type . '_' . $this->resource_id;
	}

	/**
	 * Gets the notification type.
	 *
	 * @return string
	 *
	 * @since 10.7.0
	 */
	public function get_type(): string {
		return $this->type;
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
	 * Gets the blog ID.
	 *
	 * @return int
	 *
	 * @since 10.7.0
	 */
	public function get_blog_id(): int {
		return $this->blog_id;
	}
}

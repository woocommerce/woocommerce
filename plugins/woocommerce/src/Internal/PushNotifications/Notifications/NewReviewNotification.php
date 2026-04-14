<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use WP_Comment;

defined( 'ABSPATH' ) || exit;

/**
 * Notification for new product reviews.
 *
 * @since 10.7.0
 */
class NewReviewNotification extends Notification {
	/**
	 * The notification type identifier for new reviews.
	 */
	const TYPE = 'store_review';

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return self::TYPE;
	}

	/**
	 * Returns the WPCOM-ready payload for this notification.
	 *
	 * Returns null if the comment no longer exists.
	 *
	 * @return array|null
	 *
	 * @since 10.7.0
	 */
	public function to_payload(): ?array {
		$comment = WC()->call_function( 'get_comment', $this->get_resource_id() );

		if ( ! $comment || ! $comment instanceof WP_Comment ) {
			return null;
		}

		return array(
			'type'        => $this->get_type(),
			// This represents the time the notification was triggered, so we can monitor age of notification at delivery.
			'timestamp'   => gmdate( 'c' ),
			'resource_id' => $this->get_resource_id(),
			'title'       => array(
				'format' => 'You have a new review! ⭐️',
			),
			'message'     => array(
				/**
				 * This will be translated in WordPress.com, format:
				 * 1: reviewer name, 2: product name, 3: comment content
				 */
				'format' => '%1$s left a review on %2$s: %3$s',
				'args'   => array(
					wp_strip_all_tags( $comment->comment_author ),
					wp_strip_all_tags( get_the_title( (int) $comment->comment_post_ID ) ),
					wp_strip_all_tags( $comment->comment_content ),
				),
			),
			'icon'        => get_avatar_url( $comment->comment_author_email ),
			'meta'        => array(
				'comment_id' => $this->get_resource_id(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function has_meta( string $key ): bool {
		return WC()->call_function( 'metadata_exists', 'comment', $this->get_resource_id(), $key );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function write_meta( string $key ): void {
		WC()->call_function( 'update_comment_meta', $this->get_resource_id(), $key, (string) time() );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function delete_meta( string $key ): void {
		WC()->call_function( 'delete_comment_meta', $this->get_resource_id(), $key );
	}
}

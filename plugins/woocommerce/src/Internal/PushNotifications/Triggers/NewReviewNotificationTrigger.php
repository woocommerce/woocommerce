<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for new approved product reviews and feeds notifications into
 * the PendingNotificationStore.
 *
 * @since 10.7.0
 */
class NewReviewNotificationTrigger {
	/**
	 * Registers WordPress hooks for review events.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		add_action( 'comment_post', array( $this, 'on_comment_post' ), 10, 3 );
	}

	/**
	 * Handles the comment_post hook.
	 *
	 * Only creates a notification for non-spam reviews on products.
	 *
	 * @param int        $comment_id       The comment ID.
	 * @param int|string $comment_approved 1 if approved, 0 if not, 'spam' if spam.
	 * @param array      $commentdata      The comment data.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function on_comment_post( int $comment_id, $comment_approved, array $commentdata ): void {
		if (
			'spam' === $comment_approved
			|| 'review' !== ( $commentdata['comment_type'] ?? '' )
		) {
			return;
		}

		$commented_on = get_post_type( (int) ( $commentdata['comment_post_ID'] ?? 0 ) );

		if ( 'product' !== $commented_on ) {
			return;
		}

		wc_get_container()->get( PendingNotificationStore::class )->add(
			new NewReviewNotification( $comment_id )
		);
	}
}

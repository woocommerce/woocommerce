<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * Notification management service.
 */
class NotificationManagementService {

	/**
	 * Get resend verification email URL.
	 *
	 * @param Notification $notification The notification.
	 * @return string The resend verification email URL.
	 */
	public function get_resend_verification_email_url( Notification $notification ): string {
		$url = add_query_arg(
			array(
				'wc_bis_resend_notification' => $notification->get_id(),
			),
			$notification->get_product_permalink()
		);

		return wp_nonce_url(
			$url,
			'wc_bis_resend_verification_email_nonce'
		);
	}
}

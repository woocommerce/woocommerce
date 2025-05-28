<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsListTable;

/**
 * Notifications admin page for Customer Stock Notifications.
 */
class NotificationsPage {

	/**
	 * Render page.
	 */
	public static function output() {
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table  = new NotificationsListTable();
		$table->prepare_items();
		include __DIR__ . '/Views/html-admin-notifications.php';
	}
}

<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\ListTable;

/**
 * Notifications admin page for Customer Stock Notifications.
 */
class NotificationsPage {

	/**
	 * Render page.
	 */
	public function output() {
		$table = new ListTable();
		$table->process_actions();
		$table->output_admin_notice();
		$table->prepare_items();
		include __DIR__ . '/views/html-admin-notifications.php';
	}
}

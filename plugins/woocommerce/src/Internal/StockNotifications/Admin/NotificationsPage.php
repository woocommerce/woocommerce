<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\ListTable;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationCreatePage;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationEditPage;

/**
 * Notifications admin page for Customer Stock Notifications.
 */
class NotificationsPage {

	/**
	 * Page URL.
	 *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=wc-customer-stock-notifications';

	/**
	 * Notices option name.
	 */
	const NOTICES_OPTION_NAME = 'wc_customer_stock_notifications_action_notice';

	/**
	 * Render page.
	 */
	public function output() {
		$table = new ListTable();
		$table->process_actions();
		self::output_admin_notice();
		$table->prepare_items();
		include __DIR__ . '/views/html-admin-notifications.php';
	}

	/**
	 * Create notification.
	 */
	public function create() {
		$create_page = new NotificationCreatePage();
		$create_page->output();
		self::output_admin_notice();
	}

	/**
	 * Edit notification.
	 */
	public function edit() {
		$edit_page = new NotificationEditPage();
		$edit_page->output();
		self::output_admin_notice();
	}

	/**
	 * Add admin notices.
	 *
	 * @return void
	 */
	public static function output_admin_notice(): void {
		if ( ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		$notice_data = get_option( self::NOTICES_OPTION_NAME );

		if ( empty( $notice_data ) || empty( $notice_data['message'] ) ) {
			return;
		}

		$type = in_array( $notice_data['type'], array( 'error', 'warning', 'success', 'info' ), true )
			? $notice_data['type']
			: 'info';

		\wp_admin_notice(
			$notice_data['message'],
			array(
				'type'        => $type,
				'id'          => self::NOTICES_OPTION_NAME,
				'dismissible' => false,
			)
		);

		delete_option( self::NOTICES_OPTION_NAME );
	}
}

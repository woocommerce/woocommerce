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
	const ADMIN_NOTICE_OPTION_NAME = 'wc_customer_stock_notifications_admin_notice';

	/**
	 * Render page.
	 */
	public function output() {
		$table = wc_get_container()->get( ListTable::class );
		$table->process_actions();
		$this->output_admin_notice();
		$table->prepare_items();
		include __DIR__ . '/Templates/html-admin-notifications.php';
	}

	/**
	 * Create notification.
	 */
	public function create() {
		$create_page = new NotificationCreatePage();
		$create_page->output();
		$this->output_admin_notice();
	}

	/**
	 * Edit notification.
	 */
	public function edit() {
		$edit_page = new NotificationEditPage();
		$edit_page->output();
		$this->output_admin_notice();
	}

	/**
	 * Add a notice to the admin notices.
	 *
	 * @param string $message The notice message.
	 * @param string $type The notice type (optional).
	 * @return void
	 */
	public static function add_notice( $message, $type = 'info' ) {
		if ( empty( $message ) ) {
			return;
		}

		$notice_data = get_option( self::ADMIN_NOTICE_OPTION_NAME );
		if ( false !== $notice_data ) {
			return;
		}

		if ( ! in_array( $type, array( 'error', 'warning', 'success', 'info' ), true ) ) {
			$type = 'info';
		}

		$notice_data = array(
			'message' => $message,
			'type'    => $type,
		);

		update_option( self::ADMIN_NOTICE_OPTION_NAME, $notice_data );
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public function output_admin_notice(): void {
		if ( ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		$notice_data = get_option( self::ADMIN_NOTICE_OPTION_NAME );
		if ( false === $notice_data ) {
			return;
		}

		// Check if invalid data.
		if ( empty( $notice_data ) || ! is_array( $notice_data ) || empty( $notice_data['message'] ) ) {
			delete_option( self::ADMIN_NOTICE_OPTION_NAME );
			return;
		}

		$type = in_array( $notice_data['type'], array( 'error', 'warning', 'success', 'info' ), true )
			? $notice_data['type']
			: 'info';

		\wp_admin_notice(
			$notice_data['message'],
			array(
				'type'        => $type,
				'id'          => self::ADMIN_NOTICE_OPTION_NAME,
				'dismissible' => false,
			)
		);

		delete_option( self::ADMIN_NOTICE_OPTION_NAME );
	}
}

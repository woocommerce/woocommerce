<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsListTable;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;

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
	 * Constructor.
	 */
	public function __construct() {

		// Select action.
		$action = '';

		// Nonce is checked in NotificationsPage::delete and NotificationsPage::output just displays the page.
		if ( isset( $_GET['notification_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = wc_clean( wp_unslash( $_GET['notification_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		switch ( $action ) {
			case 'delete':
				$this->delete();
				break;
			default:
				$this->output();
				break;
		}
	}

	/**
	 * Render page.
	 */
	public static function output() {

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table  = new NotificationsListTable();
		$table->prepare_items();
		include __DIR__ . '/Views/html-admin-notifications.php';
	}

	/**
	 * Delete notification.
	 *
	 * @throws \Exception If notification ID is missing.
	 */
	public static function delete() {

		check_admin_referer( 'delete_customer_stock_notification' );

		$notification_id = isset( $_GET['notification'] ) ? absint( $_GET['notification'] ) : 0;

		try {
			if ( ! $notification_id ) {
				throw new \Exception( 'Notification not found.' );
			}

			$notification = new Notification( $notification_id ); // <- this can throw
			\WC_Data_Store::load( 'stock_notification' )->delete( $notification );

			// Add admin notice.
			$notice_message = __( 'Notification deleted.', 'woocommerce' );
			update_option( 'wc_customer_stock_notifications_action_notice', $notice_message );

			wp_safe_redirect( admin_url( self::PAGE_URL ) );
			exit;

		} catch ( \Exception $e ) {
			// Add admin notice.
			$notice_message = __( 'Notification not found.', 'woocommerce' );
			update_option( 'wc_customer_stock_notifications_action_notice', $notice_message );

			wp_safe_redirect( admin_url( self::PAGE_URL ) );
			exit;
		}
	}
}

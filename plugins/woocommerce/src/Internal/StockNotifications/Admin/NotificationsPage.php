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
	const PAGE_URL = 'admin.php?page=customer_stock_notifications';

	/**
	 * Render page.
	 */
	public static function output() {

		if ( isset( $_GET['notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$updated_notice_args = array(
				'id'                 => 'message',
				'type'               => 'success',
				'additional_classes' => array( 'updated' ),
				'dismissible'        => false,
			);
			switch ( $_GET['notice'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'deleted':
					wp_admin_notice( __( 'Notification deleted.', 'woocommerce' ), $updated_notice_args );
					break;
				case 'updated':
					wp_admin_notice( __( 'Notification updated.', 'woocommerce' ), $updated_notice_args );
					break;
				case 'not_found':
					$updated_notice_args['type'] = 'error';
					wp_admin_notice( __( 'Notification not found.', 'woocommerce' ), $updated_notice_args );
					break;
			}
		}

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
				throw new \Exception( 'Missing notification ID.' );
			}

			$notification = new Notification( $notification_id ); // <- this can throw
			\WC_Data_Store::load( 'stock_notification' )->delete( $notification );

			wp_safe_redirect( add_query_arg( 'notice', 'deleted', admin_url( self::PAGE_URL ) ) );
			exit;

		} catch ( \Exception $e ) {
			wp_safe_redirect( add_query_arg( 'notice', 'not_found', admin_url( self::PAGE_URL ) ) );
			exit;
		}
	}
}

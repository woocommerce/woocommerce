<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\ListTable;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;

/**
 * Notification create page for Customer Stock Notifications.
 */
class NotificationEditPage {

	/**
	 * Render page.
	 */
	public function output() {
		$table           = new ListTable();
		$notification_id = isset( $_GET['notification_id'] ) ? absint( wp_unslash( $_GET['notification_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $notification_id ) {
			$notification = Factory::get_notification( $notification_id );
		}

		if ( ! $notification instanceof Notification ) {
			$notice_message = __( 'Notification not found.', 'woocommerce' );
			NotificationsPage::add_notice( $notice_message, 'error' );
			wp_safe_redirect( admin_url( NotificationsPage::PAGE_URL ) );
			exit;
		}

		$this->process_edit_form( $notification );
		$table->process_delete_action();

		$signed_up_customers = $table->data_store->query(
			array(
				'product_id' => $notification->get_product_id(),
				'return'     => 'count',
			)
		);

		include __DIR__ . '/Templates/html-admin-notification-edit.php';
	}

	/**
	 * Update notification.
	 *
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function process_edit_form( Notification $notification ) {

		if ( empty( $_POST ) || empty( $_POST['wc_customer_stock_notification_action'] ) ) {
			return;
		}

		check_admin_referer( 'woocommerce-customer-stock-notification-edit', 'customer_stock_notification_edit_security' );

		$action = wc_clean( wp_unslash( $_POST['wc_customer_stock_notification_action'] ) );
		switch ( $action ) {
			case 'activate_notification':
				$notification->set_status( NotificationStatus::ACTIVE );
				$result = $notification->save();
				if ( is_wp_error( $result ) ) {
					$notice_message = $result->get_error_message();
					NotificationsPage::add_notice( $notice_message, 'error' );
				} else {
					$notice_message = __( 'Notification updated.', 'woocommerce' );
					NotificationsPage::add_notice( $notice_message, 'success' );
				}
				break;
			case 'cancel_notification':
				$notification->set_status( NotificationStatus::CANCELLED );
				$notification->set_date_cancelled( time() );
				$notification->set_date_notified( NotificationCancellationSource::ADMIN );
				$result = $notification->save();
				if ( is_wp_error( $result ) ) {
					$notice_message = $result->get_error_message();
					NotificationsPage::add_notice( $notice_message, 'error' );
				} else {
					$notice_message = __( 'Notification updated.', 'woocommerce' );
					NotificationsPage::add_notice( $notice_message, 'success' );
				}
				break;
			case 'send_notification':
				$product = $notification->get_product();

				if ( ! $product || ! $product->is_in_stock() ) {
					$notice_message = __( 'Failed to send notification. Please make sure that the listed product is available.', 'woocommerce' );
					NotificationsPage::add_notice( $notice_message, 'error' );
				} else {
					$email_manager = new EmailManager();
					$email_manager->send_stock_notification_email( $notification );
					$notification->set_status( NotificationStatus::SENT );
					$notification->set_date_notified( time() );
					$notification->save();
					// translators: %s user email.
					$notice_message = sprintf( __( 'Notification sent to "%s".', 'woocommerce' ), $notification->get_user_email() );
					NotificationsPage::add_notice( $notice_message, 'success' );
				}
				break;
			case 'send_verification_email':
				// translators: %s user email.
				$notice_message = sprintf( __( 'Verification email sent to "%s".', 'woocommerce' ), $notification->get_user_email() );
				NotificationsPage::add_notice( $notice_message, 'success' );
				break;
		}

		// Construct edit url.
		$edit_url = add_query_arg(
			array(
				'notification_action' => 'edit',
				'notification_id'     => $notification->get_id(),
			),
			NotificationsPage::PAGE_URL
		);

		wp_safe_redirect( $edit_url );
		exit;
	}
}

<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;

/**
 * Notification create page for Customer Stock Notifications.
 */
class NotificationCreatePage {

	/**
	 * Render page.
	 */
	public function output() {
		$this->process_create_form();
		include __DIR__ . '/Templates/html-admin-notification-create.php';
	}

	/**
	 * Create and save notification.
	 */
	public function process_create_form() {
		if ( empty( $_POST ) ) {
			return;
		}

		check_admin_referer( 'woocommerce-customer-stock-notification-create', 'customer_stock_notification_create_security' );

		if ( ! isset( $_POST['save'] ) ) {
			return;
		}

		if ( ! isset( $_POST['product_id'] ) || empty( $_POST['product_id'] ) ) {
			NotificationsPage::add_notice( __( 'Please select a product.', 'woocommerce' ), 'error' );
			return;
		}

		if ( empty( $_POST['user_id'] ) && empty( $_POST['user_email'] ) ) {
			NotificationsPage::add_notice( __( 'Please select a customer.', 'woocommerce' ), 'error' );
			return;
		}

		// Posted data.
		$posted_data               = array();
		$posted_data['product_id'] = absint( wp_unslash( $_POST['product_id'] ) );

		if ( isset( $_POST['user_id'] ) && ! empty( $_POST['user_id'] ) ) {

			$posted_data['user_id'] = absint( wp_unslash( $_POST['user_id'] ) );
			if ( 0 === $posted_data['user_id'] ) {
				NotificationsPage::add_notice( __( 'Please select a customer.', 'woocommerce' ), 'error' );
				return;
			}

			$user                      = get_user_by( 'id', $posted_data['user_id'] );
			$posted_data['user_email'] = is_a( $user, 'WP_User' ) ? $user->user_email : '';

		} elseif ( isset( $_POST['user_email'] ) && ! empty( $_POST['user_email'] ) ) {

			$posted_data['user_email'] = sanitize_text_field( wp_unslash( $_POST['user_email'] ) );
			if ( ! filter_var( $posted_data['user_email'], FILTER_VALIDATE_EMAIL ) ) {
				NotificationsPage::add_notice( __( 'Please enter a valid email address.', 'woocommerce' ), 'error' );
				return;
			}

			$user                   = get_user_by( 'email', $posted_data['user_email'] );
			$posted_data['user_id'] = is_a( $user, 'WP_User' ) ? $user->ID : 0;
		}

		// Check if a notification already exists for the same product and customer.
		$notification_ids = \WC_Data_Store::load( 'stock_notification' )->query( $posted_data );
		if ( count( $notification_ids ) > 0 ) {
			$notice_message = sprintf(
				// translators: %s: notification edit url.
				__(
					'A <a href="%s">notification</a> for the same product and customer already exists in your database.',
					'woocommerce'
				),
				admin_url( NotificationsPage::PAGE_URL . '&notification_action=edit&notification_id=' . $notification_ids[0] )
			);
			NotificationsPage::add_notice( $notice_message, 'error' );
			return;
		}

		// Save notification.
		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $posted_data['product_id'] );
		$notification->set_user_id( $posted_data['user_id'] );
		$notification->set_user_email( $posted_data['user_email'] );
		$result = $notification->save();

		if ( is_wp_error( $result ) ) {
			$notice_message = $result->get_error_message();
			NotificationsPage::add_notice( $notice_message, 'error' );
			return;
		} else {

			$notice_message = __( 'Notification created.', 'woocommerce' );
			NotificationsPage::add_notice( $notice_message, 'success' );

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
}

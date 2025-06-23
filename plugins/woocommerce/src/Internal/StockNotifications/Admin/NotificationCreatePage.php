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
		include __DIR__ . '/views/html-admin-notification-create.php';
		$this->process_create_form();
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

		// Posted data.
		$args       = $_POST;
		$query_args = array();

		// Escape attributes.
		if ( isset( $args['user_id'] ) && ! empty( $args['user_id'] ) ) {
			$query_args['user_id'] = absint( $args['user_id'] );
			$user                  = get_user_by( 'id', $query_args['user_id'] );
			if ( $user && is_a( $user, 'WP_User' ) ) {
				$query_args['user_email'] = $user->user_email;
			}
		} elseif ( isset( $args['user_email'] ) && ! empty( $args['user_email'] ) ) {
			$query_args['user_email'] = sanitize_text_field( $args['user_email'] );
			// Is there a user with this email?
			$user = get_user_by( 'email', $query_args['user_email'] );
			if ( $user && is_a( $user, 'WP_User' ) ) {
				$query_args['user_id'] = $user->ID;
			}
		}

		if ( isset( $args['product_id'] ) && ! empty( $args['product_id'] ) ) {
			$query_args['product_id'] = absint( $args['product_id'] );
		}

		// Check if a notification already exists for the same product and customer.
		if ( ! empty( $query_args['product_id'] ) && ( ! empty( $query_args['user_id'] ) || ! empty( $query_args['user_email'] ) ) ) {

			$notification_ids = \WC_Data_Store::load( 'stock_notification' )->query(
				array(
					'product_id' => $query_args['product_id'],
					'user_id'    => isset( $query_args['user_id'] ) ? $query_args['user_id'] : 0,
					'user_email' => isset( $query_args['user_email'] ) ? $query_args['user_email'] : '',
				)
			);

			if ( count( $notification_ids ) > 0 ) {
				$notice_message = sprintf(
					// translators: %s: notification edit url.
					__(
						'A <a href="%s">notification</a> for the same product and customer already exists in your database.',
						'woocommerce'
					),
					admin_url( NotificationsPage::PAGE_URL . '&notification_action=edit&notification_id=' . $notification_ids[0] )
				);
				update_option(
					NotificationsPage::NOTICES_OPTION_NAME,
					array(
						'message' => $notice_message,
						'type'    => 'error',
					)
				);
				return;
			}
		}

		// Save notification.
		$notification = new Notification();
		if ( isset( $query_args['user_id'] ) ) {
			$notification->set_user_id( $query_args['user_id'] );
		}
		if ( isset( $query_args['user_email'] ) ) {
			$notification->set_user_email( $query_args['user_email'] );
		}
		if ( isset( $query_args['product_id'] ) ) {
			$notification->set_product_id( $query_args['product_id'] );
		}

		$notification->set_status( NotificationStatus::ACTIVE );
		$result = $notification->save();

		if ( is_wp_error( $result ) ) {
			$notice_message = $result->get_error_message();
			update_option(
				NotificationsPage::NOTICES_OPTION_NAME,
				array(
					'message' => $notice_message,
					'type'    => 'error',
				)
			);
			return;
		} else {

			$notice_message = __( 'Notification created.', 'woocommerce' );
			update_option(
				NotificationsPage::NOTICES_OPTION_NAME,
				array(
					'message' => $notice_message,
					'type'    => 'success',
				)
			);

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

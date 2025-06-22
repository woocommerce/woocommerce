<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\ListTable;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

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
	 * Render page.
	 */
	public function output() {
		$table = new ListTable();
		$table->process_actions();
		$table->output_admin_notice();
		$table->prepare_items();
		include __DIR__ . '/views/html-admin-notifications.php';
	}

	/**
	 * Create notification.
	 */
	public function create() {
		include __DIR__ . '/views/html-admin-notification-create.php';
		$this->save_notification();
	}

	/**
	 * Save notification.
	 */
	public function save_notification() {
		if ( empty( $_POST ) ) {
			return;
		}

		check_admin_referer( 'woocommerce-customer-stock-notification-edit', 'customer_stock_notification_edit_security' );

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
		if ( ! empty( $args['product_id'] ) && ( ! empty( $args['user_id'] ) || ! empty( $args['user_email'] ) ) ) {

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
						admin_url( self::PAGE_URL . '&notification_action=edit&notification_id=' . $notification_ids[0]
					)
				);
				update_option( 
					ListTable::NOTICES_OPTION_NAME,
					array(
						'message' => $notice_message, 
						'type'    => 'error',
					) 
				);
				wp_safe_redirect( self::PAGE_URL );
				exit;
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
		if ( isset( $query_args['date_created'] ) ) {
			$notification->set_date_created( $query_args['date_created'] );
		}
		$notification->set_status( NotificationStatus::ACTIVE );
		$result = $notification->save();

		if ( is_wp_error( $result ) ) {
			$notice_message = $result->get_error_message();
			update_option( 
				ListTable::NOTICES_OPTION_NAME, 
				array( 
					'message' => $notice_message, 
					'type'    => 'error' 
				)
			);
			wp_safe_redirect( self::PAGE_URL );
			exit;
		} else {
			$notice_message = __( 'Notification created.', 'woocommerce' );
			update_option( 
				ListTable::NOTICES_OPTION_NAME, 
				array( 
					'message' => $notice_message, 
					'type'    => 'success' 
				)
			);

			// TODO: Redirect to the edit page.
			wp_safe_redirect( self::PAGE_URL );
			exit;
		}
	}
}

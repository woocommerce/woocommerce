<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EmailNormalizer;

/**
 * Privacy eraser for WooCommerce Customer Stock Notifications.
 *
 * This class handles the erasure of customer stock notification data for users
 * who request their personal data to be erased.
 */
class PrivacyEraser extends \WC_Abstract_Privacy {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'register_erasers_exporters' ) );
	}

	/**
	 * Register the eraser for stock notifications.
	 */
	public function register_erasers_exporters() {
		$this->add_eraser(
			'woocommerce-customer-stock-notifications',
			__( 'WooCommerce Customer Stock Notifications', 'woocommerce' ),
			array( $this, 'erase_notification_data' )
		);
	}

	/**
	 * Erase customer stock notification data for a given email address and the account it belongs to.
	 *
	 * This method anonymizes the user email and cancels Active and Pending notifications with the
	 * 'privacy' cancellation source. Sent and already Cancelled notifications keep their status.
	 *
	 * @param string $email_address The email address to erase data for.
	 *
	 * @return array Response containing the status of the operation and messages.
	 */
	public static function erase_notification_data( string $email_address ): array {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		// Look up the account with the letter case as entered: `wp_users.user_email` is never
		// normalized, so on a case-sensitive collation the lowercased form would miss it.
		$user          = get_user_by( 'email', $email_address );
		$email_address = EmailNormalizer::normalize( $email_address );

		$notifications = NotificationQuery::get_notifications(
			array(
				'user_email' => $email_address,
			)
		);

		// Sign-ups made before an account email change keep the old email, so also match by user ID.
		if ( $user instanceof \WP_User ) {
			$notifications = array_unique(
				array_merge(
					$notifications,
					NotificationQuery::get_notifications(
						array(
							'user_id' => $user->ID,
						)
					)
				)
			);
		}

		foreach ( $notifications as $notification_id ) {
			$notification = Factory::get_notification( $notification_id );
			if ( ! $notification instanceof Notification ) {
				continue;
			}

			/**
			 * Action: woocommerce_stock_notification_erased
			 *
			 * Fires before a stock notification is anonymized by the privacy eraser, while it still
			 * holds the original email and user ID. The save that follows fires
			 * `woocommerce_stock_notification_status_changed` with the anonymized row, which cannot
			 * identify the shopper any more.
			 *
			 * @since 11.3.0
			 *
			 * @param int          $notification_id The notification ID.
			 * @param string       $original_email  The email stored on the notification before anonymization.
			 * @param Notification $notification    The notification, not yet anonymized.
			 */
			do_action( 'woocommerce_stock_notification_erased', $notification->get_id(), $notification->get_user_email(), $notification );

			$anonymous_email = wp_privacy_anonymize_data( 'email', $email_address );
			$notification->set_user_email( $anonymous_email );
			$notification->set_user_id( 0 );
			if ( in_array( $notification->get_status(), array( NotificationStatus::ACTIVE, NotificationStatus::PENDING ), true ) ) {
				$notification->set_status( NotificationStatus::CANCELLED );
				$notification->set_cancellation_source( NotificationCancellationSource::PRIVACY );
				$notification->set_date_cancelled( time() );
			}
			$notification->update_meta_data( '_anonymized', 'yes' );
			$notification->update_meta_data( 'verification_action_key', '' );
			$notification->update_meta_data( 'unsubscribe_action_key', '' );
			$notification->save();
			$response['messages'][] = sprintf(
			/* translators: %d the numeric product ID */
				__( 'Removed back-in-stock notification for product id: %d', 'woocommerce' ),
				$notification->get_product_id()
			);
			$response['items_removed'] = true;
		}//end foreach

		return $response;
	}
}

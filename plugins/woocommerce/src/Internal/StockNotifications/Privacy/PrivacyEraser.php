<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

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
	 * Erase customer stock notification data for a given email address.
	 *
	 * This method anonymizes the user email and sets the status of the notifications to 'cancelled'.
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

		$notifications = NotificationQuery::get_notifications(
			array(
				'user_email' => $email_address,
			)
		);

		foreach ( $notifications as $notification_id ) {
			$notification    = Factory::get_notification( $notification_id );
			$anonymous_email = wp_privacy_anonymize_data( 'email', $email_address );
			$notification->set_user_email( $anonymous_email );
			$notification->set_user_id( 0 );
			$notification->set_status( NotificationStatus::CANCELLED );
			$notification->set_cancellation_source( NotificationCancellationSource::USER );
			$notification->set_date_cancelled( current_time( 'mysql' ) );
			$notification->update_meta_data( '_anonymized', 'yes' );
			$notification->update_meta_data( 'email_link_action_key', '' );
			$notification->save();
			$response['messages'][] = sprintf(
			/* translators: %d the numeric product ID */
				__( 'Removed back-in-stock notification for product id: %d', 'woocommerce' ),
				$notification->get_product_id()
			);
			$response['items_removed'] = true;
		}

		return $response;
	}
}

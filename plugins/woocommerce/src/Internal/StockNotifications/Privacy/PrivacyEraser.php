<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Privacy;

/**
 * Privacy eraser for WooCommerce Stock Notifications.
 *
 * This class handles the erasure of stock notification data for users
 * who request their personal data to be erased.
 *
 * @since x.x.x
 */
class PrivacyEraser extends \WC_Abstract_Privacy {

	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'register_erasers_exporters' ) );
	}

	public function register_erasers_exporters() {
		$this->add_eraser(
			'woocommerce-stock-notifications',
			__( 'WooCommerce Stock Notifications', 'woocommerce' ),
			array( $this, 'erase_notification_data' )
		);
	}


	public static function erase_notification_data( $email_address ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$notifications = \WC_Data_Store::load( 'stock_notification' )->query(
			array(
				'user_email' => $email_address,
			)
		);

		foreach( $notifications as $notification ) {
			$anonymous_email = wp_privacy_anonymize_data( 'email', $email_address );
			$notification->set_user_email( $anonymous_email );
			$notification->set_user_id( 0 );
			$notification->save();
			$response['messages'][] = sprintf(
				/* translators: %d the numeric product ID */
				__( 'Removed customer notification for product id: %d', 'woocommerce' ),
				$notification->get_product_id()
			);
			$response['items_removed'] = true;
		}

		return $response;
	}
}

<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Privacy;

class PrivacyEraser extends \WC_Abstract_Privacy {

	final public function init() {
		parent::init();
	}

	public static function erase_notification_data( $response, $customer, $email_address ) {
		$can_erase = apply_filters( 'woocommerce_can_erase_customer_stock_notifications_data', true );

		if ( ! $can_erase ) {
			return $response;
		}

		$notifications = \WC_Data_Store::load( 'stock_notification' )->query(
			[
				'user_email' => $email_address,
			]
		);

		foreach( $notifications as $notification ) {
			$notification->set_user_email('');
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

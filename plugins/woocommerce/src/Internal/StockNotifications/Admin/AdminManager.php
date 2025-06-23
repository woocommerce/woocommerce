<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\MenusController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\Jetpack\Constants;

/**
 * Admin controller for Customer Stock Notifications.
 */
class AdminManager {

	/**
	 * Initialize admin components.
	 *
	 * @internal
	 *
	 * @return void
	 */
	final public function __construct() {

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_resources' ), 11 );
		add_action( 'woocommerce_privacy_erase_personal_data_customer', array( $this, 'erase_notification_data', 10, 3 ) );

		$container = wc_get_container();
		$container->get( MenusController::class );
		$container->get( SettingsController::class );
	}

	/**
	 * Admin scripts.
	 *
	 * @return void
	 */
	public static function admin_resources() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version   = Constants::get_constant( 'WC_VERSION' );

		wp_register_script( 'wc-admin-customer-stock-notifications', WC()->plugin_url() . '/assets/js/admin/wc-customer-stock-notifications' . $suffix . '.js', array( 'jquery' ), $version, true );

		$params = array(
			'i18n_wc_delete_notification_warning'       => __( 'Delete this notification permanently?', 'woocommerce' ),
			'i18n_wc_bulk_delete_notifications_warning' => __( 'Delete the selected notifications permanently?', 'woocommerce' ),
		);

		/*
		 * Enqueue specific styles & scripts.
		 */
		if ( 'woocommerce_page_wc-customer-stock-notifications' !== $screen_id ) {
			return;
		}

		wp_enqueue_script( 'wc-admin-customer-stock-notifications' );
		wp_localize_script( 'wc-admin-customer-stock-notifications', 'wc_admin_customer_stock_notifications_params', $params );
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

			$response['messages'][]    = __( 'Removed customer notification', 'woocommerce' );
			$response['items_removed'] = true;
		}

		return $response;
	}
}

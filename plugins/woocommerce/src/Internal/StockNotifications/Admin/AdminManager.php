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
		if (
			! in_array(
				$screen_id,
				array( 'woocommerce_page_wc-customer-stock-notifications', 'woocommerce_page_wc-settings' ),
				true
			)
		) {
			return;
		}
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['section'] ) && 'customer_stock_notifications' !== $_GET['section'] ) {
			return;
		}

		wp_enqueue_script( 'wc-admin-customer-stock-notifications' );
		wp_localize_script( 'wc-admin-customer-stock-notifications', 'wc_admin_customer_stock_notifications_params', $params );
	}
}

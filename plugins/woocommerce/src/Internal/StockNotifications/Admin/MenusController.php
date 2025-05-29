<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;

/**
 * Menus controller for Customer Stock Notifications.
 */
class MenusController {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Add Stock Notifications menu item.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 10 );

		// Integrate WooCommerce breadcrumb bar.
		add_action( 'admin_menu', array( $this, 'wc_admin_connect_customer_stock_notifications_pages' ) );
		add_filter( 'woocommerce_navigation_pages_with_tabs', array( $this, 'wc_admin_navigation_pages_with_tabs' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'wc_admin_navigation_screen_ids' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Add Stock Notifications menu item.
	 *
	 * @return bool|void
	 */
	public function add_menu() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$dashboard_page = add_submenu_page(
			'woocommerce',
			__( 'Stock Notifications', 'woocommerce' ),
			__( 'Notifications', 'woocommerce' ),
			'manage_woocommerce',
			'customer_stock_notifications',
			array( $this, 'notifications_page' )
		);

		add_action( "load-$dashboard_page", array( $this, 'add_screen_options' ) );
	}

	/**
	 * Add screen options support.
	 *
	 * @return void
	 */
	public function add_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Notifications per page', 'woocommerce' ),
				'default' => 10,
				'option'  => 'stock_notifications_per_page',
			)
		);
	}

	/**
	 * Save screen options.
	 *
	 * @param int    $status The status of the screen option.
	 * @param string $option The option name.
	 * @param int    $value The value of the screen option.
	 *
	 * @return int
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'stock_notifications_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	/**
	 * Displays the Notifications list table.
	 */
	public function notifications_page() {
		wc_get_container()->get( NotificationsPage::class );

		// Select section.
		$section = '';

		// Nonce is checked in NotificationsPage::delete and NotificationsPage::output just displays the page.
		if ( isset( $_GET['section'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$section = wc_clean( wp_unslash( $_GET['section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		switch ( $section ) {
			case 'delete':
				NotificationsPage::delete();
				break;
			default:
				NotificationsPage::output();
				break;
		}
	}

	/**
	 * Connect pages with navigation bar.
	 *
	 * @return void
	 */
	public function wc_admin_connect_customer_stock_notifications_pages() {

		if ( function_exists( 'wc_admin_connect_page' ) ) {

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-customer_stock_notifications',
					'screen_id' => 'woocommerce_page_customer_stock_notifications-notifications',
					'title'     => __( 'Stock Notifications', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page' => 'customer_stock_notifications',
						),
						'admin.php'
					),
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-customer_stock_notifications-create',
					'parent'    => 'woocommerce-customer_stock_notifications',
					'screen_id' => 'woocommerce_page_customer_stock_notifications-notifications-create',
					'title'     => __( 'Add Notification', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page'         => 'customer_stock_notifications',
							'section'      => 'create',
							'notification' => 1,
						),
						'admin.php'
					),
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-customer_stock_notifications-edit',
					'parent'    => 'woocommerce-customer_stock_notifications',
					'screen_id' => 'woocommerce_page_customer_stock_notifications-notifications-edit',
					'title'     => __( 'Edit Notification', 'woocommerce' ),
					'path'      => add_query_arg(
						array(
							'page'         => 'customer_stock_notifications',
							'section'      => 'edit',
							'notification' => 1,
						),
						'admin.php'
					),
				)
			);

		}
	}

	/**
	 * Configure Customer Stock Notifications page sections.
	 *
	 * @param array $pages Array of pages with their tab identifiers.
	 * @return array
	 */
	public function wc_admin_navigation_pages_with_tabs( $pages ) {
		$pages['customer_stock_notifications'] = 'notifications';
		return $pages;
	}

	/**
	 * Add screen id to WooCommerce.
	 *
	 * @since 0.0.0
	 * @param array $screen_ids List of screen IDs.
	 * @return array
	 */
	public static function wc_admin_navigation_screen_ids( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_customer_stock_notifications';
		return $screen_ids;
	}
}

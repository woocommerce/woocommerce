<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

/**
 * Menus controller for Customer Stock Notifications.
 */
class MenusController {

	/**
	 * Notifications page.
	 *
	 * @var NotificationsPage
	 */
	private $notifications_page;

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param NotificationsPage $notifications_page Notifications page.
	 * @return void
	 */
	final public function init( NotificationsPage $notifications_page ): void {
		$this->notifications_page = $notifications_page;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_menu' ), 10 );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_ids' ) );
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
			'wc-customer-stock-notifications',
			array( $this, 'notifications_page' )
		);

		add_action( "load-$dashboard_page", array( $this, 'add_screen_options' ) );
	}

	/**
	 * Add screen options support.
	 *
	 * @return void
	 */
	public function add_screen_options(): void {
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
	public function set_screen_option( $status, $option, $value ): int {
		if ( 'stock_notifications_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	/**
	 * Displays the Notifications list table.
	 */
	public function notifications_page() {

		$action = isset( $_GET['notification_action'] ) ? sanitize_text_field( wp_unslash( $_GET['notification_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $action, array( 'create', 'edit' ), true ) ) {
			$action = '';
		}

		switch ( $action ) {
			case 'create':
				$this->notifications_page->create();
				break;
			case 'edit':
				$this->notifications_page->edit();
				break;
			default:
				$this->notifications_page->output();
				break;
		}
	}

	/**
	 * Add screen id to WooCommerce.
	 *
	 * @param array $screen_ids List of screen IDs.
	 * @return array
	 */
	public static function add_screen_ids( $screen_ids ): array {
		$screen_ids[] = 'woocommerce_page_wc-customer-stock-notifications';
		return $screen_ids;
	}
}

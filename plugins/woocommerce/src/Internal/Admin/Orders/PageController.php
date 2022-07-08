<?php
namespace Automattic\WooCommerce\Internal\Admin\Orders;

/**
 * Controls the different pages/screens associated to the "Orders" menu page.
 */
class PageController {

	/**
	 * Instance of the orders list table.
	 *
	 * @var Automattic\WooCommerce\Internal\Admin\Orders\ListTable
	 */
	private $orders_table;

	/**
	 * Current action.
	 *
	 * @var string
	 */
	private $current_action = '';

	/**
	 * Sets up the page controller, including registering the menu item.
	 *
	 * @return void
	 */
	public function setup(): void {
		// Register menu.
		if ( 'admin_menu' === current_action() ) {
			$this->register_menu();
		} else {
			add_action( 'admin_menu', 'register_menu', 9 );
		}

		$this->set_action();

		// Perform initialization for the current action.
		add_action(
			'load-woocommerce_page_wc-orders',
			function() {
				if ( method_exists( $this, 'setup_action_' . $this->current_action ) ) {
					$this->{"setup_action_{$this->current_action}"}();
				}
			}
		);
	}

	/**
	 * Sets the current action based on querystring arguments. Defaults to 'list_orders'.
	 *
	 * @return void
	 */
	private function set_action(): void {
		$this->current_action = 'list_orders';

		if ( ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$this->current_action = 'edit_order';
		}
	}

	/**
	 * Registers the "Orders" menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Orders', 'woocommerce' ),
			__( 'Orders', 'woocommerce' ),
			'edit_others_shop_orders',
			'wc-orders',
			array( $this, 'output' )
		);

		// In some cases (such as if the authoritative order store was changed earlier in the current request) we
		// need an extra step to remove the menu entry for the menu post type.
		add_action(
			'admin_init',
			function() {
				remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_order' );
			}
		);
	}

	/**
	 * Outputs content for the current orders screen.
	 *
	 * @return void
	 */
	public function output(): void {
		switch ( $this->current_action ) {
			case 'list_orders':
			default:
				$this->orders_table->prepare_items();
				$this->orders_table->display();
				break;
		}
	}

	/**
	 * Handles initialization of the orders list table.
	 *
	 * @return void
	 */
	private function setup_action_list_orders(): void {
		$this->orders_table = new ListTable();
		$this->orders_table->setup();

		if ( $this->orders_table->current_action() ) {
			$this->orders_table->handle_bulk_actions();
		}

		$this->strip_http_referer();
	}

	/**
	 * Perform a redirect to remove teh `_wp_http_referer` string if present (see also wp-admin/edit.php where a similar
	 * process takes place), otherwise the size of this field builds to an unmanageable length over time.
	 */
	private function strip_http_referer() {
		$referer_url = esc_url_raw( wp_unslash( $_SERVER['_wp_http_referer'] ?? '' ) );

		if ( ! empty( $referer_url ) ) {
			wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $referer_url ) );
			exit;
		}
	}
}

<?php
namespace Automattic\WooCommerce\Internal\Admin\Orders;

/**
 * Controls the different pages/screens associated to the "Orders" menu page.
 */
class PageController {

	/**
	 * Instance of the orders list table.
	 *
	 * @var ListTable
	 */
	private $orders_table;

	/**
	 * Instance of orders edit form.
	 *
	 * @var Edit
	 */
	private $order_edit_form;

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
		switch ( isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '' ) {
			case 'edit':
				$this->current_action = 'edit_order';
				break;
			case 'new':
				$this->current_action = 'new_order';
				break;
			default:
				$this->current_action = 'list_orders';
				break;
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
			case 'edit_order':
			case 'new_order':
				if ( ! isset( $this->order_edit_form ) ) {
					$this->order_edit_form = new Edit();
					$this->order_edit_form->setup( $this->order );
				}
				$this->order_edit_form->display();
				break;
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
	}

	/**
	 * Handles initialization of the orders edit form.
	 *
	 * @return void
	 */
	private function setup_action_edit_order(): void {
		global $theorder;
		switch ( $this->current_action ) {
			case 'edit_order':
				$this->order = wc_get_order( absint( isset( $_GET['id'] ) ? $_GET['id'] : 0 ) );
				if ( 'edit_order' === $this->current_action && ( ! isset( $this->order ) || ! $this->order ) ) {
					wp_die( esc_html__( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'woocommerce' ) );
				}
				if ( ! current_user_can( 'edit_others_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have permission to edit this order', 'woocommerce' ) );
				}
				break;
			case 'new_order':
				$this->order = new \WC_Order();
				if ( ! current_user_can( 'publish_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You don\'t have permission to create a new order', 'woocommerce' ) );
				}
				break;
			default:
				wp_safe_redirect( admin_url( 'admin.php?page=wc-orders' ) );
				exit;
		}
		$theorder = $this->order;
	}

}

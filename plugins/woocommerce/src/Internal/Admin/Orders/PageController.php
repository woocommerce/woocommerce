<?php
namespace Automattic\WooCommerce\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Controls the different pages/screens associated to the "Orders" menu page.
 */
class PageController {

	use AccessiblePrivateMethods;

	/**
	 * Instance of the posts redirection controller.
	 *
	 * @var PostsRedirectionController
	 */
	private $redirection_controller;

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
	 * Order object to be used in edit/new form.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Verify that user has permission to edit orders.
	 *
	 * @return void
	 */
	private function verify_edit_permission() {
		if ( 'edit_order' === $this->current_action && ( ! isset( $this->order ) || ! $this->order ) ) {
			wp_die( esc_html__( 'You attempted to edit an order that does not exist. Perhaps it was deleted?', 'woocommerce' ) );
		}
		if ( ! current_user_can( 'edit_others_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this order', 'woocommerce' ) );
		}
	}

	/**
	 * Verify that user has permission to create order.
	 *
	 * @return void
	 */
	private function verify_create_permission() {
		if ( ! current_user_can( 'publish_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You don\'t have permission to create a new order', 'woocommerce' ) );
		}
		if ( isset( $this->order ) ) {
			$this->verify_edit_permission();
		}
	}

	/**
	 * Sets up the page controller, including registering the menu item.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->redirection_controller = new PostsRedirectionController( $this );

		// Register menu.
		if ( 'admin_menu' === current_action() ) {
			$this->register_menu();
		} else {
			add_action( 'admin_menu', 'register_menu', 9 );
		}

		$this->set_action();

		self::add_action( 'load-woocommerce_page_wc-orders', array( $this, 'handle_load_page_action' ) );
	}

	/**
	 * Perform initialization for the current action.
	 */
	private function handle_load_page_action() {
		if ( method_exists( $this, 'setup_action_' . $this->current_action ) ) {
			$this->{"setup_action_{$this->current_action}"}();
		}
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
		$this->orders_table = wc_get_container()->get( ListTable::class );
		$this->orders_table->setup();
		if ( $this->orders_table->current_action() ) {
			$this->orders_table->handle_bulk_actions();
		}

		$this->strip_http_referer();
	}

	/**
	 * Perform a redirect to remove the `_wp_http_referer` and `_wpnonce` strings if present in the URL (see also
	 * wp-admin/edit.php where a similar process takes place), otherwise the size of this field builds to an
	 * unmanageable length over time.
	 */
	private function strip_http_referer(): void {
		$current_url  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$stripped_url = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $current_url );

		if ( $stripped_url !== $current_url ) {
			wp_safe_redirect( $stripped_url );
			exit;
		}
	}

	/**
	 * Handles initialization of the orders edit form.
	 *
	 * @return void
	 */
	private function setup_action_edit_order(): void {
		global $theorder;
		$this->order = wc_get_order( absint( isset( $_GET['id'] ) ? $_GET['id'] : 0 ) );
		$this->verify_edit_permission();
		$theorder = $this->order;
	}

	/**
	 * Handles initialization of the orders edit form with a new order.
	 *
	 * @return void
	 */
	private function setup_action_new_order(): void {
		global $theorder;
		$this->verify_create_permission();
		$this->order = new \WC_Order();
		$this->order->set_object_read( false );
		$this->order->set_status( 'auto-draft' );
		$this->order->save();
		$theorder = $this->order;
	}

	/**
	 * Helper method to generate a link to the main orders screen.
	 *
	 * @return string Orders screen URL.
	 */
	public function get_orders_url(): string {
		return wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ?
			admin_url( 'admin.php?page=wc-orders' ) :
			admin_url( 'edit.php?post_type=shop_order' );
	}

	/**
	 * Helper method to generate edit link for an order.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return string Edit link.
	 */
	public function get_edit_url( int $order_id ) : string {
		return wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ?
			admin_url( 'admin.php?page=wc-orders&id=' . absint( $order_id ) ) . '&action=edit' :
			admin_url( 'post.php?post=' . absint( $order_id ) ) . '&action=edit';
	}

	/**
	 * Helper method to generate a link for creating order.
	 *
	 * @return string
	 */
	public function get_new_page_url() : string {
		return wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ?
			admin_url( 'admin.php?page=wc-orders&action=new' ) :
			admin_url( 'post-new.php?post_type=shop_order' );
	}

}

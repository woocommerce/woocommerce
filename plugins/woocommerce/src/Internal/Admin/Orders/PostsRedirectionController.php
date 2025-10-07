<?php
namespace Automattic\WooCommerce\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * When {@see OrdersTableDataStore} is in use, this class takes care of redirecting admins from CPT-based URLs
 * to the new ones.
 */
class PostsRedirectionController {

	/**
	 * Instance of the PageController class.
	 *
	 * @var PageController
	 */
	private $page_controller;

	/**
	 * Constructor.
	 *
	 * @param PageController $page_controller Page controller instance. Used to generate links/URLs.
	 */
	public function __construct( PageController $page_controller ) {
		$this->page_controller = $page_controller;

		if ( ! wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			return;
		}

		add_action(
			'admin_menu',
			function () {
				$this->maybe_update_menu_items();
			},
			9999
		);

		add_action(
			'load-edit.php',
			function() {
				$this->maybe_redirect_to_orders_page();
			}
		);

		add_action(
			'load-post-new.php',
			function() {
				$this->maybe_redirect_to_new_order_page();
			}
		);

		add_action(
			'load-post.php',
			function() {
				$this->maybe_redirect_to_edit_order_page();
			}
		);
	}

	/**
	 * If needed, performs a redirection to the main orders page.
	 *
	 * @return void
	 */
	private function maybe_redirect_to_orders_page(): void {
		$post_type = $_GET['post_type'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $post_type || ! in_array( $post_type, wc_get_order_types( 'admin-menu' ), true ) ) {
			return;
		}

		// Respect query args, except for 'post_type'.
		$query_args = wp_unslash( $_GET );
		$action     = $query_args['action'] ?? '';
		$posts      = $query_args['post'] ?? array();
		unset( $query_args['post_type'], $query_args['post'], $query_args['_wpnonce'], $query_args['_wp_http_referer'], $query_args['action'] );

		// Remap 'post_status' arg.
		if ( isset( $query_args['post_status'] ) ) {
			$query_args['status'] = $query_args['post_status'];
			unset( $query_args['post_status'] );
		}

		$new_url = $this->page_controller->get_base_page_url( $post_type );
		$new_url = add_query_arg( $query_args, $new_url );

		// Handle bulk actions.
		if ( $action && in_array( $action, array( 'trash', 'untrash', 'delete', 'mark_processing', 'mark_on-hold', 'mark_completed', 'mark_cancelled' ), true ) ) {
			check_admin_referer( 'bulk-posts' );

			$new_url = add_query_arg(
				array(
					'action'           => $action,
					'id'               => $posts,
					'_wp_http_referer' => $this->page_controller->get_orders_url(),
					'_wpnonce'         => wp_create_nonce( 'bulk-orders' ),
				),
				$new_url
			);
		}

		wp_safe_redirect( $new_url, 301 );
		exit;
	}

	/**
	 * If needed, performs a redirection to the new order page.
	 *
	 * @return void
	 */
	private function maybe_redirect_to_new_order_page(): void {
		$post_type = $_GET['post_type'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $post_type || ! in_array( $post_type, wc_get_order_types( 'admin-menu' ), true ) ) {
			return;
		}

		// Respect query args, except for 'post_type'.
		$query_args = wp_unslash( $_GET );
		unset( $query_args['post_type'] );

		$new_url = $this->page_controller->get_new_page_url( $post_type );
		$new_url = add_query_arg( $query_args, $new_url );

		wp_safe_redirect( $new_url, 301 );
		exit;
	}

	/**
	 * If needed, performs a redirection to the edit order page.
	 *
	 * @return void
	 */
	private function maybe_redirect_to_edit_order_page(): void {
		$post_id = absint( $_GET['post'] ?? 0 );
		if ( ! $post_id ) {
			return;
		}

		$redirect_from_types   = wc_get_order_types( 'admin-menu' );
		$redirect_from_types[] = 'shop_order_placehold';

		$post_type  = get_post_type( $post_id );
		$order_type = $post_type ? $post_type : OrderUtil::get_order_type( $post_id );
		if ( ! in_array( $order_type, $redirect_from_types, true ) || ! isset( $_GET['action'] ) ) {
			return;
		}

		// Respect query args, except for 'post'.
		$query_args = wp_unslash( $_GET );
		$action     = $query_args['action'];
		unset( $query_args['post'], $query_args['_wpnonce'], $query_args['_wp_http_referer'], $query_args['action'] );

		$new_url = '';

		switch ( $action ) {
			case 'edit':
				$new_url = $this->page_controller->get_edit_url( $post_id );
				break;

			case 'trash':
			case 'untrash':
			case 'delete':
				// Re-generate nonce if validation passes.
				check_admin_referer( $action . '-post_' . $post_id );

				$new_url = add_query_arg(
					array(
						'action'           => $action,
						'order'            => array( $post_id ),
						'_wp_http_referer' => $this->page_controller->get_orders_url(),
						'_wpnonce'         => wp_create_nonce( 'bulk-orders' ),
					),
					$this->page_controller->get_orders_url()
				);

				break;

			default:
				break;
		}

		if ( ! $new_url ) {
			return;
		}

		$new_url = add_query_arg( $query_args, $new_url );

		wp_safe_redirect( $new_url, 301 );
		exit;
	}

	/**
	 * Rewrites legacy post type menu items to point to the HPOS orders page when the main WooCommerce menu is not visible.
	 *
	 * @since 10.3.0
	 */
	private function maybe_update_menu_items(): void {
		global $pagenow, $submenu;

		// Do not conflict with CPT > HPOS redirection.
		if ( 'edit.php' === $pagenow && in_array( $_GET['post_type'] ?? '', wc_get_order_types( 'admin-menu' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( \WC_Admin_Menus::can_view_woocommerce_menu_item() ) {
			return;
		}

		$post_types = array_filter( array_map( 'get_post_type_object', wc_get_order_types( 'admin-menu' ) ) );
		foreach ( $post_types as $post_type ) {
			if ( ! current_user_can( $post_type->cap->edit_posts ) || ! isset( $submenu[ 'edit.php?post_type=' . $post_type->name ] ) ) {
				continue;
			}

			$post_type_menu = &$submenu[ 'edit.php?post_type=' . $post_type->name ];
			$menu_indexes   = array_flip( array_map( fn( $x ) => $x[2], $post_type_menu ) );

			// Rewrite URL for the legacy menu item.
			$post_type_menu[ $menu_indexes[ 'edit.php?post_type=' . $post_type->name ] ][2] = $this->page_controller->get_base_page_url( $post_type->name );

			// Hide the legacy "Add New" menu item.
			unset( $post_type_menu[ $menu_indexes[ "post-new.php?post_type={$post_type->name}" ] ] );
		}
	}
}

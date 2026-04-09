<?php
/**
 * WooCommerce Admin POS Staff Table List
 *
 * @package WooCommerce\Admin
 * @since   10.8.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * POS Staff table list class.
 *
 * @since 10.8.0
 */
class WC_Admin_POS_Staff_Table_List extends WP_List_Table {

	/**
	 * PIN service instance.
	 *
	 * @var POSPinService
	 */
	private $pin_service;

	/**
	 * Initialize the POS Staff table list.
	 *
	 * @since 10.8.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'staff',
				'plural'   => 'staff',
				'ajax'     => false,
			)
		);

		$this->pin_service = wc_get_container()->get( POSPinService::class );
	}

	/**
	 * No items found text.
	 *
	 * @since 10.8.0
	 */
	public function no_items(): void {
		esc_html_e( 'No POS staff found.', 'woocommerce' );
	}

	/**
	 * Get list columns.
	 *
	 * @since 10.8.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'user'       => __( 'User', 'woocommerce' ),
			'role'       => __( 'Role', 'woocommerce' ),
			'pin_status' => __( 'PIN status', 'woocommerce' ),
			'actions'    => __( 'Actions', 'woocommerce' ),
		);
	}

	/**
	 * Return user column.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_user( $user ) {
		$edit_url = admin_url(
			'admin.php?page=wc-settings&tab=point-of-sale&section=staff&edit-staff=' . $user->ID
		);

		$output  = '<strong><a href="' . esc_url( $edit_url ) . '">';
		$output .= esc_html( $user->display_name );
		$output .= '</a></strong>';
		$output .= '<br><span class="description">' . esc_html( $user->user_email ) . '</span>';

		return $output;
	}

	/**
	 * Return role column.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_role( $user ) {
		$roles = (array) $user->roles;

		if ( empty( $roles ) ) {
			return '';
		}

		$wp_roles  = wp_roles();
		$first     = reset( $roles );
		$role_name = isset( $wp_roles->role_names[ $first ] )
			? translate_user_role( $wp_roles->role_names[ $first ] )
			: $first;

		return esc_html( $role_name );
	}

	/**
	 * Return PIN status column.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_pin_status( $user ) {
		if ( $this->pin_service->has_pin( $user->ID ) ) {
			return '<mark class="yes"><span class="dashicons dashicons-yes-alt"></span> '
				. esc_html__( 'Active', 'woocommerce' ) . '</mark>';
		}

		return '<mark class="no">&ndash;</mark> ' . esc_html__( 'Not set', 'woocommerce' );
	}

	/**
	 * Return actions column.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_actions( $user ) {
		$edit_url = admin_url(
			'admin.php?page=wc-settings&tab=point-of-sale&section=staff&edit-staff=' . $user->ID
		);

		$actions = array();

		if ( $this->pin_service->has_pin( $user->ID ) ) {
			$actions[] = '<a href="' . esc_url( $edit_url ) . '">'
				. esc_html__( 'Reset PIN', 'woocommerce' ) . '</a>';

			$remove_url = wp_nonce_url(
				add_query_arg(
					array(
						'remove-pin' => $user->ID,
					),
					admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' )
				),
				'remove-pos-pin'
			);

			$actions[] = '<a class="submitdelete" href="' . esc_url( $remove_url ) . '">'
				. esc_html__( 'Remove PIN', 'woocommerce' ) . '</a>';
		} else {
			$actions[] = '<a href="' . esc_url( $edit_url ) . '">'
				. esc_html__( 'Set PIN', 'woocommerce' ) . '</a>';
		}

		return implode( ' | ', $actions );
	}

	/**
	 * Prepare table list items.
	 *
	 * @since 10.8.0
	 */
	public function prepare_items(): void {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$users = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);

		$filtered = array();
		foreach ( $users as $user ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles().
			if ( user_can( $user->ID, 'woocommerce_pos_access' ) ) {
				$filtered[] = $user;
			}
		}

		$total       = count( $filtered );
		$offset      = ( $current_page - 1 ) * $per_page;
		$this->items = array_slice( $filtered, $offset, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @since 10.8.0
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}
}

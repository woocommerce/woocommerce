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
		esc_html_e( 'No staff found.', 'woocommerce' );
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
			'pin_status' => __( 'PIN', 'woocommerce' ),
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
		$output = '<strong>';
		if ( current_user_can( 'edit_user', $user->ID ) ) {
			$output .= '<a href="' . esc_url( add_query_arg( array( 'user_id' => $user->ID ), admin_url( 'user-edit.php' ) ) ) . '">';
		}
		$output .= esc_html( $user->display_name );
		if ( current_user_can( 'edit_user', $user->ID ) ) {
			$output .= '</a>';
		}
		$output .= '</strong>';
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
		return esc_html( $this->get_role_name( $user ) );
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
			return '<span class="wc-pos-staff-status wc-pos-staff-status--active">'
				. esc_html__( 'Active', 'woocommerce' ) . '</span>';
		}

		return '<span class="wc-pos-staff-status">' . esc_html__( 'Not set', 'woocommerce' ) . '</span>';
	}

	/**
	 * Return actions column.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_actions( $user ) {
		return implode( ' | ', $this->get_row_actions( $user ) );
	}

	/**
	 * Prepare table list items.
	 *
	 * @since 10.8.0
	 */
	public function prepare_items(): void {
		$per_page              = 20;
		$current_page          = $this->get_pagenum();
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

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

	/**
	 * Generate table navigation markup.
	 *
	 * @since 10.8.0
	 * @param 'top'|'bottom' $which The location of the navigation.
	 */
	protected function display_tablenav( $which ): void {
		if ( 'top' === $which ) {
			return;
		}

		if ( empty( $this->_pagination_args['total_pages'] ) || $this->_pagination_args['total_pages'] < 2 ) {
			return;
		}

		echo '<div class="tablenav ' . esc_attr( $which ) . '">';
		$this->pagination( $which );
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Return row actions for a staff member.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return array
	 */
	private function get_row_actions( WP_User $user ): array {
		$edit_url = add_query_arg(
			array(
				'page'       => 'wc-settings',
				'tab'        => 'point-of-sale',
				'section'    => 'staff',
				'edit-staff' => $user->ID,
			),
			admin_url( 'admin.php' )
		);

		$actions = array(
			'<a href="' . esc_url( $edit_url ) . '">'
				. ( $this->pin_service->has_pin( $user->ID )
					? esc_html__( 'Reset PIN', 'woocommerce' )
					: esc_html__( 'Set PIN', 'woocommerce' ) )
				. '</a>',
		);

		if ( $this->pin_service->has_pin( $user->ID ) ) {
			$remove_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'       => 'wc-settings',
						'tab'        => 'point-of-sale',
						'section'    => 'staff',
						'remove-pin' => $user->ID,
					),
					admin_url( 'admin.php' )
				),
				'remove-pos-pin'
			);

			$actions[] = '<a class="submitdelete" href="' . esc_url( $remove_url ) . '">'
				. esc_html__( 'Remove PIN', 'woocommerce' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Return a translated role name for a user.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	private function get_role_name( WP_User $user ): string {
		$roles = (array) $user->roles;

		if ( empty( $roles ) ) {
			return '';
		}

		$wp_roles = wp_roles();
		$first    = reset( $roles );

		return isset( $wp_roles->role_names[ $first ] )
			? translate_user_role( $wp_roles->role_names[ $first ] )
			: $first;
	}
}

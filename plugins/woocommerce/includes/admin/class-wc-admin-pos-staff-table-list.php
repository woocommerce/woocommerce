<?php
/**
 * WooCommerce Admin POS Staff Table List
 *
 * @package WooCommerce\Admin
 * @since   11.0.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * POS Staff table list class.
 *
 * @since 11.0.0
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
	 * @since 11.0.0
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
	 * @since 11.0.0
	 */
	public function no_items(): void {
		esc_html_e( 'No staff found.', 'woocommerce' );
	}

	/**
	 * Get list columns.
	 *
	 * @since 11.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'user'       => __( 'User', 'woocommerce' ),
			'wp_role'    => __( 'WP role', 'woocommerce' ),
			'role'       => __( 'POS role', 'woocommerce' ),
			'pin_status' => __( 'PIN', 'woocommerce' ),
			'actions'    => __( 'Actions', 'woocommerce' ),
		);
	}

	/**
	 * Return user column.
	 *
	 * @since 11.0.0
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
	 * @since 11.0.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_role( $user ) {
		return esc_html( WC_Admin_POS_Staff::get_pos_preset_label( $user ) );
	}

	/**
	 * Return WP role column.
	 *
	 * Lists the user's non-pos_staff WP roles so the admin can tell at a glance
	 * whether someone is a POS-only account (created from the staff page) or an
	 * existing user (administrator, shop manager, …) who also has POS access.
	 * Disambiguates the "Delete user" vs "Remove staff" action on this row.
	 *
	 * @since 11.0.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_wp_role( $user ) {
		$wp_roles = wp_roles();
		$names    = array();
		foreach ( (array) $user->roles as $role ) {
			if ( POSCapabilities::POS_STAFF_ROLE === $role ) {
				continue;
			}
			if ( isset( $wp_roles->roles[ $role ]['name'] ) ) {
				$names[] = translate_user_role( $wp_roles->roles[ $role ]['name'] );
			}
		}

		if ( empty( $names ) ) {
			return '<span class="wc-pos-staff-pos-only">'
				. esc_html__( 'POS-only', 'woocommerce' )
				. '</span>';
		}

		return esc_html( implode( ', ', $names ) );
	}

	/**
	 * Return PIN status column.
	 *
	 * @since 11.0.0
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
	 * @since 11.0.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public function column_actions( $user ) {
		return implode( ' | ', $this->get_row_actions( $user ) );
	}

	/**
	 * Prepare table list items.
	 *
	 * @since 11.0.0
	 */
	public function prepare_items(): void {
		$per_page              = 20;
		$current_page          = $this->get_pagenum();
		$offset                = ( $current_page - 1 ) * $per_page;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$user_query = new \WP_User_Query(
			array_merge(
				POSCapabilities::pos_staff_user_query_args(),
				array(
					'orderby' => 'display_name',
					'order'   => 'ASC',
					'number'  => $per_page,
					'offset'  => $offset,
				)
			)
		);

		$this->items = $user_query->get_results();
		$total       = $user_query->get_total();

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
			)
		);
	}

	/**
	 * Generate table navigation markup.
	 *
	 * @since 11.0.0
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

		$post_url = add_query_arg(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'point-of-sale',
				'section' => 'staff',
			),
			admin_url( 'admin.php' )
		);

		$confirm = sprintf(
			/* translators: %s: staff display name. */
			__( 'Remove POS access for %s? Their WP account will remain.', 'woocommerce' ),
			$user->display_name
		);

		// Inline onclick is robust against script-load order and event-bubbling edge
		// cases. wp_json_encode renders a safely-quoted JS string literal that we then
		// drop into the HTML attribute through esc_attr.
		$onclick = sprintf( 'return confirm(%s);', wp_json_encode( $confirm ) );

		$remove  = '<form method="post" action="' . esc_url( $post_url ) . '" class="wc-pos-staff-remove-staff-form" style="display:inline;">';
		$remove .= wp_nonce_field( 'remove-pos-staff', 'woocommerce_pos_staff_remove_nonce', true, false );
		$remove .= '<input type="hidden" name="user_id" value="' . esc_attr( (string) $user->ID ) . '" />';
		$remove .= '<button type="submit" name="remove_pos_staff" class="button-link submitdelete" onclick="' . esc_attr( $onclick ) . '">'
			. esc_html__( 'Remove POS access', 'woocommerce' )
			. '</button>';
		$remove .= '</form>';

		return array(
			'<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'woocommerce' ) . '</a>',
			$remove,
		);
	}
}

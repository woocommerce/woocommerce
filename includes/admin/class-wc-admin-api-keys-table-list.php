<?php
/**
 * WooCommerce API Keys Table List
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Admin_API_Keys_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'key',
			'plural'   => 'keys',
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'description'   => __( 'Description', 'woocommerce' ),
			'truncated_key' => __( 'Consumer key ending in', 'woocommerce' ),
			'user'          => __( 'User', 'woocommerce' ),
			'permissions'   => __( 'Permissions', 'woocommerce' ),
			'last_access'   => __( 'Last access', 'woocommerce' ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_cb( $key ) {
		return sprintf( '<input type="checkbox" name="key[]" value="%1$s" />', $key['key_id'] );
	}

	/**
	 * Return description column.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_description( $key ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&edit-key=' . $key['key_id'] );

		$output = '<strong>';
		$output .= '<a href="' . esc_url( $url ) . '" class="row-title">';
		if ( empty( $key['description'] ) ) {
			$output .= esc_html__( 'API key', 'woocommerce' );
		} else {
			$output .= esc_html( $key['description'] );
		}
		$output .= '</a>';
		$output .= '</strong>';

		// Get actions
		$actions = array(
			'id'    => sprintf( __( 'ID: %d', 'woocommerce' ), $key['key_id'] ),
			'edit'  => '<a href="' . esc_url( $url ) . '">' . __( 'View/Edit', 'woocommerce' ) . '</a>',
			'trash' => '<a class="submitdelete" aria-label="' . esc_attr__( 'Revoke API key', 'woocommerce' ) . '" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'revoke-key' => $key['key_id'] ), admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' ) ), 'revoke' ) ) . '">' . __( 'Revoke', 'woocommerce' ) . '</a>',
		);

		$row_actions = array();

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * Return truncated consumer key column.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_truncated_key( $key ) {
		return '<code>&hellip;' . esc_html( $key['truncated_key'] ) . '</code>';
	}

	/**
	 * Return user column.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_user( $key ) {
		$user = get_user_by( 'id', $key['user_id'] );

		if ( ! $user ) {
			return '';
		}

		if ( current_user_can( 'edit_user', $user->ID ) ) {
			return '<a href="' . esc_url( add_query_arg( array( 'user_id' => $user->ID ), admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $user->display_name ) . '</a>';
		}

		return esc_html( $user->display_name );
	}

	/**
	 * Return permissions column.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_permissions( $key ) {
		$permission_key = $key['permissions'];
		$permissions    = array(
			'read'       => __( 'Read', 'woocommerce' ),
			'write'      => __( 'Write', 'woocommerce' ),
			'read_write' => __( 'Read/Write', 'woocommerce' ),
		);

		if ( isset( $permissions[ $permission_key ] ) ) {
			return esc_html( $permissions[ $permission_key ] );
		} else {
			return '';
		}
	}

	/**
	 * Return last access column.
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_last_access( $key ) {
		if ( ! empty( $key['last_access'] ) ) {
			/* translators: 1: last access date 2: last access time */
			$date = sprintf( __( '%1$s at %2$s', 'woocommerce' ), date_i18n( wc_date_format(), strtotime( $key['last_access'] ) ), date_i18n( wc_time_format(), strtotime( $key['last_access'] ) ) );

			return apply_filters( 'woocommerce_api_key_last_access_datetime', $date, $key['last_access'] );
		}

		return __( 'Unknown', 'woocommerce' );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'revoke' => __( 'Revoke', 'woocommerce' ),
		);
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = apply_filters( 'woocommerce_api_keys_settings_items_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$search = '';

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = "AND description LIKE '%" . esc_sql( $wpdb->esc_like( wc_clean( $_REQUEST['s'] ) ) ) . "%' ";
		}

		// Get the API keys
		$keys = $wpdb->get_results(
			"SELECT key_id, user_id, description, permissions, truncated_key, last_access FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search}" .
			$wpdb->prepare( "ORDER BY key_id DESC LIMIT %d OFFSET %d;", $per_page, $offset ), ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(key_id) FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search};" );

		$this->items = $keys;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}

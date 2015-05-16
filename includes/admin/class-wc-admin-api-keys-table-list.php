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
	 * Initialize the webhook table list
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'key', 'woocommerce' ),
			'plural'   => __( 'keys', 'woocommerce' ),
			'ajax'     => false
		) );
	}

	/**
	 * Get list columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'description' => __( 'Description', 'woocommerce' ),
			'user'        => __( 'User', 'woocommerce' ),
			'permissions' => __( 'Permissions', 'woocommerce' ),
			'actions'     => __( 'Actions', 'woocommerce' ),
		);
	}

	/**
	 * Column cb
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_cb( $key ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $key['key_id'] );
	}

	/**
	 * Return description column
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_description( $key ) {
		$output = '<strong>';
		if ( empty( $key['description'] ) ) {
			$output .= esc_html__( 'API Key', 'woocommerce' );
		} else {
			$output .= esc_html( $key['description'] );
		}
		$output .= '</strong>';

		return $output;
	}

	/**
	 * Return user column
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_user( $key ) {
		$user = get_user_by( 'id', $key['user_id'] );

		if ( ! $user ) {
			return '';
		}

		$user_name = ! empty( $user->data->display_name ) ? $user->data->display_name : $user->data->user_login;

		if ( current_user_can( 'edit_user' ) ) {
			return '<a href="' . esc_url( add_query_arg( array( 'user_id' => $user->ID ), admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $user_name ) . '</a>';
		}

		return esc_html( $user_name );
	}

	/**
	 * Return permissions column
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_permissions( $key ) {
		$permission_key = $key['permissions'];
		$permissions    = array(
			'read'       => __( 'Read', 'woocommerce' ),
			'write'      => __( 'Write', 'woocommerce' ),
			'read_write' => __( 'Read/Write', 'woocommerce' )
		);

		if ( isset( $permissions[ $permission_key ] ) ) {
			return esc_html( $permissions[ $permission_key ] );
		} else {
			return '';
		}
	}

	/**
	 * Return actions column
	 *
	 * @param  array $key
	 * @return string
	 */
	public function column_actions( $key ) {
		$actions = array();

		$actions['revoke'] = array(
			'url'       => wp_nonce_url( add_query_arg( 'revoke', $key['key_id'] ), 'revoke' ),
			'name'      => __( 'Revoke API Key', 'woocommerce' ),
			'action'    => 'revoke'
		);

		$actions['edit'] = array(
			'url'       => admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&edit-key=' . $key['key_id'] ),
			'name'      => __( 'View/Edit', 'woocommerce' ),
			'action'    => 'edit'
		);

		$output = '';

		foreach ( $actions as $action ) {
			$output .= sprintf( '<a class="button tips %1$s" href="%2$s" data-tip="%3$s">%3$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ) );
		}

		return $output;
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'revoke' => __( 'Revoke', 'woocommerce' )
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
			$search = "AND description LIKE '" . $wpdb->esc_like( $_REQUEST['s'] ) . "'";
		}

		// Get the API keys
		$keys = $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE 1 = 1
			$search
			LIMIT %d
			OFFSET %d
		 ", $per_page, $offset ), ARRAY_A );

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 $search" );

		$this->items = $keys;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => $count / $per_page
		) );
	}
}

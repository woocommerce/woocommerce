<?php
/**
 * WooCommerce Admin API Keys Class.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_API_Keys
 */
class WC_Admin_API_Keys {

	/**
	 * Initialize the API Keys admin actions
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is API Keys settings page
	 *
	 * @return bool
	 */
	private function is_api_keys_settings_page() {
		return isset( $_GET['page'] )
			&& 'wc-settings' == $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'api' == $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'keys' == isset( $_GET['section'] );
	}

	/**
	 * Page output
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['create-key'] ) || isset( $_GET['edit-key'] ) ) {
			$key_id   = isset( $_GET['edit-key'] ) ? absint( $_GET['edit-key'] ) : 0;
			$key_data = self::get_key_data( $key_id );

			include( 'settings/views/html-keys-edit.php' );
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output
	 */
	private static function table_list_output() {
		echo '<h3>' . __( 'Keys/Apps', 'woocommerce' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&create-key=1' ) ) . '" class="add-new-h2">' . __( 'Add Key', 'woocommerce' ) . '</a></h3>';

		$keys_table_list = new WC_Admin_API_Keys_Table_List();
		$keys_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="api" />';
		echo '<input type="hidden" name="section" value="keys" />';

		$keys_table_list->views();
		$keys_table_list->search_box( __( 'Search Key', 'woocommerce' ), 'key' );
		$keys_table_list->display();
	}

	/**
	 * Get key data
	 *
	 * @param  int $key_id
	 * @return array
	 */
	private static function get_key_data( $key_id ) {
		global $wpdb;

		$empty = array(
			'key_id'          => 0,
			'user_id'         => '',
			'description'     => '',
			'permissions'     => '',
			'consumer_key'    => '',
			'consumer_secret' => ''
		);

		if ( 0 == $key_id ) {
			return $empty;
		}

		$key = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, description, permissions, consumer_key, consumer_secret
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE key_id = %d
		", $key_id ), ARRAY_A );

		if ( is_null( $key ) ) {
			return $empty;
		}

		return $key;
	}

	/**
	 * API Keys admin actions
	 */
	public function actions() {
		if ( $this->is_api_keys_settings_page() ) {
			// Generate Key / Edit Key
			if ( isset( $_POST['update_api_key'] ) && isset( $_POST['key_id'] ) ) {
				$this->update_key();
			}

			// Revoke key
			if ( isset( $_GET['revoke-key'] ) ) {
				$this->revoke_key();
			}

			// Bulk actions
			if ( isset( $_GET['action'] ) && isset( $_GET['key'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['status'] ) ) {

			switch ( intval( $_GET['status'] ) ) {
				case 2 :
					WC_Admin_Settings::add_message( __( 'API Key generated successfully.', 'woocommerce' ) );
					break;
				case 3 :
					WC_Admin_Settings::add_message( __( 'API Key revoked successfully.', 'woocommerce' ) );
					break;
				case -1 :
					WC_Admin_Settings::add_error( __( 'Description is missing.', 'woocommerce' ) );
					break;
				case -2 :
					WC_Admin_Settings::add_error( __( 'User is missing.', 'woocommerce' ) );
					break;
				case -3 :
					WC_Admin_Settings::add_error( __( 'Description is missing.', 'woocommerce' ) );
					break;

				default :
					WC_Admin_Settings::add_message( __( 'API Key updated successfully.', 'woocommerce' ) );
					break;
			}
		}
	}

	/**
	 * Update Key
	 */
	private function update_key() {
		global $wpdb;

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$url    = admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' );
		$key_id = absint( $_POST['key_id'] );
		$status = 1;

		try {
			if ( empty( $_POST['key_description'] ) ) {
				throw new Exception( 'Description is missing', -1 );
			}
			if ( empty( $_POST['key_user'] ) ) {
				throw new Exception( 'User is missing', -2 );
			}
			if ( empty( $_POST['key_permissions'] ) ) {
				throw new Exception( 'permissions is missing', -3 );
			}

			$description = sanitize_text_field( $_POST['key_description'] );
			$permissions = ( in_array( $_POST['key_permissions'], array( 'read', 'write', 'read_write' ) ) ) ? sanitize_text_field( $_POST['key_permissions'] ) : 'read';
			$user_id     = absint( $_POST['key_user'] );

			if ( 0 < $key_id ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_api_keys',
					array(
						'user_id'     => $user_id,
						'description' => $description,
						'permissions' => $permissions
					),
					array( 'key_id' => $key_id ),
					array(
						'%d',
						'%s',
						'%s'
					),
					array( '%d' )
				);
			} else {
				$status          = 2;
				$user            = get_userdata( $user_id );
				$consumer_key    = 'ck_' . hash( 'md5', $user->user_login . date( 'U' ) . mt_rand() );
				$consumer_secret = 'cs_' . hash( 'md5', $user->ID . date( 'U' ) . mt_rand() );

				$wpdb->insert(
					$wpdb->prefix . 'woocommerce_api_keys',
					array(
						'user_id'         => $user_id,
						'description'     => $description,
						'permissions'     => $permissions,
						'consumer_key'    => $consumer_key,
						'consumer_secret' => $consumer_secret
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
						'%s'
					)
				);

				$key_id = $wpdb->insert_id;
			}

			wp_redirect( esc_url_raw( add_query_arg( array( 'edit-key' => $key_id, 'status' => $status ), $url ) ) );
			exit();
		} catch ( Exception $e ) {
			wp_redirect( esc_url_raw( add_query_arg( array( 'edit-key' => $key_id, 'status' => $e->getCode() ), $url ) ) );
			exit();
		}
	}

	/**
	 * Revoke key
	 */
	private function revoke_key() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'revoke' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$key_id = absint( $_GET['revoke-key'] );
		$this->remove_key( $key_id );

		wp_redirect( esc_url_raw( add_query_arg( array( 'status' => 3 ), admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' ) ) ) );
		exit();
	}

	/**
	 * Bulk actions
	 */
	private function bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$keys = array_map( 'absint', (array) $_GET['key'] );

		if ( 'revoke' == $_GET['action'] ) {
			$this->bulk_revoke_key( $keys );
		}
	}

	/**
	 * Bulk revoke key
	 *
	 * @param array $keys
	 */
	private function bulk_revoke_key( $keys ) {
		foreach ( $keys as $key_id ) {
			$this->remove_key( $key_id );
		}
	}

	/**
	 * Remove key
	 *
	 * @param  int $key_id
	 * @return bool
	 */
	private function remove_key( $key_id ) {
		global $wpdb;

		$delete = $wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );

		return $delete;
	}
}

new WC_Admin_API_Keys();

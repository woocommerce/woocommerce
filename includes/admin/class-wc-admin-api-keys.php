<?php
/**
 * WooCommerce Admin API Keys Class
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
 * WC_Admin_API_Keys.
 */
class WC_Admin_API_Keys {

	/**
	 * Initialize the API Keys admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is API Keys settings page.
	 * @return bool
	 */
	private function is_api_keys_settings_page() {
		return isset( $_GET['page'] )
			&& 'wc-settings' === $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'api' === $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'keys' === $_GET['section'];
	}

	/**
	 * Page output.
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
	 * Table list output.
	 */
	private static function table_list_output() {

		global $wpdb;

		echo '<h2>' . __( 'Keys/Apps', 'woocommerce' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&create-key=1' ) ) . '" class="add-new-h2">' . __( 'Add key', 'woocommerce' ) . '</a></h2>';

		// Get the API keys count
		$count = $wpdb->get_var( "SELECT COUNT(key_id) FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1;" );

		if ( absint( $count ) && $count > 0 ) {
			$keys_table_list = new WC_Admin_API_Keys_Table_List();
			$keys_table_list->prepare_items();

			echo '<input type="hidden" name="page" value="wc-settings" />';
			echo '<input type="hidden" name="tab" value="api" />';
			echo '<input type="hidden" name="section" value="keys" />';

			$keys_table_list->views();
			$keys_table_list->search_box( __( 'Search key', 'woocommerce' ), 'key' );
			$keys_table_list->display();
		} else {
			echo '<div class="woocommerce-BlankState woocommerce-BlankState--api">';
			?>
			<h2 class="woocommerce-BlankState-message"><?php _e( 'The WooCommerce REST API allows external apps to view and manage store data. Access is granted only to those with valid API keys.', 'woocommerce' ); ?></h2>
			<a class="woocommerce-BlankState-cta button-primary button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&create-key=1' ) ); ?>"><?php _e( 'Create an API key', 'woocommerce' ); ?></a>

			<?php echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions  { display: none; } </style></div>';
		}
	}

	/**
	 * Get key data.
	 *
	 * @param  int $key_id
	 * @return array
	 */
	private static function get_key_data( $key_id ) {
		global $wpdb;

		$empty = array(
			'key_id'        => 0,
			'user_id'       => '',
			'description'   => '',
			'permissions'   => '',
			'truncated_key' => '',
			'last_access'   => '',
		);

		if ( 0 == $key_id ) {
			return $empty;
		}

		$key = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, description, permissions, truncated_key, last_access
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE key_id = %d
		", $key_id ), ARRAY_A );

		if ( is_null( $key ) ) {
			return $empty;
		}

		return $key;
	}

	/**
	 * API Keys admin actions.
	 */
	public function actions() {
		if ( $this->is_api_keys_settings_page() ) {
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
		if ( isset( $_GET['revoked'] ) && 1 == $_GET['revoked'] ) {
			WC_Admin_Settings::add_message( __( 'API key revoked successfully.', 'woocommerce' ) );
		}
	}

	/**
	 * Revoke key.
	 */
	private function revoke_key() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'revoke' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$key_id = absint( $_GET['revoke-key'] );
		$this->remove_key( $key_id );

		wp_redirect( esc_url_raw( add_query_arg( array( 'revoked' => 1 ), admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' ) ) ) );
		exit();
	}

	/**
	 * Bulk actions.
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
	 * Bulk revoke key.
	 *
	 * @param array $keys
	 */
	private function bulk_revoke_key( $keys ) {
		foreach ( $keys as $key_id ) {
			$this->remove_key( $key_id );
		}
	}

	/**
	 * Remove key.
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

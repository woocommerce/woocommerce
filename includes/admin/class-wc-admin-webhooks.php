<?php
/**
 * WooCommerce Admin Webhooks Class
 *
 * @package WooCommerce\Admin
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Webhooks.
 */
class WC_Admin_Webhooks {

	/**
	 * Initialize the webhooks admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'woocommerce_settings_page_init', array( $this, 'screen_option' ) );
		add_filter( 'woocommerce_save_settings_api_webhooks', array( $this, 'allow_save_settings' ) );
	}

	/**
	 * Check if should allow save settings.
	 * This prevents "Your settings have been saved." notices on the table list.
	 *
	 * @param  bool $allow If allow save settings.
	 * @return bool
	 */
	public function allow_save_settings( $allow ) {
		if ( ! isset( $_GET['edit-webhook'] ) ) {// WPCS: input var okay, CSRF ok.
			return false;
		}

		return $allow;
	}

	/**
	 * Check if is webhook settings page.
	 *
	 * @return bool
	 */
	private function is_webhook_settings_page() {
		return isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'advanced' === $_GET['tab'] && 'webhooks' === $_GET['section']; // WPCS: input var okay, CSRF ok.
	}

	/**
	 * Save method.
	 */
	private function save() {
		check_admin_referer( 'woocommerce-settings' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to update Webhooks', 'woocommerce' ) );
		}

		$errors     = array();
		$webhook_id = isset( $_POST['webhook_id'] ) ? absint( $_POST['webhook_id'] ) : 0;  // WPCS: input var okay, CSRF ok.
		$webhook    = new WC_Webhook( $webhook_id );

		// Name.
		if ( ! empty( $_POST['webhook_name'] ) ) { // WPCS: input var okay, CSRF ok.
			$name = sanitize_text_field( wp_unslash( $_POST['webhook_name'] ) ); // WPCS: input var okay, CSRF ok.
		} else {
			$name = sprintf(
				/* translators: %s: date */
				__( 'Webhook created on %s', 'woocommerce' ),
				// @codingStandardsIgnoreStart
				strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) )
				// @codingStandardsIgnoreEnd
			);
		}

		$webhook->set_name( $name );

		if ( ! $webhook->get_user_id() ) {
			$webhook->set_user_id( get_current_user_id() );
		}

		// Status.
		$webhook->set_status( ! empty( $_POST['webhook_status'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_status'] ) ) : 'disabled' ); // WPCS: input var okay, CSRF ok.

		// Delivery URL.
		$delivery_url = ! empty( $_POST['webhook_delivery_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_delivery_url'] ) ) : ''; // WPCS: input var okay, CSRF ok.

		if ( wc_is_valid_url( $delivery_url ) ) {
			$webhook->set_delivery_url( $delivery_url );
		}

		// Secret.
		$secret = ! empty( $_POST['webhook_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ) ) : wp_generate_password( 50, true, true ); // WPCS: input var okay, CSRF ok.
		$webhook->set_secret( $secret );

		// Topic.
		if ( ! empty( $_POST['webhook_topic'] ) ) { // WPCS: input var okay, CSRF ok.
			$resource = '';
			$event    = '';

			switch ( $_POST['webhook_topic'] ) { // WPCS: input var okay, CSRF ok.
				case 'action':
					$resource = 'action';
					$event    = ! empty( $_POST['webhook_action_event'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_action_event'] ) ) : ''; // WPCS: input var okay, CSRF ok.
					break;

				default:
					list( $resource, $event ) = explode( '.', sanitize_text_field( wp_unslash( $_POST['webhook_topic'] ) ) ); // WPCS: input var okay, CSRF ok.
					break;
			}

			$topic = $resource . '.' . $event;

			if ( wc_is_webhook_valid_topic( $topic ) ) {
				$webhook->set_topic( $topic );
			} else {
				$errors[] = __( 'Webhook topic unknown. Please select a valid topic.', 'woocommerce' );
			}
		}

		// API version.
		$webhook->set_api_version( ! empty( $_POST['webhook_api_version'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_api_version'] ) ) : 'wp_api_v2' ); // WPCS: input var okay, CSRF ok.

		$webhook->save();

		// Run actions.
		do_action( 'woocommerce_webhook_options_save', $webhook->get_id() );
		if ( $errors ) {
			// Redirect to webhook edit page to avoid settings save actions.
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=' . $webhook->get_id() . '&error=' . rawurlencode( implode( '|', $errors ) ) ) );
			exit();
		} elseif ( isset( $_POST['webhook_status'] ) && 'active' === $_POST['webhook_status'] && $webhook->get_pending_delivery() ) { // WPCS: input var okay, CSRF ok.
			// Ping the webhook at the first time that is activated.
			$result = $webhook->deliver_ping();

			if ( is_wp_error( $result ) ) {
				// Redirect to webhook edit page to avoid settings save actions.
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=' . $webhook->get_id() . '&error=' . rawurlencode( $result->get_error_message() ) ) );
				exit();
			}
		}

		// Redirect to webhook edit page to avoid settings save actions.
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=' . $webhook->get_id() . '&updated=1' ) );
		exit();
	}

	/**
	 * Bulk delete.
	 *
	 * @param array $webhooks List of webhooks IDs.
	 */
	public static function bulk_delete( $webhooks ) {
		foreach ( $webhooks as $webhook_id ) {
			$webhook = new WC_Webhook( (int) $webhook_id );
			$webhook->delete( true );
		}

		$qty    = count( $webhooks );
		$status = isset( $_GET['status'] ) ? '&status=' . sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // WPCS: input var okay, CSRF ok.

		// Redirect to webhooks page.
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks' . $status . '&deleted=' . $qty ) );
		exit();
	}

	/**
	 * Delete webhook.
	 */
	private function delete() {
		check_admin_referer( 'delete-webhook' );

		if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
			$webhook_id = absint( $_GET['delete'] ); // WPCS: input var okay, CSRF ok.

			if ( $webhook_id ) {
				$this->bulk_delete( array( $webhook_id ) );
			}
		}
	}

	/**
	 * Webhooks admin actions.
	 */
	public function actions() {
		if ( $this->is_webhook_settings_page() ) {
			// Save.
			if ( isset( $_POST['save'] ) && isset( $_POST['webhook_id'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->save();
			}

			// Delete webhook.
			if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->delete();
			}
		}
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button.
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['edit-webhook'] ) ) { // WPCS: input var okay, CSRF ok.
			$webhook_id = absint( $_GET['edit-webhook'] ); // WPCS: input var okay, CSRF ok.
			$webhook    = new WC_Webhook( $webhook_id );

			include 'settings/views/html-webhooks-edit.php';
			return;
		}

		self::table_list_output();
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['deleted'] ) ) { // WPCS: input var okay, CSRF ok.
			$deleted = absint( $_GET['deleted'] ); // WPCS: input var okay, CSRF ok.

			/* translators: %d: count */
			WC_Admin_Settings::add_message( sprintf( _n( '%d webhook permanently deleted.', '%d webhooks permanently deleted.', $deleted, 'woocommerce' ), $deleted ) );
		}

		if ( isset( $_GET['updated'] ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::add_message( __( 'Webhook updated successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['created'] ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::add_message( __( 'Webhook created successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['error'] ) ) { // WPCS: input var okay, CSRF ok.
			foreach ( explode( '|', sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) as $message ) { // WPCS: input var okay, CSRF ok.
				WC_Admin_Settings::add_error( trim( $message ) );
			}
		}
	}

	/**
	 * Add screen option.
	 */
	public function screen_option() {
		global $webhooks_table_list;

		if ( ! isset( $_GET['edit-webhook'] ) && $this->is_webhook_settings_page() ) { // WPCS: input var okay, CSRF ok.
			$webhooks_table_list = new WC_Admin_Webhooks_Table_List();

			// Add screen option.
			add_screen_option(
				'per_page',
				array(
					'default' => 10,
					'option'  => 'woocommerce_webhooks_per_page',
				)
			);
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		global $webhooks_table_list;

		echo '<h2>' . esc_html__( 'Webhooks', 'woocommerce' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=0' ) ) . '" class="add-new-h2">' . esc_html__( 'Add webhook', 'woocommerce' ) . '</a></h2>';

		// Get the webhooks count.
		$data_store = WC_Data_Store::load( 'webhook' );
		$count      = count( $data_store->get_webhooks_ids() );

		if ( 0 < $count ) {
			$webhooks_table_list->process_bulk_action();
			$webhooks_table_list->prepare_items();

			echo '<input type="hidden" name="page" value="wc-settings" />';
			echo '<input type="hidden" name="tab" value="api" />';
			echo '<input type="hidden" name="section" value="webhooks" />';

			$webhooks_table_list->views();
			$webhooks_table_list->search_box( __( 'Search webhooks', 'woocommerce' ), 'webhook' );
			$webhooks_table_list->display();
		} else {
			echo '<div class="woocommerce-BlankState woocommerce-BlankState--webhooks">';
			?>
			<h2 class="woocommerce-BlankState-message"><?php esc_html_e( 'Webhooks are event notifications sent to URLs of your choice. They can be used to integrate with third-party services which support them.', 'woocommerce' ); ?></h2>
			<a class="woocommerce-BlankState-cta button-primary button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks&edit-webhook=0' ) ); ?>"><?php esc_html_e( 'Create a new webhook', 'woocommerce' ); ?></a>
			<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions { display: none; }</style>
			<?php
		}
	}

	/**
	 * Logs output.
	 *
	 * @deprecated 3.3.0
	 * @param WC_Webhook $webhook Deprecated.
	 */
	public static function logs_output( $webhook = 'deprecated' ) {
		wc_deprecated_function( 'WC_Admin_Webhooks::logs_output', '3.3' );
	}

	/**
	 * Get the webhook topic data.
	 *
	 * @param WC_Webhook $webhook Webhook instance.
	 *
	 * @return array
	 */
	public static function get_topic_data( $webhook ) {
		$topic    = $webhook->get_topic();
		$event    = '';
		$resource = '';

		if ( $topic ) {
			list( $resource, $event ) = explode( '.', $topic );

			if ( 'action' === $resource ) {
				$topic = 'action';
			} elseif ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ), true ) ) {
				$topic = 'custom';
			}
		}

		return array(
			'topic'    => $topic,
			'event'    => $event,
			'resource' => $resource,
		);
	}

	/**
	 * Get the logs navigation.
	 *
	 * @deprecated 3.3.0
	 * @param int        $total Deprecated.
	 * @param WC_Webhook $webhook Deprecated.
	 */
	public static function get_logs_navigation( $total, $webhook ) {
		wc_deprecated_function( 'WC_Admin_Webhooks::get_logs_navigation', '3.3' );
	}
}

new WC_Admin_Webhooks();

<?php
/**
 * WooCommerce Admin Webhooks Class
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Webhooks.
 */
class WC_Admin_Webhooks {

	/**
	 * Initialize the webhooks admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is webhook settings page.
	 *
	 * @return bool
	 */
	private function is_webhook_settings_page() {
		return isset( $_GET['page'] )
			&& 'wc-settings' == $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'api' == $_GET['tab']
			&& isset( $_GET['section'] )
			&& 'webhooks' == isset( $_GET['section'] );
	}

	/**
	 * Updated the Webhook name.
	 *
	 * @param int $webhook_id
	 */
	private function update_name( $webhook_id ) {
		global $wpdb;

		// @codingStandardsIgnoreStart
		/* translators: %s: date` */
		$name = ! empty( $_POST['webhook_name'] ) ? $_POST['webhook_name'] : sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) );
		// @codingStandardsIgnoreEnd
		$wpdb->update( $wpdb->posts, array( 'post_title' => $name ), array( 'ID' => $webhook_id ) );
	}

	/**
	 * Updated the Webhook status.
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_status( $webhook ) {
		$status = ! empty( $_POST['webhook_status'] ) ? wc_clean( $_POST['webhook_status'] ) : '';

		$webhook->update_status( $status );
	}

	/**
	 * Updated the Webhook delivery URL.
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_delivery_url( $webhook ) {
		$delivery_url = ! empty( $_POST['webhook_delivery_url'] ) ? $_POST['webhook_delivery_url'] : '';

		if ( wc_is_valid_url( $delivery_url ) ) {
			$webhook->set_delivery_url( $delivery_url );
		}
	}

	/**
	 * Updated the Webhook secret.
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_secret( $webhook ) {
		$secret = ! empty( $_POST['webhook_secret'] ) ? $_POST['webhook_secret'] : wp_generate_password( 50, true, true );

		$webhook->set_secret( $secret );
	}

	/**
	 * Updated the Webhook topic.
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_topic( $webhook ) {
		if ( ! empty( $_POST['webhook_topic'] ) ) {

			$resource = '';
			$event    = '';

			switch ( $_POST['webhook_topic'] ) {
				case 'custom' :
					if ( ! empty( $_POST['webhook_custom_topic'] ) ) {
						list( $resource, $event ) = explode( '.', wc_clean( $_POST['webhook_custom_topic'] ) );
					}
					break;
				case 'action' :
					$resource = 'action';
					$event    = ! empty( $_POST['webhook_action_event'] ) ? wc_clean( $_POST['webhook_action_event'] ) : '';
					break;

				default :
					list( $resource, $event ) = explode( '.', wc_clean( $_POST['webhook_topic'] ) );
					break;
			}

			$topic = $resource . '.' . $event;

			if ( wc_is_webhook_valid_topic( $topic ) ) {
				$webhook->set_topic( $topic );
			}
		}
	}

	/**
	 * Update webhook api version.
	 *
	 * @param WC_Webhook $webhook Webhook instance.
	 */
	private function update_api_version( $webhook ) {
		$version = ! empty( $_POST['webhook_api_version'] ) ? wc_clean( $_POST['webhook_api_version'] ) : 'wp_api_v2';

		$webhook->set_api_version( $version );
	}

	/**
	 * Save method.
	 */
	private function save() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$webhook_id = absint( $_POST['webhook_id'] );

		if ( ! current_user_can( 'edit_shop_webhook', $webhook_id ) ) {
			return;
		}

		$webhook = new WC_Webhook( $webhook_id );

		// Name
		$this->update_name( $webhook->id );

		// Status
		$this->update_status( $webhook );

		// Delivery URL
		$this->update_delivery_url( $webhook );

		// Secret
		$this->update_secret( $webhook );

		// Topic
		$this->update_topic( $webhook );

		// API version.
		$this->update_api_version( $webhook );

		// Update date.
		wp_update_post( array( 'ID' => $webhook->id, 'post_modified' => current_time( 'mysql' ) ) );

		// Run actions
		do_action( 'woocommerce_webhook_options_save', $webhook->id );

		delete_transient( 'woocommerce_webhook_ids' );

		// Ping the webhook at the first time that is activated
		$pending_delivery = get_post_meta( $webhook->id, '_webhook_pending_delivery', true );

		if ( isset( $_POST['webhook_status'] ) && 'active' === $_POST['webhook_status'] && $pending_delivery ) {
			$result = $webhook->deliver_ping();

			if ( is_wp_error( $result ) ) {
				// Redirect to webhook edit page to avoid settings save actions
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&error=' . urlencode( $result->get_error_message() ) ) );
				exit();
			}
		}

		// Redirect to webhook edit page to avoid settings save actions
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&updated=1' ) );
		exit();
	}

	/**
	 * Create Webhook.
	 */
	private function create() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'create-webhook' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		if ( ! current_user_can( 'publish_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to create Webhooks!', 'woocommerce' ) );
		}

		$webhook_id = wp_insert_post( array(
			'post_type'     => 'shop_webhook',
			'post_status'   => 'pending',
			'ping_status'   => 'closed',
			'post_author'   => get_current_user_id(),
			'post_password' => strlen( ( $password = uniqid( 'webhook_' ) ) ) > 20 ? substr( $password, 0, 20 ) : $password,
			// @codingStandardsIgnoreStart
			/* translators: %s: date */
			'post_title'    => sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) ),
			// @codingStandardsIgnoreEnd
			'comment_status' => 'open',
		) );

		if ( is_wp_error( $webhook_id ) ) {
			wp_die( $webhook_id->get_error_messages() );
		}

		update_post_meta( $webhook_id, '_webhook_pending_delivery', true );
		$webhook = new WC_Webhook( $webhook_id );
		$webhook->set_api_version( 'wp_api_v2' );

		delete_transient( 'woocommerce_webhook_ids' );

		// Redirect to edit page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook_id . '&created=1' ) );
		exit();
	}

	/**
	 * Bulk trash/delete.
	 *
	 * @param array $webhooks
	 * @param bool  $delete
	 */
	private function bulk_trash( $webhooks, $delete = false ) {
		foreach ( $webhooks as $webhook_id ) {
			if ( $delete ) {
				wp_delete_post( $webhook_id, true );
			} else {
				wp_trash_post( $webhook_id );
			}
		}

		$type   = ! EMPTY_TRASH_DAYS || $delete ? 'deleted' : 'trashed';
		$qty    = count( $webhooks );
		$status = isset( $_GET['status'] ) ? '&status=' . sanitize_text_field( $_GET['status'] ) : '';

		delete_transient( 'woocommerce_webhook_ids' );

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks' . $status . '&' . $type . '=' . $qty ) );
		exit();
	}

	/**
	 * Bulk untrash.
	 *
	 * @param array $webhooks
	 */
	private function bulk_untrash( $webhooks ) {
		foreach ( $webhooks as $webhook_id ) {
			wp_untrash_post( $webhook_id );
		}

		$qty = count( $webhooks );

		delete_transient( 'woocommerce_webhook_ids' );

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&status=trash&untrashed=' . $qty ) );
		exit();
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		if ( ! current_user_can( 'edit_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to edit Webhooks!', 'woocommerce' ) );
		}

		$webhooks = array_map( 'absint', (array) $_GET['webhook'] );

		switch ( $_GET['action'] ) {
			case 'trash' :
				$this->bulk_trash( $webhooks );
				break;
			case 'untrash' :
				$this->bulk_untrash( $webhooks );
				break;
			case 'delete' :
				$this->bulk_trash( $webhooks, true );
				break;
			default :
				break;
		}
	}

	/**
	 * Empty Trash.
	 */
	private function empty_trash() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'empty_trash' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		if ( ! current_user_can( 'delete_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to delete Webhooks!', 'woocommerce' ) );
		}

		$webhooks = get_posts( array(
			'post_type'           => 'shop_webhook',
			'ignore_sticky_posts' => true,
			'nopaging'            => true,
			'post_status'         => 'trash',
			'fields'              => 'ids',
		) );

		foreach ( $webhooks as $webhook_id ) {
			wp_delete_post( $webhook_id, true );
		}

		$qty = count( $webhooks );

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&deleted=' . $qty ) );
		exit();
	}

	/**
	 * Webhooks admin actions.
	 */
	public function actions() {
		if ( $this->is_webhook_settings_page() ) {
			// Save
			if ( isset( $_POST['save'] ) && isset( $_POST['webhook_id'] ) ) {
				$this->save();
			}

			// Create
			if ( isset( $_GET['create-webhook'] ) ) {
				$this->create();
			}

			// Bulk actions
			if ( isset( $_GET['action'] ) && isset( $_GET['webhook'] ) ) {
				$this->bulk_actions();
			}

			// Empty trash
			if ( isset( $_GET['empty_trash'] ) ) {
				$this->empty_trash();
			}
		}
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['edit-webhook'] ) ) {
			$webhook_id = absint( $_GET['edit-webhook'] );
			$webhook    = new WC_Webhook( $webhook_id );

			if ( 'trash' != $webhook->post_data->post_status ) {
				include( 'settings/views/html-webhooks-edit.php' );
				return;
			}
		}

		self::table_list_output();
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['trashed'] ) ) {
			$trashed = absint( $_GET['trashed'] );

			/* translators: %d: count */
			WC_Admin_Settings::add_message( sprintf( _n( '%d webhook moved to the Trash.', '%d webhooks moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) );
		}

		if ( isset( $_GET['untrashed'] ) ) {
			$untrashed = absint( $_GET['untrashed'] );

			/* translators: %d: count */
			WC_Admin_Settings::add_message( sprintf( _n( '%d webhook restored from the Trash.', '%d webhooks restored from the Trash.', $untrashed, 'woocommerce' ), $untrashed ) );
		}

		if ( isset( $_GET['deleted'] ) ) {
			$deleted = absint( $_GET['deleted'] );

			/* translators: %d: count */
			WC_Admin_Settings::add_message( sprintf( _n( '%d webhook permanently deleted.', '%d webhooks permanently deleted.', $deleted, 'woocommerce' ), $deleted ) );
		}

		if ( isset( $_GET['updated'] ) ) {
			WC_Admin_Settings::add_message( __( 'Webhook updated successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['created'] ) ) {
			WC_Admin_Settings::add_message( __( 'Webhook created successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['error'] ) ) {
			WC_Admin_Settings::add_error( wc_clean( $_GET['error'] ) );
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		echo '<h2>' . __( 'Webhooks', 'woocommerce' ) . ' <a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&create-webhook=1' ), 'create-webhook' ) ) . '" class="add-new-h2">' . __( 'Add webhook', 'woocommerce' ) . '</a></h2>';

		$webhooks_table_list = new WC_Admin_Webhooks_Table_List();
		$webhooks_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="api" />';
		echo '<input type="hidden" name="section" value="webhooks" />';

		$webhooks_table_list->views();
		$webhooks_table_list->search_box( __( 'Search webhooks', 'woocommerce' ), 'webhook' );
		$webhooks_table_list->display();
	}

	/**
	 * Logs output.
	 *
	 * @param WC_Webhook $webhook
	 */
	public static function logs_output( $webhook ) {
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;
		$args    = array(
			'post_id' => $webhook->id,
			'status'  => 'approve',
			'type'    => 'webhook_delivery',
			'number'  => 10,
		);

		if ( 1 < $current ) {
			$args['offset'] = ( $current - 1 ) * 10;
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$logs = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		if ( $logs ) {
			include_once( dirname( __FILE__ ) . '/settings/views/html-webhook-logs.php' );
		} else {
			echo '<p>' . __( 'This Webhook has no log yet.', 'woocommerce' ) . '</p>';
		}
	}

	/**
	 * Get the webhook topic data.
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
			} elseif ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) ) {
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
	 * @param  int $total
	 *
	 * @return string
	 */
	public static function get_logs_navigation( $total, $webhook ) {
		$pages   = ceil( $total / 10 );
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;

		$html = '<div class="webhook-logs-navigation">';

			$html .= '<p class="info" style="float: left;"><strong>';
			/* translators: 1: items count (i.e. 8 items) 2: current page 3: total pages */
			$html .= sprintf(
				__( '%1$s &ndash; Page %2$d of %3$d', 'woocommerce' ),
				sprintf( _n( '%d item', '%d items', $total, 'woocommerce' ), $total ),
				$current,
				$pages
			);
			$html .= '</strong></p>';

			if ( 1 < $pages ) {
				$html .= '<p class="tools" style="float: right;">';
					if ( 1 == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</button> ';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current - 1 ) ) . '#webhook-logs">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</a> ';
					}

					if ( $pages == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</button>';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current + 1 ) ) . '#webhook-logs">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</a>';
					}
				$html .= '</p>';
			}

		$html .= '<div class="clear"></div></div>';

		return $html;
	}
}

new WC_Admin_Webhooks();

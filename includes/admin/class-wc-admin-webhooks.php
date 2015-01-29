<?php
/**
 * WooCommerce Admin Webhooks Class.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Webhooks
 */
class WC_Admin_Webhooks {

	/**
	 * Initialize the webhooks admin actions
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is webhook settings page
	 *
	 * @return bool
	 */
	private function is_webhook_settings_page() {
		return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['tab'] ) && 'webhooks' == $_GET['tab'];
	}

	/**
	 * Updated the Webhook name
	 *
	 * @param int $webhook_id
	 */
	private function update_name( $webhook_id ) {
		global $wpdb;

		$name = ! empty( $_POST['webhook_name'] ) ? $_POST['webhook_name'] : sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) );
		$wpdb->update( $wpdb->posts, array( 'post_title' => $name ), array( 'ID' => $webhook_id ) );
	}

	/**
	 * Updated the Webhook status
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_status( $webhook ) {
		$status = ! empty( $_POST['webhook_status'] ) ? wc_clean( $_POST['webhook_status'] ) : '';

		$webhook->update_status( $status );
	}

	/**
	 * Updated the Webhook delivery URL
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
	 * Updated the Webhook secret
	 *
	 * @param WC_Webhook $webhook
	 */
	private function update_secret( $webhook ) {
		$secret = ! empty( $_POST['webhook_secret'] ) ? $_POST['webhook_secret'] : get_user_meta( get_current_user_id(), 'woocommerce_api_consumer_secret', true );

		$webhook->set_secret( $secret );
	}

	/**
	 * Updated the Webhook topic
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
	 * Save method
	 */
	private function save() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
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

		// Ping the webhook at the first time that is activated
		$peding_delivery = get_post_meta( $webhook->id, '_webhook_pending_delivery', true );
		if ( isset( $_POST['webhook_status'] ) && 'active' === $_POST['webhook_status'] && $peding_delivery ) {
			$webhook->deliver_ping();
		}

		do_action( 'woocommerce_webhook_options_save', $webhook->id );

		delete_transient( 'woocommerce_webhook_ids' );

		// Redirect to webhook edit page to avoid settings save actions
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks&edit-webhook=' . $webhook->id . '&updated=1' ) );
		exit();
	}

	/**
	 * Create Webhook
	 */
	private function create() {
		if ( ! current_user_can( 'publish_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to create Webhooks!', 'woocommerce' ) );
		}

		$webhook_id = wp_insert_post( array(
			'post_type'     => 'shop_webhook',
			'post_status'   => 'pending',
			'ping_status'   => 'closed',
			'post_author'   => get_current_user_id(),
			'post_password' => strlen( ( $password = uniqid( 'webhook_' ) ) ) > 20 ? substr( $password, 0, 20 ) : $password,
			'post_title'    => sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) ),
			'comment_status' => 'open'
		) );

		if ( is_wp_error( $webhook_id ) ) {
			wp_die( $webhook_id->get_error_messages() );
		}

		update_post_meta( $webhook_id, '_webhook_pending_delivery', true );

		delete_transient( 'woocommerce_webhook_ids' );

		// Redirect to edit page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks&edit-webhook=' . $webhook_id . '&created=1' ) );
		exit();
	}

	/**
	 * Bulk trash/delete
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

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks' . $status . '&' . $type . '=' . $qty ) );
		exit();
	}

	/**
	 * Bulk untrash
	 *
	 * @param array $webhooks
	 */
	private function bulk_untrash( $webhooks ) {
		foreach ( $webhooks as $webhook_id ) {
			wp_untrash_post( $webhook_id );
		}

		$qty = count( $webhooks );

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks&status=trash&untrashed=' . $qty ) );
		exit();
	}

	/**
	 * Webhook bulk actions
	 */
	private function bulk_actions() {
		if ( ! current_user_can( 'edit_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to edit Webhooks!', 'woocommerce' ) );
		}

		$webhooks = array_map( 'absint', (array) $_GET['webhook'] );

		delete_transient( 'woocommerce_webhook_ids' );

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
	 * Empty Trash
	 */
	public function empty_trash() {
		if ( ! current_user_can( 'delete_shop_webhooks' ) ) {
			wp_die( __( 'You don\'t have permissions to delete Webhooks!', 'woocommerce' ) );
		}

		$webhooks = get_posts( array(
			'post_type'           => 'shop_webhook',
			'ignore_sticky_posts' => true,
			'nopaging'            => true,
			'post_status'         => 'trash',
			'fields'              => 'ids'
		) );

		foreach ( $webhooks as $webhook_id ) {
			wp_delete_post( $webhook_id, true );
		}

		$qty = count( $webhooks );

		// Redirect to webhooks page
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks&deleted=' . $qty ) );
		exit();
	}

	/**
	 * Webhooks admin actions
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

			// Bulk actions
			if ( isset( $_GET['empty_trash'] ) ) {
				$this->empty_trash();
			}
		}
	}
}

new WC_Admin_Webhooks();

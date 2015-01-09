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
		// Save webhooks
		add_action( 'admin_init', array( $this, 'save' ) );
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
			list( $resource, $event ) = explode( '.', wc_clean( $_POST['webhook_topic'] ) );

			if ( 'action' === $resource ) {
				$event = ! empty( $_POST['webhook_action_event'] ) ? wc_clean( $_POST['webhook_action_event'] ) : '';
			} else if ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) && ! empty( $_POST['webhook_custom_topic'] ) ) {
				list( $resource, $event ) = explode( '.', wc_clean( $_POST['webhook_custom_topic'] ) );
			}

			$topic = $resource . '.' . $event;

			if ( wc_is_webhook_valid_topic( $topic ) ) {
				$webhook->set_topic( $topic );
			}
		}
	}

	/**
	 * Set Webhook post data.
	 *
	 * @param int $webhook_id
	 */
	private function set_post_data( $webhook_id ) {
		global $wpdb;

		$password = uniqid( 'webhook_' );
		$password = strlen( $password ) > 20 ? substr( $password, 0, 20 ) : $password;

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_password'  => $password,
				'ping_status'    => 'closed',
				'comment_status' => 'open'
			),
			array( 'ID' => $webhook_id )
		);
	}

	/**
	 * Save method
	 */
	public function save() {
		if ( isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['tab'] ) && 'webhooks' == $_GET['tab'] && isset( $_POST['save'] ) && isset( $_POST['webhook_id'] ) ) {

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

			// Webhook Created
			if ( isset( $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'] ) {
				// Set Post data like ping status and password
				$this->set_post_data( $webhook->id );

				// Ping webhook
				$webhook->deliver_ping();
			}

			do_action( 'woocommerce_webhook_options_save', $webhook->id );

			// Redirect to webhook edit page to avoid settings save actions
			wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=webhooks&edit-webhook=' . $webhook->id . '&updated=1' ) );
			exit();
		}
	}
}

new WC_Admin_Webhooks();

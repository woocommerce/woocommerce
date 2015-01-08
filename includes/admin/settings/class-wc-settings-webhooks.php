<?php
/**
 * WooCommerce Webhooks Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Webhooks' ) ) :

/**
 * WC_Settings_Webhooks
 */
class WC_Settings_Webhooks extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id    = 'webhooks';
		$this->label = __( 'Webhooks', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );

		$this->notices();
	}

	/**
	 * Form method
	 *
	 * @param  string $method
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		if ( isset( $_GET['edit-webhook'] ) ) {
			return 'post';
		}

		return 'get';
	}

	/**
	 * Notices.
	 */
	private function notices() {
		if ( isset( $_GET['trashed'] ) ) {
			$trashed = absint( $_GET['trashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook moved to the Trash.', '%d webhooks moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) );
		}

		if ( isset( $_GET['untrashed'] ) ) {
			$untrashed = absint( $_GET['untrashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook restored from the Trash.', '%d webhooks restored from the Trash.', $untrashed, 'woocommerce' ), $untrashed ) );
		}

		if ( isset( $_GET['deleted'] ) ) {
			$deleted = absint( $_GET['deleted'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook permanently deleted.', '%d webhooks permanently deleted.', $deleted, 'woocommerce' ), $deleted ) );
		}
	}

	/**
	 * Table list output
	 */
	private function table_list_output() {
		$webhooks_table_list = new WC_Admin_Webhooks_Table_List();
		$webhooks_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="webhooks" />';

		$webhooks_table_list->views();
		$webhooks_table_list->display();
	}

	/**
	 * Edit webhook output
	 */
	private function edit_output() {
		$webhook_id = absint( $_GET['edit-webhook'] );
		$webhook    = new WC_Webhook( $webhook_id );

		include_once( 'views/html-webhooks-edit.php' );
	}

	/**
	 * Logs output
	 *
	 * @param  WC_Webhook $webhook
	 */
	private function logs_output( $webhook ) {
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;
		$args    = array(
			'post_id' => $webhook->id,
			'status'  => 'approve',
			'type'    => 'webhook_delivery',
			'number'  => 10
		);

		if ( 1 < $current ) {
			$args['offset'] = ( $current - 1 ) * 10;
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$logs = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		if ( $logs ) {
			include_once( 'views/html-webhook-logs.php' );
		} else {
			echo '<p>' . __( 'This Webhook has no log yet.', 'woocommerce' ) . '</p>';
		}
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['edit-webhook'] ) ) {
			$this->edit_output();
		} else {
			$this->table_list_output();
		}
	}

	/**
	 * Get the webhook topic data
	 *
	 * @return array
	 */
	private function get_topic_data( $webhook ) {
		$topic    = $webhook->get_topic();
		$event    = '';
		$resource = '';

		if ( $topic ) {
			list( $resource, $event ) = explode( '.', $topic );

			if ( 'action' === $resource ) {
				$topic = 'action';
			} else if ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) ) {
				$topic = 'custom';
			}
		}

		return array(
			'topic'    => $topic,
			'event'    => $event,
			'resource' => $resource
		);
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
	 * Get the logs navigation.
	 *
	 * @param  int $total
	 *
	 * @return string
	 */
	private function get_logs_navigation( $total, $webhook ) {
		$pages   = ceil( $total / 10 );
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;

		$html = '<div class="webhook-logs-navigation">';

			$html .= '<p class="info" style="float: left;"><strong>';
			$html .= sprintf( '%s &ndash; Page %d of %d', _n( '1 item', sprintf( '%d items', $total ), $total, 'woocommerce' ), $current, $pages );
			$html .= '</strong></p>';

			if ( 1 < $pages ) {
				$html .= '<p class="tools" style="float: right;">';
					if ( 1 == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</button> ';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current - 1 ) ) . '#webhook-logs">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</a> ';
					}

					if ( $pages == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</button>';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current + 1 ) ) . '#webhook-logs">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</a>';
					}
				$html .= '</p>';
			}

		$html .= '<div class="clear"></div></div>';

		return $html;
	}
}

endif;

return new WC_Settings_Webhooks();

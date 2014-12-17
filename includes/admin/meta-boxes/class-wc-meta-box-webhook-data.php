<?php
/**
 * Webhook Data
 *
 * Display the webhook data meta box.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Meta Boxes
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Webhook_Data Class
 */
class WC_Meta_Box_Webhook_Data {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		$webhook = new WC_Webhook( $post->ID );
		?>
		<style>
			#post-body-content { display: none; }
		</style>

		<div id="webhook-options" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
					// Name
					woocommerce_wp_text_input( array(
						'id'          => 'name',
						'label'       => __( 'Name', 'woocommerce' ),
						'description' => sprintf( __( 'Friendly name for identifying this webhook, defaults to Webhook created on %s.', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) ),
						'desc_tip'    => true,
						'value'       => ( 'auto-draft' == $post->post_status ) ? '' : $webhook->get_name()
					) );
				?>
			</div>

			<div class="options_group">
				<?php

					// Status
					woocommerce_wp_select( array(
						'id'          => 'status',
						'label'       => __( 'Status', 'woocommerce' ),
						'description' => __( 'The options are "Active" (delivers payload), "Paused" (does not deliver), or "Disabled" (does not deliver due delivery failures).', 'woocommerce' ),
						'desc_tip'    => true,
						'options'     => wc_get_webhook_statuses(),
						'value'       => $webhook->get_status()
					) );
				?>
			</div>

			<div class="options_group">
				<?php
					// Topic
					$topic = $webhook->get_topic();
					$event = '';

					if ( $topic ) {
						list( $resource, $event ) = explode( '.', $topic );

						if ( 'action' === $resource ) {
							$topic = 'action';
						} else if ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) ) {
							$topic = 'custom';
						}
					}

					woocommerce_wp_select( array(
						'id'          => 'topic',
						'label'       => __( 'Topic', 'woocommerce' ),
						'description' => __( 'Select when the webhook will fire.', 'woocommerce' ),
						'desc_tip'    => true,
						'options'     => array(
							''                 => __( 'Select an option&hellip;', 'woocommerce' ),
							'coupon.created'   => __( 'Coupon Created', 'woocommerce' ),
							'coupon.updated'   => __( 'Coupon Updated', 'woocommerce' ),
							'coupon.deleted'   => __( 'Coupon Deleted', 'woocommerce' ),
							'customer.created' => __( 'Customer Created', 'woocommerce' ),
							'customer.updated' => __( 'Customer Updated', 'woocommerce' ),
							'customer.deleted' => __( 'Customer Deleted', 'woocommerce' ),
							'order.created'    => __( 'Order Created', 'woocommerce' ),
							'order.updated'    => __( 'Order Updated', 'woocommerce' ),
							'order.deleted'    => __( 'Order Deleted', 'woocommerce' ),
							'product.created'  => __( 'Product Created', 'woocommerce' ),
							'product.updated'  => __( 'Product Updated', 'woocommerce' ),
							'product.deleted'  => __( 'Product Deleted', 'woocommerce' ),
							'action'           => __( 'Action', 'woocommerce' ),
							'custom'           => __( 'Custom', 'woocommerce' )
						),
						'value'       => $topic
					) );

					// Action
					woocommerce_wp_text_input( array(
						'id'          => 'action_event',
						'label'       => __( 'Action Event', 'woocommerce' ),
						'description' => __( 'Enter the Action that will trigger this webhook.', 'woocommerce' ),
						'desc_tip'    => true,
						'value'       => $event
					) );

					// Custom Topic
					woocommerce_wp_text_input( array(
						'id'          => 'custom_topic',
						'label'       => __( 'Custom Topic', 'woocommerce' ),
						'description' => __( 'Enter the Custom Topic that will trigger this webhook.', 'woocommerce' ),
						'desc_tip'    => true,
						'value'       => $webhook->get_topic()
					) );
				?>
			</div>

			<div class="options_group">
				<?php
					// Delivery url
					woocommerce_wp_text_input( array(
						'id'          => 'delivery_url',
						'label'       => __( 'Delivery URL', 'woocommerce' ),
						'description' => __( 'URL where the webhook payload is delivered.', 'woocommerce' ),
						'data_type'   => 'url',
						'desc_tip'    => true,
						'value'       => $webhook->get_delivery_url()
					) );
				?>
			</div>

			<div class="options_group">
				<?php
					// Secret
					woocommerce_wp_text_input( array(
						'id'          => 'secret',
						'label'       => __( 'Secret', 'woocommerce' ),
						'description' => __( 'The a Secret Key is used to generate a hash of the delivered webhook and provided in the request headers. This will default to the current API user\'s consumer secret if not provided.', 'woocommerce' ),
						'desc_tip'    => true,
						'value'       => $webhook->get_secret()
					) );
				?>
			</div>

			<?php do_action( 'woocommerce_webhook_options' ); ?>

			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Updated the Webhook name
	 *
	 * @param int $post_id
	 */
	private static function update_name( $post_id ) {
		global $wpdb;

		$name = ! empty( $_POST['name'] ) ? $_POST['name'] : sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) );
		$wpdb->update( $wpdb->posts, array( 'post_title' => $name ), array( 'ID' => $post_id ) );
	}

	/**
	 * Updated the Webhook status
	 *
	 * @param WC_Webhook $webhook
	 */
	private static function update_status( $webhook ) {
		$status = ! empty( $_POST['status'] ) ? wc_clean( $_POST['status'] ) : '';

		$webhook->update_status( $status );
	}

	/**
	 * Updated the Webhook delivery URL
	 *
	 * @param WC_Webhook $webhook
	 */
	private static function update_delivery_url( $webhook ) {
		$delivery_url = ! empty( $_POST['delivery_url'] ) ? $_POST['delivery_url'] : '';

		$webhook->set_delivery_url( $delivery_url );
	}

	/**
	 * Updated the Webhook secret
	 *
	 * @param WC_Webhook $webhook
	 */
	private static function update_secret( $webhook ) {
		$secret = ! empty( $_POST['secret'] ) ? $_POST['secret'] : get_user_meta( get_current_user_id(), 'woocommerce_api_consumer_secret', true );

		$webhook->set_secret( $secret );
	}

	/**
	 * Updated the Webhook topic
	 *
	 * @param WC_Webhook $webhook
	 */
	private static function update_topic( $webhook ) {
		if ( ! empty( $_POST['topic'] ) ) {
			list( $resource, $event ) = explode( '.', wc_clean( $_POST['topic'] ) );

			if ( 'action' === $resource ) {
				$event = ! empty( $_POST['action_event'] ) ? wc_clean( $_POST['action_event'] ) : '';
			} else if ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) && ! empty( $_POST['custom_topic'] ) ) {
				list( $resource, $event ) = explode( '.', wc_clean( $_POST['custom_topic'] ) );
			}

			$webhook->set_topic( $resource . '.' . $event );
		}
	}

	/**
	 * Set Webhook post data.
	 *
	 * @param int $post_id
	 */
	private static function set_post_data( $post_id ) {
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
			array( 'ID' => $post_id )
		);
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id ) {
		$webhook = new WC_Webhook( $post_id );

		// Name
		self::update_name( $post_id );

		// Status
		self::update_status( $webhook );

		// Delivery URL
		self::update_delivery_url( $webhook );

		// Secret
		self::update_secret( $webhook );

		// Topic
		self::update_topic( $webhook );

		// Webhook Created
		if ( isset( $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'] ) {
			// Set Post data like ping status and password
			self::set_post_data( $post_id );

			// Ping webhook
			$webhook->deliver_ping();
		}

		do_action( 'woocommerce_webhook_options_save', $post_id );
	}
}

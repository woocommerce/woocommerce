<?php
/**
 * Webhook Data Store
 *
 * @version  3.2.0
 * @package  WooCommerce/Classes/Data_Store
 * @category Class
 * @author   Automattic
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook data store class.
 */
class WC_Webhook_Data_Store extends WC_Data_Store_WP implements WC_Webhook_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Meta type. Webhooks does not have any meta data.
	 *
	 * @var string
	 */
	protected $meta_type = null;

	/**
	 * Create a new webhook in the database.
	 *
	 * @since  3.2.0
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @throws Exception
	 */
	public function create( &$webhook ) {
		global $wpdb;

		if ( ! is_null( $webhook->get_date_created( 'edit' ) ) ) {
			$date_created     = $webhook->get_date_created()->date( 'Y-m-d H:i:s' );
			$date_created_gmt = gmdate( 'Y-m-d H:i:s', $webhook->get_date_created()->getTimestamp() );
		} else {
			$date_created     = current_time( 'mysql' );
			$date_created_gmt = current_time( 'mysql', 1 );
			$webhook->set_date_created( $date_created );
		}

		$data = array(
			'status'            => $webhook->get_status( 'edit' ),
			'name'              => $webhook->get_name( 'edit' ),
			'user_id'           => $webhook->get_user_id( 'edit' ),
			'delivery_url'      => $webhook->get_delivery_url( 'edit' ),
			'secret'            => $webhook->get_secret( 'edit' ),
			'topic'             => $webhook->get_topic( 'edit' ),
			'date_created'      => $date_created,
			'date_created_gmt'  => $date_created_gmt,
			'api_version'       => $this->get_api_version_number( $webhook->get_api_version( 'edit' ) ),
			'failure_count'     => $webhook->get_failure_count( 'edit' ),
			'pending_delivery'  => $webhook->get_pending_delivery( 'edit' ),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_webhooks', $data );
		$webhook_id = $wpdb->insert_id;
		$webhook->set_id( $webhook_id );
		$webhook->apply_changes();

		do_action( 'woocommerce_new_webhook', $webhook_id );
	}

	/**
	 * Read a webhook from the database.
	 *
	 * @since  3.2.0
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @throws Exception
	 */
	public function read( &$webhook ) {
		global $wpdb;

		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT webhook_id, status, name, user_id, delivery_url, secret, topic, date_created, date_modified, api_version, failure_count, pending_delivery FROM {$wpdb->prefix}woocommerce_webhooks WHERE webhook_id = %d LIMIT 1;", $webhook->get_id() ) ) ) {
			$webhook->set_props( array(
				'id'               => $data->webhook_id,
				'status'           => $data->status,
				'name'             => $data->name,
				'user_id'          => $data->user_id,
				'delivery_url'     => $data->delivery_url,
				'secret'           => $data->secret,
				'topic'            => $data->topic,
				'date_created'     => $data->date_created,
				'date_modified'    => $data->date_modified,
				'api_version'      => $data->api_version,
				'failure_count'    => $data->failure_count,
				'pending_delivery' => $data->pending_delivery,
			) );
			$webhook->set_object_read( true );
			do_action( 'woocommerce_webhook_loaded', $webhook );
		} else {
			throw new Exception( __( 'Invalid webhook.', 'woocommerce' ) );
		}
	}

	/**
	 * Update a webhook.
	 *
	 * @since  3.2.0
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @throws Exception
	 */
	public function update( &$webhook ) {
		global $wpdb;

		if ( ! is_null( $webhook->get_date_created( 'edit' ) ) ) {
			$date_modified     = $webhook->get_date_modified()->date( 'Y-m-d H:i:s' );
			$date_modified_gmt = gmdate( 'Y-m-d H:i:s', $webhook->get_date_modified()->getTimestamp() );
		} else {
			$date_modified     = current_time( 'mysql' );
			$date_modified_gmt = current_time( 'mysql', 1 );
			$webhook->set_date_modified( $date_modified );
		}

		$data = array(
			'status'            => $webhook->get_status( 'edit' ),
			'name'              => $webhook->get_name( 'edit' ),
			'user_id'           => $webhook->get_user_id( 'edit' ),
			'delivery_url'      => $webhook->get_delivery_url( 'edit' ),
			'secret'            => $webhook->get_secret( 'edit' ),
			'topic'             => $webhook->get_topic( 'edit' ),
			'date_modified'     => $date_modified,
			'date_modified_gmt' => $date_modified_gmt,
			'api_version'       => $this->get_api_version_number( $webhook->get_api_version( 'edit' ) ),
			'failure_count'     => $webhook->get_failure_count( 'edit' ),
			'pending_delivery'  => $webhook->get_pending_delivery( 'edit' ),
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_webhooks',
			$data,
			array(
				'webhook_id' => $webhook->get_id( 'edit' ),
			)
		);

		$webhook->apply_changes();

		do_action( 'woocommerce_webhook_updated', $webhook->get_id() );
	}

	/**
	 * Remove a webhook from the database.
	 *
	 * @since 3.2.0
	 * @param WC_Webhook $webhook      Webhook instance.
	 * @param bool       $force_delete Skip trash bin forcing to delete.
	 */
	public function delete( &$webhook, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'woocommerce_webhooks',
			array(
				'webhook_id' => $webhook->get_id(),
			),
			array( '%d' )
		);

		do_action( 'woocommerce_webhook_deleted', $webhook->get_id(), $webhook );
	}

	/**
	 * Get API version number.
	 *
	 * @since  3.2.0
	 * @param  string $api_version REST API version.
	 * @return int
	 */
	public function get_api_version_number( $api_version ) {
		return 'legacy_v3' === $api_version ? -1 : intval( substr( $api_version, -1 ) );
	}
}

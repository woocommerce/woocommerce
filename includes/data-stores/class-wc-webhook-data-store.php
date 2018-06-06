<?php
/**
 * Webhook Data Store
 *
 * @version  3.3.0
 * @package  WooCommerce/Classes/Data_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook data store class.
 */
class WC_Webhook_Data_Store implements WC_Webhook_Data_Store_Interface {

	/**
	 * Create a new webhook in the database.
	 *
	 * @since 3.3.0
	 * @param WC_Webhook $webhook Webhook instance.
	 */
	public function create( &$webhook ) {
		global $wpdb;

		$changes = $webhook->get_changes();
		if ( isset( $changes['date_created'] ) ) {
			$date_created     = $webhook->get_date_created()->date( 'Y-m-d H:i:s' );
			$date_created_gmt = gmdate( 'Y-m-d H:i:s', $webhook->get_date_created()->getTimestamp() );
		} else {
			$date_created     = current_time( 'mysql' );
			$date_created_gmt = current_time( 'mysql', 1 );
			$webhook->set_date_created( $date_created );
		}

		// Pending delivery by default if not set while creating a new webhook.
		if ( ! isset( $changes['pending_delivery'] ) ) {
			$webhook->set_pending_delivery( true );
		}

		$data = array(
			'status'           => $webhook->get_status( 'edit' ),
			'name'             => $webhook->get_name( 'edit' ),
			'user_id'          => $webhook->get_user_id( 'edit' ),
			'delivery_url'     => $webhook->get_delivery_url( 'edit' ),
			'secret'           => $webhook->get_secret( 'edit' ),
			'topic'            => $webhook->get_topic( 'edit' ),
			'date_created'     => $date_created,
			'date_created_gmt' => $date_created_gmt,
			'api_version'      => $this->get_api_version_number( $webhook->get_api_version( 'edit' ) ),
			'failure_count'    => $webhook->get_failure_count( 'edit' ),
			'pending_delivery' => $webhook->get_pending_delivery( 'edit' ),
		);

		$wpdb->insert( $wpdb->prefix . 'wc_webhooks', $data ); // WPCS: DB call ok.

		$webhook_id = $wpdb->insert_id;
		$webhook->set_id( $webhook_id );
		$webhook->apply_changes();

		delete_transient( 'woocommerce_webhook_ids' );
		WC_Cache_Helper::incr_cache_prefix( 'webhooks' );
		do_action( 'woocommerce_new_webhook', $webhook_id );
	}

	/**
	 * Read a webhook from the database.
	 *
	 * @since  3.3.0
	 * @param  WC_Webhook $webhook Webhook instance.
	 * @throws Exception When webhook is invalid.
	 */
	public function read( &$webhook ) {
		global $wpdb;

		$data = wp_cache_get( $webhook->get_id(), 'webhooks' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT webhook_id, status, name, user_id, delivery_url, secret, topic, date_created, date_modified, api_version, failure_count, pending_delivery FROM {$wpdb->prefix}wc_webhooks WHERE webhook_id = %d LIMIT 1;", $webhook->get_id() ), ARRAY_A ); // WPCS: cache ok, DB call ok.

			wp_cache_add( $webhook->get_id(), $data, 'webhooks' );
		}

		if ( is_array( $data ) ) {
			$webhook->set_props(
				array(
					'id'               => $data['webhook_id'],
					'status'           => $data['status'],
					'name'             => $data['name'],
					'user_id'          => $data['user_id'],
					'delivery_url'     => $data['delivery_url'],
					'secret'           => $data['secret'],
					'topic'            => $data['topic'],
					'date_created'     => '0000-00-00 00:00:00' === $data['date_created'] ? null : $data['date_created'],
					'date_modified'    => '0000-00-00 00:00:00' === $data['date_modified'] ? null : $data['date_modified'],
					'api_version'      => $data['api_version'],
					'failure_count'    => $data['failure_count'],
					'pending_delivery' => $data['pending_delivery'],
				)
			);
			$webhook->set_object_read( true );

			do_action( 'woocommerce_webhook_loaded', $webhook );
		} else {
			throw new Exception( __( 'Invalid webhook.', 'woocommerce' ) );
		}
	}

	/**
	 * Update a webhook.
	 *
	 * @since 3.3.0
	 * @param WC_Webhook $webhook Webhook instance.
	 */
	public function update( &$webhook ) {
		global $wpdb;

		$changes = $webhook->get_changes();
		$trigger = isset( $changes['delivery_url'] );

		if ( isset( $changes['date_modified'] ) ) {
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
			$wpdb->prefix . 'wc_webhooks',
			$data,
			array(
				'webhook_id' => $webhook->get_id( 'edit' ),
			)
		); // WPCS: DB call ok.

		$webhook->apply_changes();

		wp_cache_delete( $webhook->get_id(), 'webhooks' );
		WC_Cache_Helper::incr_cache_prefix( 'webhooks' );

		if ( 'active' === $webhook->get_status() && ( $trigger || $webhook->get_pending_delivery() ) ) {
			$webhook->deliver_ping();
		}

		do_action( 'woocommerce_webhook_updated', $webhook->get_id() );
	}

	/**
	 * Remove a webhook from the database.
	 *
	 * @since 3.3.0
	 * @param WC_Webhook $webhook      Webhook instance.
	 * @param bool       $force_delete Skip trash bin forcing to delete.
	 */
	public function delete( &$webhook, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'wc_webhooks',
			array(
				'webhook_id' => $webhook->get_id(),
			),
			array( '%d' )
		); // WPCS: cache ok, DB call ok.

		delete_transient( 'woocommerce_webhook_ids' );
		WC_Cache_Helper::incr_cache_prefix( 'webhooks' );
		do_action( 'woocommerce_webhook_deleted', $webhook->get_id(), $webhook );
	}

	/**
	 * Get API version number.
	 *
	 * @since  3.3.0
	 * @param  string $api_version REST API version.
	 * @return int
	 */
	public function get_api_version_number( $api_version ) {
		return 'legacy_v3' === $api_version ? -1 : intval( substr( $api_version, -1 ) );
	}

	/**
	 * Get all webhooks IDs.
	 *
	 * @since  3.3.0
	 * @return int[]
	 */
	public function get_webhooks_ids() {
		global $wpdb;

		$ids = get_transient( 'woocommerce_webhook_ids' );

		if ( false === $ids ) {
			$results = $wpdb->get_results( "SELECT webhook_id FROM {$wpdb->prefix}wc_webhooks" ); // WPCS: cache ok, DB call ok.
			$ids     = array_map( 'intval', wp_list_pluck( $results, 'webhook_id' ) );

			set_transient( 'woocommerce_webhook_ids', $ids );
		}

		return $ids;
	}

	/**
	 * Search webhooks.
	 *
	 * @param  array $args Search arguments.
	 * @return array
	 */
	public function search_webhooks( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args, array(
				'limit'   => 10,
				'offset'  => 0,
				'order'   => 'DESC',
				'orderby' => 'id',
			)
		);

		// Map post statuses.
		$statuses = array(
			'publish' => 'active',
			'draft'   => 'paused',
			'pending' => 'disabled',
		);

		// Map orderby to support a few post keys.
		$orderby_mapping = array(
			'ID'            => 'webhook_id',
			'id'            => 'webhook_id',
			'name'          => 'name',
			'title'         => 'name',
			'post_title'    => 'name',
			'post_name'     => 'name',
			'date_created'  => 'date_created_gmt',
			'date'          => 'date_created_gmt',
			'post_date'     => 'date_created_gmt',
			'date_modified' => 'date_modified_gmt',
			'modified'      => 'date_modified_gmt',
			'post_modified' => 'date_modified_gmt',
		);
		$orderby         = isset( $orderby_mapping[ $args['orderby'] ] ) ? $orderby_mapping[ $args['orderby'] ] : 'webhook_id';

		$limit         = -1 < $args['limit'] ? sprintf( 'LIMIT %d', $args['limit'] ) : '';
		$offset        = 0 < $args['offset'] ? sprintf( 'OFFSET %d', $args['offset'] ) : '';
		$status        = ! empty( $args['status'] ) ? "AND `status` = '" . sanitize_key( isset( $statuses[ $args['status'] ] ) ? $statuses[ $args['status'] ] : $args['status'] ) . "'" : '';
		$search        = ! empty( $args['search'] ) ? "AND `name` LIKE '%" . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . "%'" : '';
		$include       = '';
		$exclude       = '';
		$date_created  = '';
		$date_modified = '';

		if ( ! empty( $args['include'] ) ) {
			$args['include'] = implode( ',', wp_parse_id_list( $args['include'] ) );
			$include         = 'AND webhook_id IN (' . $args['include'] . ')';
		}

		if ( ! empty( $args['exclude'] ) ) {
			$args['exclude'] = implode( ',', wp_parse_id_list( $args['exclude'] ) );
			$exclude         = 'AND webhook_id NOT IN (' . $args['exclude'] . ')';
		}

		if ( ! empty( $args['after'] ) || ! empty( $args['before'] ) ) {
			$args['after']  = empty( $args['after'] ) ? '0000-00-00' : $args['after'];
			$args['before'] = empty( $args['before'] ) ? current_time( 'mysql', 1 ) : $args['before'];

			$date_created = "AND `date_created_gmt` BETWEEN STR_TO_DATE('" . $args['after'] . "', '%Y-%m-%d %H:%i:%s') and STR_TO_DATE('" . $args['before'] . "', '%Y-%m-%d %H:%i:%s')";
		}

		if ( ! empty( $args['modified_after'] ) || ! empty( $args['modified_before'] ) ) {
			$args['modified_after']  = empty( $args['modified_after'] ) ? '0000-00-00' : $args['modified_after'];
			$args['modified_before'] = empty( $args['modified_before'] ) ? current_time( 'mysql', 1 ) : $args['modified_before'];

			$date_modified = "AND `date_modified_gmt` BETWEEN STR_TO_DATE('" . $args['modified_after'] . "', '%Y-%m-%d %H:%i:%s') and STR_TO_DATE('" . $args['modified_before'] . "', '%Y-%m-%d %H:%i:%s')";
		}

		$order = "ORDER BY {$orderby} " . strtoupper( sanitize_key( $args['order'] ) );

		// Check for cache.
		$cache_key = WC_Cache_Helper::get_cache_prefix( 'webhooks' ) . 'search_webhooks' . md5( implode( ',', $args ) );
		$ids       = wp_cache_get( $cache_key, 'webhook_search_results' );

		if ( false !== $ids ) {
			return $ids;
		}

		$query = trim(
			"SELECT webhook_id
			FROM {$wpdb->prefix}wc_webhooks
			WHERE 1=1
			{$status}
			{$search}
			{$include}
			{$exclude}
			{$date_created}
			{$date_modified}
			{$order}
			{$limit}
			{$offset}"
		);

		$results = $wpdb->get_results( $query ); // WPCS: cache ok, DB call ok, unprepared SQL ok.

		$ids = wp_list_pluck( $results, 'webhook_id' );
		wp_cache_set( $cache_key, $ids, 'webhook_search_results' );

		return $ids;
	}

	/**
	 * Get total webhook counts by status.
	 *
	 * @return array
	 */
	public function get_count_webhooks_by_status() {
		$statuses = array_keys( wc_get_webhook_statuses() );
		$counts   = array();

		foreach ( $statuses as $status ) {
			$count = count(
				$this->search_webhooks(
					array(
						'limit'  => -1,
						'status' => $status,
					)
				)
			);

			$counts[ $status ] = $count;
		}

		return $counts;
	}
}

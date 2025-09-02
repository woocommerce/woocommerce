<?php
/**
 * StockNotificationsDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores\StockNotifications;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * The Stock Notifications Data Store.
 */
class StockNotificationsDataStore implements \WC_Object_Data_Store_Interface {

	/**
	 * The database util object to use.
	 *
	 * @var DatabaseUtil
	 */
	protected DatabaseUtil $database_util;

	/**
	 * Handles custom metadata in the wc_stock_notificationmeta table.
	 *
	 * @var StockNotificationsMetaDataStore
	 */
	protected StockNotificationsMetaDataStore $data_store_meta;

	/**
	 * Initialize.
	 *
	 * @internal
	 *
	 * @param StockNotificationsMetaDataStore $data_store_meta The data store meta instance to use.
	 * @param DatabaseUtil                    $database_util   The database util instance to use.
	 *
	 * @return void
	 */
	final public function init( StockNotificationsMetaDataStore $data_store_meta, DatabaseUtil $database_util ) {
		$this->data_store_meta = $data_store_meta;
		$this->database_util   = $database_util;
	}

	/**
	 * Get the stock notifications table name.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_stock_notifications';
	}

	/**
	 * Get the stock notifications meta table name.
	 *
	 * @return string
	 */
	public function get_meta_table_name(): string {
		return $this->data_store_meta->get_table_name();
	}

	/**
	 * Get the database schema.
	 *
	 * @return string
	 */
	public function get_database_schema(): string {

		if ( ! Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' ) ) {
			return '';
		}

		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$table_name       = $this->get_table_name();
		$meta_table_name  = $this->get_meta_table_name();
		$max_index_length = $this->database_util->get_max_index_length();

		$sql = "
CREATE TABLE $table_name (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	product_id bigint(20) unsigned NOT NULL,
	user_id bigint(20) unsigned NOT NULL,
	user_email varchar(100) NOT NULL,
	status varchar(20) NOT NULL DEFAULT 'pending',
	date_created_gmt datetime NULL,
	date_modified_gmt datetime NULL,
	date_confirmed_gmt datetime NULL,
	date_last_attempt_gmt datetime NULL,
	date_notified_gmt datetime NULL,
	date_cancelled_gmt datetime NULL,
	cancellation_source varchar(30) NULL,
	PRIMARY KEY  (id),
	KEY product_status_attempt (product_id, status, date_last_attempt_gmt, id),
	KEY user_lookup (user_id, product_id, status),
	KEY email_lookup (user_email, product_id, status)
) $collate;
CREATE TABLE $meta_table_name (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	notification_id bigint(20) unsigned NOT NULL,
	meta_key varchar(255) NULL,
	meta_value longtext NULL,
	PRIMARY KEY  (id),
	KEY notification_id (notification_id),
	KEY meta_key (meta_key($max_index_length))
) $collate;
		";

		return $sql;
	}

	/**
	 * Filter the raw meta data.
	 *
	 * This is required due to the use of the WC_Data::read_meta_data() method.
	 * It's a post-specific method that used to filter internal meta data.
	 * For custom tables, technically there is no internal meta data,
	 * so this method is a no-op.
	 *
	 * @param Notification $notification  The data object to filter.
	 * @param array        $raw_meta_data The raw meta data to filter.
	 * @return array
	 */
	public function filter_raw_meta_data( &$notification, $raw_meta_data ): array {
		return $raw_meta_data;
	}

	/**
	 * Get the internal meta keys.
	 *
	 * Required for the use of the WC_Data::is_internal_meta_key() method.
	 * It's a no-op for custom tables.
	 *
	 * @return array
	 */
	public function get_internal_meta_keys(): array {
		return array();
	}

	/**
	 * Create a new stock notification.
	 *
	 * @param Notification $notification The data object to create.
	 * @return int|\WP_Error The notification ID on success. WP_Error on failure.
	 */
	public function create( &$notification ) {
		global $wpdb;

		// Fill in created and modified dates.
		if ( ! $notification->get_date_created( 'edit' ) ) {
			$notification->set_date_created( time() );
		}
		if ( ! $notification->get_date_modified( 'edit' ) ) {
			$notification->set_date_modified( time() );
		}

		$insert = $wpdb->insert(
			$this->get_table_name(),
			array(
				'product_id'            => $notification->get_product_id( 'edit' ),
				'user_id'               => $notification->get_user_id( 'edit' ),
				'user_email'            => $notification->get_user_email( 'edit' ),
				'status'                => $notification->get_status( 'edit' ),
				'date_created_gmt'      => gmdate( 'Y-m-d H:i:s', $notification->get_date_created( 'edit' )->getTimestamp() ),
				'date_modified_gmt'     => gmdate( 'Y-m-d H:i:s', $notification->get_date_modified( 'edit' )->getTimestamp() ),
				'date_confirmed_gmt'    => $notification->get_date_confirmed( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_confirmed( 'edit' )->getTimestamp() ) : null,
				'date_last_attempt_gmt' => $notification->get_date_last_attempt( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_last_attempt( 'edit' )->getTimestamp() ) : null,
				'date_notified_gmt'     => $notification->get_date_notified( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_notified( 'edit' )->getTimestamp() ) : null,
				'date_cancelled_gmt'    => $notification->get_date_cancelled( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_cancelled( 'edit' )->getTimestamp() ) : null,
				'cancellation_source'   => $notification->get_cancellation_source( 'edit' ),
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $insert ) {
			return new \WP_Error( 'db_insert_error', 'Could not insert stock notification into the database.' );
		}

		$notification_id = (int) $wpdb->insert_id;
		$notification->set_id( $notification_id );
		$notification->save_meta_data();
		$notification->apply_changes();

		return $notification->get_id();
	}

	/**
	 * Read a stock notification.
	 *
	 * @param Notification $notification The data object to read.
	 *
	 * @throws \Exception If the stock notification is not found.
	 *
	 * @return void
	 */
	public function read( &$notification ) {
		global $wpdb;

		if ( 0 === $notification->get_id() ) {
			throw new \Exception( 'Invalid notification ID.' );
		}

		$data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$this->get_table_name(),
				$notification->get_id()
			)
		);

		if ( ! $data ) {
			throw new \Exception( 'Stock notification not found' );
		}

		$notification->set_props(
			array(
				'id'                  => $data->id,
				'product_id'          => $data->product_id,
				'user_id'             => $data->user_id,
				'user_email'          => $data->user_email,
				'status'              => $data->status,
				'date_created'        => wc_string_to_timestamp( $data->date_created_gmt ),
				'date_modified'       => wc_string_to_timestamp( $data->date_modified_gmt ),
				'date_confirmed'      => wc_string_to_timestamp( $data->date_confirmed_gmt ),
				'date_last_attempt'   => wc_string_to_timestamp( $data->date_last_attempt_gmt ),
				'date_notified'       => wc_string_to_timestamp( $data->date_notified_gmt ),
				'date_cancelled'      => wc_string_to_timestamp( $data->date_cancelled_gmt ),
				'cancellation_source' => $data->cancellation_source,
			)
		);

		$notification->read_meta_data();
		$notification->set_object_read( true );
	}

	/**
	 * Update a stock notification.
	 *
	 * @param Notification $notification The data object to update.
	 * @return int|\WP_Error The number of rows updated or WP_Error on failure.
	 */
	public function update( &$notification ) {
		global $wpdb;

		if ( 0 === $notification->get_id() ) {
			return new \WP_Error( 'invalid_stock_notification', 'Invalid notification ID.' );
		}

		$changes = $notification->get_changes();
		$result  = 0;

		if ( array_intersect( array( 'product_id', 'user_id', 'user_email', 'status', 'date_modified', 'date_confirmed', 'date_last_attempt', 'date_notified', 'date_cancelled', 'cancellation_source' ), array_keys( $changes ) ) ) {

			if ( ! array_key_exists( 'date_modified', $changes ) ) {
				$notification->set_date_modified( time() );
			}

			$result = $wpdb->update(
				$this->get_table_name(),
				array(
					'product_id'            => $notification->get_product_id( 'edit' ),
					'user_id'               => $notification->get_user_id( 'edit' ),
					'user_email'            => $notification->get_user_email( 'edit' ),
					'status'                => $notification->get_status( 'edit' ),
					'date_created_gmt'      => $notification->get_date_created( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_created( 'edit' )->getTimestamp() ) : null,
					'date_modified_gmt'     => gmdate( 'Y-m-d H:i:s', $notification->get_date_modified( 'edit' )->getTimestamp() ),
					'date_confirmed_gmt'    => $notification->get_date_confirmed( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_confirmed( 'edit' )->getTimestamp() ) : null,
					'date_last_attempt_gmt' => $notification->get_date_last_attempt( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_last_attempt( 'edit' )->getTimestamp() ) : null,
					'date_notified_gmt'     => $notification->get_date_notified( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_notified( 'edit' )->getTimestamp() ) : null,
					'date_cancelled_gmt'    => $notification->get_date_cancelled( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $notification->get_date_cancelled( 'edit' )->getTimestamp() ) : null,
					'cancellation_source'   => $notification->get_cancellation_source( 'edit' ),
				),
				array( 'id' => $notification->get_id() ),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new \WP_Error( 'db_update_error', 'Could not update stock notification in the database.' );
			}

			if ( 0 === $result ) {
				return new \WP_Error( 'db_update_error', 'Invalid notification ID.' );
			}
		}

		$notification->save_meta_data();

		if ( $changes ) {
			$notification->apply_changes();
		}

		return $result;
	}

	/**
	 * Delete a stock notification.
	 *
	 * @param Notification $notification The data object to delete.
	 * @param array        $args         Additional arguments.
	 * @return void
	 */
	public function delete( &$notification, $args = array() ) {
		global $wpdb;

		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $notification->get_id() ), array( '%d' ) );

		if ( $deleted > 0 ) {
			$this->data_store_meta->delete_by_notification_id( $notification->get_id() );
		}
	}

	/**
	 * Add meta.
	 *
	 * @param Notification $notification The data object to add.
	 * @param \stdClass    $meta         The meta object to add (containing ->key and ->value).
	 * @return int|false The meta ID or false if the meta was not added.
	 */
	public function add_meta( &$notification, $meta ) {
		$add_meta = $this->data_store_meta->add_meta( $notification, $meta );
		$this->after_meta_change( $notification );
		return $add_meta ? $add_meta : false;
	}

	/**
	 * Read meta.
	 *
	 * @param Notification $notification The data object to read.
	 * @return array
	 */
	public function read_meta( &$notification ): array {
		$raw_meta_data = $this->data_store_meta->read_meta( $notification );
		return $this->filter_raw_meta_data( $notification, $raw_meta_data );
	}

	/**
	 * Update meta.
	 *
	 * @param Notification $notification The data object to update.
	 * @param \stdClass    $meta         The meta object to update (containing ->id, ->key and ->value).
	 * @return bool
	 */
	public function update_meta( &$notification, $meta ): bool {
		$update_meta = $this->data_store_meta->update_meta( $notification, $meta );
		$this->after_meta_change( $notification );
		return $update_meta;
	}

	/**
	 * Delete meta.
	 *
	 * @param Notification $notification The data object to delete.
	 * @param \stdClass    $meta         The meta object to delete (containing at least ->id).
	 * @return bool
	 */
	public function delete_meta( &$notification, $meta ): bool {
		$delete_meta = $this->data_store_meta->delete_meta( $notification, $meta );

		$this->after_meta_change( $notification );
		return $delete_meta;
	}

	/**
	 * Perform after meta change operations.
	 *
	 * @param Notification $notification The notification object.
	 * @return bool True if changes were applied, false otherwise.
	 */
	private function after_meta_change( &$notification ): bool {

		$current_time      = time();
		$current_date_time = new \WC_DateTime( "@$current_time", new \DateTimeZone( 'UTC' ) );

		$should_save =
			$notification->get_id() > 0
			&& $notification->get_date_modified( 'edit' ) < $current_date_time
			&& empty( $notification->get_changes() );

		if ( $should_save ) {
			$notification->set_date_modified( $current_time );
			$saved = $notification->save();
			return ! is_wp_error( $saved );
		}

		return false;
	}

	/**
	 * Query the stock notifications.
	 *
	 * @param array $args The arguments.
	 * @return array<int>|array<Notification>|int An array of notifications or the number of notifications.
	 */
	public function query( array $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'             => '',
				'product_id'         => array(),
				'user_id'            => 0,
				'user_email'         => '',
				'last_attempt_limit' => 0,
				'start_date'         => 0,
				'end_date'           => 0,
				'limit'              => -1,
				'offset'             => 0,
				'order_by'           => array( 'id' => 'ASC' ),
				'return'             => 'ids', // i.e. 'count', 'ids', 'objects'.
			)
		);

		$table  = $this->get_table_name();
		$select = 'id';
		if ( 'count' === $args['return'] ) {
			$select = 'COUNT(id)';
		} elseif ( 'objects' === $args['return'] ) {
			$select = '*';
		}

		// WHERE clauses.
		$where        = array();
		$where_values = array();

		if ( $args['status'] ) {
			$where[]        = 'status = %s';
			$where_values[] = esc_sql( $args['status'] );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$product_ids  = array_map( 'absint', (array) $args['product_id'] );
			$where[]      = 'product_id IN (' . implode( ',', array_fill( 0, count( $product_ids ), '%d' ) ) . ')';
			$where_values = array_merge( $where_values, $product_ids );
		}

		if ( $args['user_id'] ) {
			$where[]        = 'user_id = %d';
			$where_values[] = absint( $args['user_id'] );
		}

		if ( $args['user_email'] ) {
			$where[]        = 'user_email = %s';
			$where_values[] = esc_sql( $args['user_email'] );
		}

		if ( $args['last_attempt_limit'] > 0 ) {
			$where[]        = '(date_last_attempt_gmt < %s OR date_last_attempt_gmt IS NULL)';
			$where_values[] = gmdate( 'Y-m-d H:i:s', $args['last_attempt_limit'] );
		}

		if ( $args['start_date'] ) {
			$where[]        = 'date_created_gmt >= %s';
			$where_values[] = esc_sql( $args['start_date'] );
		}

		if ( $args['end_date'] ) {
			$where[]        = 'date_created_gmt < %s';
			$where_values[] = esc_sql( $args['end_date'] );
		}

		// ORDER BY clauses.
		$order_by         = '';
		$order_by_clauses = array();

		if ( $args['order_by'] && is_array( $args['order_by'] ) ) {
			foreach ( $args['order_by'] as $what => $how ) {
				$order_by_clauses[] = $table . '.' . esc_sql( strval( $what ) ) . ' ' . esc_sql( strval( $how ) );
			}
		}

		// Assemble the query.
		$where    = implode( ' AND ', $where );
		$where    = $where ? ' WHERE ' . $where : '';
		$order_by = ! empty( $order_by_clauses ) ? ' ORDER BY ' . implode( ', ', $order_by_clauses ) : '';
		$limit    = $args['limit'] > 0 ? ' LIMIT ' . absint( $args['limit'] ) : '';
		$offset   = $args['offset'] > 0 ? ' OFFSET ' . absint( $args['offset'] ) : '';
		$sql      = "SELECT $select FROM $table $where $order_by $limit $offset";

		// Prepare the query.
		$prepared_sql = empty( $where_values ) ? $sql : $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Execute the query.
		if ( 'count' === $args['return'] ) {
			return (int) $wpdb->get_var( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $prepared_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $results ) || ! is_array( $results ) ) {
			return array();
		}

		if ( 'objects' === $args['return'] ) {

			return array_map(
				function ( $result ) {
					return new Notification( $result );
				},
				$results
			);
		}

		return array_map(
			function ( $result ) {
				return absint( $result['id'] );
			},
			$results
		);
	}

	/**
	 * Check if the product has active notifications.
	 *
	 * @param array<int> $product_ids The product IDs.
	 * @return bool True if the product has active notifications, false otherwise.
	 */
	public function product_has_active_notifications( array $product_ids ): bool {
		global $wpdb;

		$product_ids = array_filter( array_map( 'absint', $product_ids ) );
		if ( empty( $product_ids ) ) {
			return false;
		}

		$table    = $this->get_table_name();
		$format   = array_fill( 0, count( $product_ids ), '%d' );
		$query_in = '(' . implode( ',', $format ) . ')';
		$sql      = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			"SELECT 1 FROM %i WHERE product_id IN $query_in AND status = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( $table, ...$product_ids, NotificationStatus::ACTIVE )
		);
		return (int) $wpdb->get_var( $sql ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Check if a notification exists by email.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $email The email address.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public function notification_exists_by_email( int $product_id, string $email ): bool {

		if ( ! is_email( $email ) ) {
			return false;
		}

		global $wpdb;

		$table = $this->get_table_name();
		$sql   = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			'SELECT 1 FROM %i WHERE product_id = %d AND user_email = %s AND status IN (%s, %s) LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( $table, $product_id, $email, NotificationStatus::ACTIVE, NotificationStatus::PENDING )
		);
		return (int) $wpdb->get_var( $sql ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Check if a notification exists by user ID.
	 *
	 * @param int $product_id The product ID.
	 * @param int $user_id The user ID.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public function notification_exists_by_user_id( int $product_id, int $user_id ): bool {

		if ( 0 === $user_id ) {
			return false;
		}

		global $wpdb;

		$table = $this->get_table_name();
		$sql   = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			'SELECT 1 FROM %i WHERE product_id = %d AND user_id = %d AND status IN (%s, %s) LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( $table, $product_id, $user_id, NotificationStatus::ACTIVE, NotificationStatus::PENDING )
		);
		return (int) $wpdb->get_var( $sql ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get distinct notification creation dates.
	 *
	 * @return array
	 */
	public function get_distinct_dates() {

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT
					YEAR(date_created_gmt) AS year,
					MONTH(date_created_gmt) AS month
				FROM %i
				ORDER BY year DESC, month DESC',
				$this->get_table_name()
			)
		);

		return $results;
	}
}

<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use RuntimeException;

/**
 * Storage for POS cash sessions, their accounting movements and drawer audit events.
 *
 * Tables use the site prefix, so each site in a network has its own data. Unique keys enforce one open
 * session per device, one movement per order/refund source per site, and one record per request ID.
 * Insert methods return null when a unique key rejects the row; callers re-read to decide why.
 *
 * @since 11.3.0
 */
class CashSessionsDataStore {

	/**
	 * Database utility, used for index length limits.
	 *
	 * @var DatabaseUtil
	 */
	private DatabaseUtil $database_util;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param DatabaseUtil $database_util Database utility.
	 */
	final public function init( DatabaseUtil $database_util ): void {
		$this->database_util = $database_util;
	}

	/**
	 * Sessions table name.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	public function get_sessions_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_pos_cash_sessions';
	}

	/**
	 * Movements table name.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	public function get_movements_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_pos_cash_movements';
	}

	/**
	 * Drawer events table name.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	public function get_drawer_events_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_pos_cash_drawer_events';
	}

	/**
	 * Table names owned by this data store.
	 *
	 * @since 11.3.0
	 *
	 * @return string[]
	 */
	public function get_table_names(): array {
		return array( $this->get_sessions_table(), $this->get_movements_table(), $this->get_drawer_events_table() );
	}

	/**
	 * Schema for dbDelta, used by WC_Install.
	 *
	 * Amounts are BIGINT minor units at the session precision.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate          = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$max_index_length = $this->database_util->get_max_index_length();
		$sessions         = $this->get_sessions_table();
		$movements        = $this->get_movements_table();
		$drawer_events    = $this->get_drawer_events_table();

		return "
CREATE TABLE $sessions (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	device_id varchar(128) NOT NULL,
	open_device_hash char(64) NULL,
	drawer_name varchar(128) NULL,
	drawer_key varchar(255) NULL,
	status varchar(20) NOT NULL,
	revision int(10) unsigned NOT NULL DEFAULT 1,
	currency char(3) NOT NULL,
	currency_precision tinyint(3) unsigned NOT NULL,
	expected_amount bigint(20) NULL,
	counted_amount bigint(20) NULL,
	variance bigint(20) NULL,
	note text NULL,
	opened_by bigint(20) unsigned NOT NULL,
	opened_by_name varchar(250) NOT NULL,
	closed_by bigint(20) unsigned NULL,
	closed_by_name varchar(250) NULL,
	date_created_gmt datetime NOT NULL,
	date_closed_gmt datetime NULL,
	open_request_id char(36) NOT NULL,
	open_request_hash char(64) NOT NULL,
	close_request_id char(36) NULL,
	close_request_hash char(64) NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY open_request_id (open_request_id),
	UNIQUE KEY open_device_hash (open_device_hash),
	KEY device_id (device_id),
	KEY drawer_key (drawer_key($max_index_length)),
	KEY status (status)
) $collate;
CREATE TABLE $movements (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	session_id bigint(20) unsigned NOT NULL,
	type varchar(20) NOT NULL,
	amount bigint(20) unsigned NOT NULL,
	reason varchar(500) NOT NULL DEFAULT '',
	order_id bigint(20) unsigned NULL,
	refund_id bigint(20) unsigned NULL,
	source_key varchar(40) NULL,
	created_by bigint(20) unsigned NOT NULL,
	created_by_name varchar(250) NOT NULL,
	occurred_at_gmt datetime NOT NULL,
	date_created_gmt datetime NOT NULL,
	request_id char(36) NULL,
	request_hash char(64) NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY session_request (session_id, request_id),
	UNIQUE KEY source_key (source_key),
	KEY order_id (order_id)
) $collate;
CREATE TABLE $drawer_events (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	session_id bigint(20) unsigned NOT NULL,
	type varchar(20) NOT NULL,
	reason varchar(20) NOT NULL,
	drawer_name varchar(128) NOT NULL,
	order_id bigint(20) unsigned NULL,
	refund_id bigint(20) unsigned NULL,
	movement_id bigint(20) unsigned NULL,
	correlation_id char(36) NULL,
	occurred_at_gmt datetime NOT NULL,
	created_by bigint(20) unsigned NOT NULL,
	created_by_name varchar(250) NOT NULL,
	date_created_gmt datetime NOT NULL,
	request_id char(36) NOT NULL,
	request_hash char(64) NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY session_request (session_id, request_id),
	KEY correlation_id (correlation_id)
) $collate;
";
	}

	/**
	 * Insert an open session.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $row Column values; status, revision and open_device_hash are set here.
	 * @return int|null New session ID, or null when a unique key rejected the row.
	 */
	public function insert_session( array $row ): ?int {
		$row['status']           = CashSessionStatus::OPEN;
		$row['revision']         = 1;
		$row['open_device_hash'] = self::device_hash( (string) $row['device_id'] );

		return $this->insert( $this->get_sessions_table(), $row );
	}

	/**
	 * Insert a movement.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $row Column values.
	 * @return int|null New movement ID, or null when a unique key rejected the row.
	 */
	public function insert_movement( array $row ): ?int {
		return $this->insert( $this->get_movements_table(), $row );
	}

	/**
	 * Insert a drawer event.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $row Column values.
	 * @return int|null New event ID, or null when a unique key rejected the row.
	 */
	public function insert_drawer_event( array $row ): ?int {
		return $this->insert( $this->get_drawer_events_table(), $row );
	}

	/**
	 * Read a session.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @return array<string, mixed>|null
	 */
	public function get_session( int $session_id ): ?array {
		global $wpdb;
		$table = $this->get_sessions_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $session_id ), ARRAY_A ) );
	}

	/**
	 * Read a session with an exclusive row lock, inside a transaction.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @return array<string, mixed>|null Null when the session does not exist.
	 * @throws RuntimeException When the query fails, for example on a lock wait timeout.
	 */
	public function lock_session( int $session_id ): ?array {
		global $wpdb;
		$table = $this->get_sessions_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d FOR UPDATE", $session_id ), ARRAY_A );
		if ( null === $row && '' !== $wpdb->last_error ) {
			throw new RuntimeException( 'Could not lock the cash session: ' . esc_html( $wpdb->last_error ) );
		}
		return $this->row_or_null( $row );
	}

	/**
	 * Find a session by the request ID that opened it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $request_id Lowercase request ID.
	 * @return array<string, mixed>|null
	 */
	public function find_session_by_open_request( string $request_id ): ?array {
		global $wpdb;
		$table = $this->get_sessions_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE open_request_id = %s", $request_id ), ARRAY_A ) );
	}

	/**
	 * Find the open session of a device. Device IDs match exactly.
	 *
	 * @since 11.3.0
	 *
	 * @param string $device_id Device ID.
	 * @return array<string, mixed>|null
	 */
	public function find_open_session_by_device( string $device_id ): ?array {
		global $wpdb;
		$table = $this->get_sessions_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE open_device_hash = %s", self::device_hash( $device_id ) ), ARRAY_A ) );
	}

	/**
	 * Query sessions, newest first.
	 *
	 * @since 11.3.0
	 *
	 * @param array{device_id?: string, drawer_key?: string, status?: string} $filters Exact-match filters.
	 * @param int                                                             $page     1-based page.
	 * @param int                                                             $per_page Page size.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public function query_sessions( array $filters, int $page, int $per_page ): array {
		global $wpdb;

		$where = array( '1=1' );
		$args  = array();
		// device_id is compared through the binary hash of the column so that matching stays exact.
		if ( isset( $filters['device_id'] ) ) {
			$where[] = 'device_id = %s AND SHA2(device_id, 256) = %s';
			$args[]  = $filters['device_id'];
			$args[]  = self::device_hash( $filters['device_id'] );
		}
		if ( isset( $filters['drawer_key'] ) ) {
			$where[] = 'drawer_key = %s';
			$args[]  = $filters['drawer_key'];
		}
		if ( isset( $filters['status'] ) ) {
			$where[] = 'status = %s';
			$args[]  = $filters['status'];
		}

		return $this->paginate( $this->get_sessions_table(), implode( ' AND ', $where ), $args, 'id DESC', $page, $per_page );
	}

	/**
	 * Increase the revision of an open session.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @return bool False when the session is not open.
	 */
	public function bump_revision( int $session_id ): bool {
		global $wpdb;
		$table = $this->get_sessions_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE $table SET revision = revision + 1 WHERE id = %d AND status = %s", $session_id, CashSessionStatus::OPEN ) );
		return 1 === $updated;
	}

	/**
	 * Close an open session if its revision still matches.
	 *
	 * @since 11.3.0
	 *
	 * @param int                  $session_id        Session ID.
	 * @param int                  $expected_revision Revision the totals were computed at.
	 * @param array<string, mixed> $fields            Closing column values.
	 * @return bool False when the session was already closed or changed.
	 */
	public function close_session( int $session_id, int $expected_revision, array $fields ): bool {
		global $wpdb;

		$fields['status']           = CashSessionStatus::CLOSED;
		$fields['open_device_hash'] = null;

		$updated = $wpdb->update(
			$this->get_sessions_table(),
			$fields,
			array(
				'id'       => $session_id,
				'status'   => CashSessionStatus::OPEN,
				'revision' => $expected_revision,
			)
		);
		return 1 === $updated;
	}

	/**
	 * Read a movement.
	 *
	 * @since 11.3.0
	 *
	 * @param int $movement_id Movement ID.
	 * @return array<string, mixed>|null
	 */
	public function get_movement( int $movement_id ): ?array {
		global $wpdb;
		$table = $this->get_movements_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $movement_id ), ARRAY_A ) );
	}

	/**
	 * Find a movement by session and request ID.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $session_id Session ID.
	 * @param string $request_id Lowercase request ID.
	 * @return array<string, mixed>|null
	 */
	public function find_movement_by_request( int $session_id, string $request_id ): ?array {
		global $wpdb;
		$table = $this->get_movements_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE session_id = %d AND request_id = %s", $session_id, $request_id ), ARRAY_A ) );
	}

	/**
	 * Find the movement recorded for an order or refund source.
	 *
	 * @since 11.3.0
	 *
	 * @param string $source_key Source key such as "order:123".
	 * @return array<string, mixed>|null
	 */
	public function find_movement_by_source( string $source_key ): ?array {
		global $wpdb;
		$table = $this->get_movements_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE source_key = %s", $source_key ), ARRAY_A ) );
	}

	/**
	 * Query the movements of a session, oldest first.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @param int $page       1-based page.
	 * @param int $per_page   Page size.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public function query_movements( int $session_id, int $page, int $per_page ): array {
		return $this->paginate( $this->get_movements_table(), 'session_id = %d', array( $session_id ), 'id ASC', $page, $per_page );
	}

	/**
	 * Sum movement amounts by session and type.
	 *
	 * @since 11.3.0
	 *
	 * @param int[] $session_ids Session IDs.
	 * @return array<int, array<string, int>> Session ID => movement type => minor units.
	 * @throws RuntimeException When the query fails.
	 */
	public function get_movement_sums( array $session_ids ): array {
		global $wpdb;

		$session_ids = array_values( array_unique( array_map( 'absint', $session_ids ) ) );
		if ( empty( $session_ids ) ) {
			return array();
		}

		$table        = $this->get_movements_table();
		$placeholders = implode( ',', array_fill( 0, count( $session_ids ), '%d' ) );
		$rows         = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name is trusted, placeholders are generated.
			$wpdb->prepare( "SELECT session_id, type, CAST(SUM(amount) AS CHAR) AS total FROM $table WHERE session_id IN ($placeholders) GROUP BY session_id, type", $session_ids ),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			throw new RuntimeException( 'Could not read cash movement totals: ' . esc_html( $wpdb->last_error ) );
		}

		$sums = array();
		foreach ( $rows as $row ) {
			$total = (string) $row['total'];
			if ( strlen( $total ) > strlen( (string) CashMoney::MAX_TOTAL_MINOR_UNITS ) ) {
				throw new RuntimeException( 'A cash movement total is out of range.' );
			}
			$sums[ (int) $row['session_id'] ][ (string) $row['type'] ] = (int) $total;
		}
		return $sums;
	}

	/**
	 * Read a drawer event.
	 *
	 * @since 11.3.0
	 *
	 * @param int $event_id Event ID.
	 * @return array<string, mixed>|null
	 */
	public function get_drawer_event( int $event_id ): ?array {
		global $wpdb;
		$table = $this->get_drawer_events_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $event_id ), ARRAY_A ) );
	}

	/**
	 * Find a drawer event by session and request ID.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $session_id Session ID.
	 * @param string $request_id Lowercase request ID.
	 * @return array<string, mixed>|null
	 */
	public function find_drawer_event_by_request( int $session_id, string $request_id ): ?array {
		global $wpdb;
		$table = $this->get_drawer_events_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted.
		return $this->row_or_null( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE session_id = %d AND request_id = %s", $session_id, $request_id ), ARRAY_A ) );
	}

	/**
	 * Query the drawer events of a session, oldest first.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @param int $page       1-based page.
	 * @param int $per_page   Page size.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public function query_drawer_events( int $session_id, int $page, int $per_page ): array {
		return $this->paginate( $this->get_drawer_events_table(), 'session_id = %d', array( $session_id ), 'id ASC', $page, $per_page );
	}

	/**
	 * Hash used for the open-device unique key, so device IDs compare exactly regardless of collation.
	 *
	 * @param string $device_id Device ID.
	 * @return string
	 */
	private static function device_hash( string $device_id ): string {
		return hash( 'sha256', $device_id );
	}

	/**
	 * Insert a row, suppressing the database error a unique key violation would print.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $row   Column values.
	 * @return int|null Insert ID, or null on failure.
	 */
	private function insert( string $table, array $row ): ?int {
		global $wpdb;

		$suppress = $wpdb->suppress_errors( true );
		$result   = $wpdb->insert( $table, $row );
		$wpdb->suppress_errors( $suppress );

		return 1 === $result ? (int) $wpdb->insert_id : null;
	}

	/**
	 * Run a paginated query.
	 *
	 * @param string       $table    Table name.
	 * @param string       $where    WHERE clause with placeholders.
	 * @param array<mixed> $args     Placeholder values.
	 * @param string       $order_by Trusted ORDER BY clause.
	 * @param int          $page     1-based page.
	 * @param int          $per_page Page size.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 * @throws RuntimeException When a query fails.
	 */
	private function paginate( string $table, string $where, array $args, string $order_by, int $page, int $per_page ): array {
		global $wpdb;

		$offset = ( max( 1, $page ) - 1 ) * $per_page;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Table, WHERE and ORDER BY are built from trusted fragments with placeholders.
		$count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
		$total     = $wpdb->get_var( empty( $args ) ? $count_sql : $wpdb->prepare( $count_sql, $args ) );
		$rows      = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY $order_by LIMIT %d OFFSET %d", array_merge( $args, array( $per_page, $offset ) ) ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		if ( null === $total || ! is_array( $rows ) ) {
			throw new RuntimeException( 'Could not query cash session records: ' . esc_html( $wpdb->last_error ) );
		}

		return array(
			'rows'  => $rows,
			'total' => (int) $total,
		);
	}

	/**
	 * Normalize a get_row() result.
	 *
	 * @param mixed $row Query result.
	 * @return array<string, mixed>|null
	 */
	private function row_or_null( $row ): ?array {
		return is_array( $row ) ? $row : null;
	}
}

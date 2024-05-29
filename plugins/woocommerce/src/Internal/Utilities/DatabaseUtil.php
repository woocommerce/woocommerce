<?php
/**
 * DatabaseUtil class file.
 */

namespace Automattic\WooCommerce\Internal\Utilities;

use DateTime;
use DateTimeZone;
use Vtiful\Kernel\Format;

/**
 * A class of utilities for dealing with the database.
 */
class DatabaseUtil {

	/**
	 * Wrapper for the WordPress dbDelta function, allows to execute a series of SQL queries.
	 *
	 * @param string $queries The SQL queries to execute.
	 * @param bool   $execute Ture to actually execute the queries, false to only simulate the execution.
	 * @return array The result of the execution (or simulation) from dbDelta.
	 */
	public function dbdelta( string $queries = '', bool $execute = true ): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		return dbDelta( $queries, $execute );
	}

	/**
	 * Given a set of table creation SQL statements, check which of the tables are currently missing in the database.
	 *
	 * @param string $creation_queries The SQL queries to execute ("CREATE TABLE" statements, same format as for dbDelta).
	 * @return array An array containing the names of the tables that currently don't exist in the database.
	 */
	public function get_missing_tables( string $creation_queries ): array {
		global $wpdb;
		$suppress_errors = $wpdb->suppress_errors( true );
		$dbdelta_output  = $this->dbdelta( $creation_queries, false );
		$wpdb->suppress_errors( $suppress_errors );
		$parsed_output = $this->parse_dbdelta_output( $dbdelta_output );
		return $parsed_output['created_tables'];
	}

	/**
	 * Parses the output given by dbdelta and returns information about it.
	 *
	 * @param array $dbdelta_output The output from the execution of dbdelta.
	 * @return array[] An array containing a 'created_tables' key whose value is an array with the names of the tables that have been (or would have been) created.
	 */
	public function parse_dbdelta_output( array $dbdelta_output ): array {
		$created_tables = array();

		foreach ( $dbdelta_output as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$created_tables[] = str_replace( '(', '', $table_name );
			}
		}

		return array( 'created_tables' => $created_tables );
	}

	/**
	 * Drops a database table.
	 *
	 * @param string $table_name The name of the table to drop.
	 * @param bool   $add_prefix True if the table name passed needs to be prefixed with $wpdb->prefix before processing.
	 * @return bool True on success, false on error.
	 */
	public function drop_database_table( string $table_name, bool $add_prefix = false ) {
		global $wpdb;

		if ( $add_prefix ) {
			$table_name = $wpdb->prefix . $table_name;
		}

		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
	}

	/**
	 * Drops a table index, if both the table and the index exist.
	 *
	 * @param string $table_name The name of the table that contains the index.
	 * @param string $index_name The name of the index to be dropped.
	 * @return bool True if the index has been dropped, false if either the table or the index don't exist.
	 */
	public function drop_table_index( string $table_name, string $index_name ): bool {
		global $wpdb;

		if ( empty( $this->get_index_columns( $table_name, $index_name ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "ALTER TABLE $table_name DROP INDEX $index_name" );
		return true;
	}

	/**
	 * Create a primary key for a table, only if the table doesn't have a primary key already.
	 *
	 * @param string $table_name Table name.
	 * @param array  $columns An array with the index column names.
	 * @return bool True if the key has been created, false if the table already had a primary key.
	 */
	public function create_primary_key( string $table_name, array $columns ) {
		global $wpdb;

		if ( ! empty( $this->get_index_columns( $table_name ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "ALTER TABLE $table_name ADD PRIMARY KEY(`" . join( '`,`', $columns ) . '`)' );
		return true;
	}

	/**
	 * Get the columns of a given table index, or of the primary key.
	 *
	 * @param string $table_name Table name.
	 * @param string $index_name Index name, empty string for the primary key.
	 * @return array The index columns. Empty array if the table or the index don't exist.
	 */
	public function get_index_columns( string $table_name, string $index_name = '' ): array {
		global $wpdb;

		if ( empty( $index_name ) ) {
			$index_name = 'PRIMARY';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM $table_name WHERE Key_name = %s", $index_name ) );

		if ( empty( $results ) ) {
			return array();
		}

		return array_column( $results, 'Column_name' );
	}

	/**
	 * Formats an object value of type `$type` for inclusion in the database.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  Data type.
	 * @return mixed
	 * @throws \Exception When an invalid type is passed.
	 */
	public function format_object_value_for_db( $value, string $type ) {
		switch ( $type ) {
			case 'decimal':
				$value = wc_format_decimal( $value, false, true );
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'bool':
				$value = wc_string_to_bool( $value );
				break;
			case 'string':
				$value = strval( $value );
				break;
			case 'date':
				// Date properties are converted to the WP timezone (see WC_Data::set_date_prop() method), however
				// for our own tables we persist dates in GMT.
				$value = $value ? ( new DateTime( $value ) )->setTimezone( new DateTimeZone( '+00:00' ) )->format( 'Y-m-d H:i:s' ) : null;
				break;
			case 'date_epoch':
				$value = $value ? ( new DateTime( "@{$value}" ) )->format( 'Y-m-d H:i:s' ) : null;
				break;
			default:
				throw new \Exception( esc_html( 'Invalid type received: ' . $type ) );
		}

		return $value;
	}

	/**
	 * Returns the `$wpdb` placeholder to use for data type `$type`.
	 *
	 * @param string $type Data type.
	 * @return string
	 * @throws \Exception When an invalid type is passed.
	 */
	public function get_wpdb_format_for_type( string $type ) {
		static $wpdb_placeholder_for_type = array(
			'int'        => '%d',
			'decimal'    => '%f',
			'string'     => '%s',
			'date'       => '%s',
			'date_epoch' => '%s',
			'bool'       => '%d',
		);

		if ( ! isset( $wpdb_placeholder_for_type[ $type ] ) ) {
			throw new \Exception( esc_html( 'Invalid column type: ' . $type ) );
		}

		return $wpdb_placeholder_for_type[ $type ];
	}

	/**
	 * Generates ON DUPLICATE KEY UPDATE clause to be used in migration.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return string SQL clause for INSERT...ON DUPLICATE KEY UPDATE
	 */
	public function generate_on_duplicate_statement_clause( array $columns ): string {
		$update_value_statements = array();
		foreach ( $columns as $column ) {
			$update_value_statements[] = "`$column` = VALUES( `$column` )";
		}
		$update_value_clause = implode( ', ', $update_value_statements );

		return "ON DUPLICATE KEY UPDATE $update_value_clause";
	}

	/**
	 * Hybrid of $wpdb->update and $wpdb->insert. It will try to update a row, and if it doesn't exist, it will insert it. This needs unique constraints to be set on the table on all ID columns.
	 *
	 * You can use this function only when:
	 * 1. There is only one unique constraint on the table. The constraint can contain multiple columns, but it must be the only one unique constraint.
	 * 2. The complete unique constraint must be part of the $data array.
	 * 3. You do not need the LAST_INSERT_ID() value.
	 *
	 * @param string $table_name Table name.
	 * @param array  $data Unescaped data to update (in column => value pairs).
	 * @param array  $format An array of formats to be mapped to each of the values in $data.
	 *
	 * @return int Returns the value of DB's  ON DUPLICATE KEY UPDATE clause.
	 */
	public function insert_on_duplicate_key_update( $table_name, $data, $format ): int {
		global $wpdb;
		if ( empty( $data ) ) {
			return 0;
		}

		$columns      = array_keys( $data );
		$value_format = array();
		$values       = array();
		$index        = 0;
		// Directly use NULL for placeholder if the value is NULL, since otherwise $wpdb->prepare will convert it to empty string.
		foreach ( $data as $key => $value ) {
			if ( is_null( $value ) ) {
				$value_format[] = 'NULL';
			} else {
				$values[]       = $value;
				$value_format[] = $format[ $index ];
			}
			++$index;
		}
		$column_clause       = '`' . implode( '`, `', $columns ) . '`';
		$value_format_clause = implode( ', ', $value_format );
		$on_duplicate_clause = $this->generate_on_duplicate_statement_clause( $columns );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$sql = $wpdb->prepare(
			"
INSERT INTO $table_name ( $column_clause )
VALUES ( $value_format_clause )
$on_duplicate_clause
",
			$values
		);
		// phpcs:enable
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared.
		return $wpdb->query( $sql );
	}

	/**
	 * Hybrid of $wpdb->update and $wpdb->insert. It will try to update a row, and if it doesn't exist, it will insert it. Unlike `insert_on_duplicate_key_update` it does not require a unique constraint, but also does not guarantee uniqueness on its own.
	 *
	 * When a unique constraint is present, it will perform better than the `insert_on_duplicate_key_update` since it needs fewer locks.
	 *
	 * Note that it will only update at max just 1 database row, unlike `wpdb->update` which updates everything that matches the `$where` criteria. This is also why it needs a primary_key_column.
	 *
	 * @param string $table_name Table Name.
	 * @param array  $data Data to insert update in array($column_name => $value) format.
	 * @param array  $where Update conditions in array($column_name => $value) format. Conditions will be joined by AND.
	 * @param array  $format Format strings for data. Unlike $wpdb->update/insert, this method won't guess the format, and has to be provided explicitly.
	 * @param array  $where_format Format strings for where conditions. Unlike $wpdb->update/insert, this method won't guess the format, and has to be provided explicitly.
	 * @param string $primary_key_column Name of the Primary key column.
	 * @param string $primary_key_format Format for primary key.
	 *
	 * @return bool|int Number of rows affected. Boolean false on error.
	 */
	public function insert_or_update( $table_name, $data, $where, $format, $where_format, $primary_key_column = 'id', $primary_key_format = '%d' ) {
		global $wpdb;
		if ( empty( $data ) || empty( $where ) ) {
			return 0;
		}

		// Build select query.
		$values     = array();
		$index      = 0;
		$conditions = array();
		foreach ( $where as $column => $value ) {
			if ( is_null( $value ) ) {
				$conditions[] = "`$column` IS NULL";
				continue;
			}
			$conditions[] = "`$column` = " . $where_format[ $index ];
			$values[]     = $value;
			++$index;
		}

		$conditions = implode( ' AND ', $conditions );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $primary_key_column and $table_name are hardcoded. $conditions is being prepared.
		$query = $wpdb->prepare( "SELECT `$primary_key_column` FROM `$table_name` WHERE $conditions LIMIT 1", $values );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared above.
		$row_id = $wpdb->get_var( $query );

		if ( $row_id ) {
			// Update the row.
			$result = $wpdb->update( $table_name, $data, array( $primary_key_column => $row_id ), $format, array( $primary_key_format ) );
		} else {
			// Insert the row.
			$result = $wpdb->insert( $table_name, $data, $format );
		}

		return $result;
	}

	/**
	 * Get max index length.
	 *
	 * @return int Max index length.
	 */
	public function get_max_index_length(): int {
		/**
		 * Filters the maximum index length in the database.
		 *
		 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
		 * As of WP 4.2, however, they moved to utf8mb4, which uses 4 bytes per character. This means that an index which
		 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
		 *
		 * Additionally, MyISAM engine also limits the index size to 1000 bytes. We add this filter so that interested folks on InnoDB engine can increase the size till allowed 3071 bytes.
		 *
		 * @param int $max_index_length Maximum index length. Default 191.
		 *
		 * @since 8.0.0
		 */
		$max_index_length = apply_filters( 'woocommerce_database_max_index_length', 191 );
		// Index length cannot be more than 768, which is 3078 bytes in utf8mb4 and max allowed by InnoDB engine.
		return min( absint( $max_index_length ), 767 );
	}

	/**
	 * Create a fulltext index on order address table.
	 *
	 * @return void
	 */
	public function create_fts_index_order_address_table(): void {
		global $wpdb;
		$address_table = $wpdb->prefix . 'wc_order_addresses';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $address_table is hardcoded.
		$wpdb->query( "CREATE FULLTEXT INDEX order_addresses_fts ON $address_table (first_name, last_name, company, address_1, address_2, city, state, postcode, country, email)" );
	}

	/**
	 * Check if fulltext index with key `order_addresses_fts` on order address table exists.
	 *
	 * @return bool
	 */
	public function fts_index_on_order_address_table_exists(): bool {
		global $wpdb;
		$address_table = $wpdb->prefix . 'wc_order_addresses';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $address_table is hardcoded.
		return ! empty( $wpdb->get_results( "SHOW INDEX FROM $address_table WHERE Key_name = 'order_addresses_fts'" ) );
	}

	/**
	 * Create a fulltext index on order item table.
	 *
	 * @return void
	 */
	public function create_fts_index_order_item_table(): void {
		global $wpdb;
		$order_item_table = $wpdb->prefix . 'woocommerce_order_items';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_item_table is hardcoded.
		$wpdb->query( "CREATE FULLTEXT INDEX order_item_fts ON $order_item_table (order_item_name)" );
	}

	/**
	 * Check if fulltext index with key `order_item_fts` on order item table exists.
	 *
	 * @return bool
	 */
	public function fts_index_on_order_item_table_exists(): bool {
		global $wpdb;
		$order_item_table = $wpdb->prefix . 'woocommerce_order_items';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_item_table is hardcoded.
		return ! empty( $wpdb->get_results( "SHOW INDEX FROM $order_item_table WHERE Key_name = 'order_item_fts'" ) );
	}
}

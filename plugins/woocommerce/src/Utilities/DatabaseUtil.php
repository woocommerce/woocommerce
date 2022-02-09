<?php
/**
 * DatabaseUtil class file.
 */

namespace Automattic\WooCommerce\Internal\Utilities;

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
		$dbdelta_output = $this->dbdelta( $creation_queries, false );
		$parsed_output  = $this->parse_dbdelta_output( $dbdelta_output );
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
				$created_tables[] = $table_name;
			}
		}

		return array( 'created_tables' => $created_tables );
	}

	/**
	 * Drops a database table.
	 *
	 * @param string $table_name The name of the table to drop.
	 * @param bool   $add_prefix True if the table name passed needs to be prefixed with $wpdb->prefix before processing.
	 */
	public function drop_database_table( string $table_name, bool $add_prefix = false ) {
		global $wpdb;

		if ( $add_prefix ) {
			$table_name = $wpdb->prefix . $table_name;
		}

		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}

<?php
/**
 * A class of utilities for dealing with Database management.
 */

namespace Automattic\WooCommerce\Utilities;

use Automattic\Jetpack\Constants;

class DBUtil {

	/**
	 * Verify if the table(s) already exists.
	 *
	 * @param string $table_creation_sql Schema definition to check against.
	 *
	 * return array List of missing tables.
	 */
	public static function verify_database_tables_exist( $table_creation_sql ) {
		require_once( Constants::get_constant( 'ABSPATH' ) . 'wp-admin/includes/upgrade.php' );

		$missing_tables = array();
		$queries        = dbDelta( $table_creation_sql, false );

		foreach ( $queries as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$missing_tables[] = $table_name;
			}
		}
		return $missing_tables;
	}

	/**
	 * Create DB tables for passed schema. Currently a wrapper for dbDelta.
	 *
	 * @param string $table_creation_sql Table SQL
	 *
	 * @return array List of tables that we were not able to create.
	 */
	public static function create_database_tables( $table_creation_sql ) {
		require_once( Constants::get_constant( 'ABSPATH' ) . 'wp-admin/includes/upgrade.php' );
		dbDelta( $table_creation_sql );
		return self::verify_database_tables_exist( $table_creation_sql );
	}

}

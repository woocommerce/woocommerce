<?php
/**
 * Helper class with utility functions for migrations.
 */

namespace Automattic\WooCommerce\DataBase\Migrations;

/**
 * Class MigrationHelper.
 *
 * Helper class to asist with migration related operations.
 */
class MigrationHelper {

	/**
	 * Placeholders that we will use in building $wpdb queries.
	 *
	 * @var string[]
	 */
	private static $wpdb_placeholder_for_type = array(
		'int'        => '%d',
		'decimal'    => '%f',
		'string'     => '%s',
		'date'       => '%s',
		'date_epoch' => '%s',
		'bool'       => '%d',
	);

	/**
	 * Get insert clause for appropriate switch.
	 *
	 * @param string $switch Name of the switch to use.
	 *
	 * @return string Insert clause.
	 */
	public static function get_insert_switch( $switch ) {
		switch ( $switch ) {
			case 'insert_ignore':
				$insert_query = 'INSERT IGNORE';
				break;
			case 'replace':
				$insert_query = 'REPLACE';
				break;
			case 'insert':
			default:
				$insert_query = 'INSERT';
		}

		return $insert_query;
	}

	/**
	 * Helper method to escape backtick in column and table names.
	 * WP does not provide a method to escape table/columns names yet, but hopefully soon in @link https://core.trac.wordpress.org/ticket/52506
	 *
	 * @param string|array $identifier Column or table name.
	 *
	 * @return array|string|string[] Escaped identifier.
	 */
	public static function escape_backtick( $identifier ) {
		return str_replace( '`', '``', $identifier );
	}

	/**
	 * Return $wpdb->prepare placeholder for data type.
	 *
	 * @param string $type Data type.
	 *
	 * @return string $wpdb placeholder.
	 */
	public static function get_wpdb_placeholder_for_type( $type ) {
		return self::$wpdb_placeholder_for_type[ $type ];
	}

}

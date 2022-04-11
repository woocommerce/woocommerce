<?php
/**
 * Helper class with utility functions for migrations.
 */

namespace Automattic\WooCommerce\Database\Migrations;

/**
 * Class MigrationHelper.
 *
 * Helper class to assist with migration related operations.
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
			case 'replace': // delete and then insert.
				$insert_query = 'REPLACE';
				break;
			case 'update':
				$insert_query = 'UPDATE';
				break;
			case 'insert':
			default:
				$insert_query = 'INSERT';
		}

		return $insert_query;
	}

	/**
	 * Helper method to escape backtick in various schema fields.
	 *
	 * @param array $schema_config Schema config.
	 *
	 * @return array Schema config escaped for backtick.
	 */
	public static function escape_schema_for_backtick( $schema_config ) {
		array_walk( $schema_config['source']['entity'], array( self::class, 'escape_and_add_backtick' ) );
		array_walk( $schema_config['source']['meta'], array( self::class, 'escape_and_add_backtick' ) );
		array_walk( $schema_config['destination'], array( self::class, 'escape_and_add_backtick' ) );
		return $schema_config;
	}

	/**
	 * Helper method to escape backtick in column and table names.
	 * WP does not provide a method to escape table/columns names yet, but hopefully soon in @link https://core.trac.wordpress.org/ticket/52506
	 *
	 * @param string|array $identifier Column or table name.
	 *
	 * @return array|string|string[] Escaped identifier.
	 */
	public static function escape_and_add_backtick( $identifier ) {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
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

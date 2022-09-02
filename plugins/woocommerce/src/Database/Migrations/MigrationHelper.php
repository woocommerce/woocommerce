<?php
/**
 * Helper class with utility functions for migrations.
 */

namespace Automattic\WooCommerce\Database\Migrations;

/**
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
	 * Helper method to escape backtick in various schema fields.
	 *
	 * @param array $schema_config Schema config.
	 *
	 * @return array Schema config escaped for backtick.
	 */
	public static function escape_schema_for_backtick( array $schema_config ): array {
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
	public static function get_wpdb_placeholder_for_type( string $type ): string {
		return self::$wpdb_placeholder_for_type[ $type ];
	}

	/**
	 * Generates ON DUPLICATE KEY UPDATE clause to be used in migration.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return string SQL clause for INSERT...ON DUPLICATE KEY UPDATE
	 */
	public static function generate_on_duplicate_statement_clause( array $columns ): string {
		$update_value_statements = array();
		foreach ( $columns as $column ) {
			$update_value_statements[] = "$column = VALUES( $column )";
		}
		$update_value_clause = implode( ', ', $update_value_statements );

		return "ON DUPLICATE KEY UPDATE $update_value_clause";
	}

}

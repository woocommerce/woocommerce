<?php
/**
 * Generic Migration class to move any meta data associated to an entity, to a different meta table associated with a custom entity table.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\DataBase\Migrations\MigrationHelper;

/**
 * Class MetaToMetaTableMigrator.
 *
 * Generic class for powering migrations from one meta table to another table.
 *
 * @package Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable
 */
class MetaToMetaTableMigrator {

	/**
	 * Schema config, see __construct for more details.
	 *
	 * @var array
	 */
	private $schema_config;

	/**
	 * MetaToMetaTableMigrator constructor.
	 *
	 * @param array $schema_config This parameters provides general but essential information about tables under migrations. Must be of the form-
	 * TODO: Add structure.
	 */
	public function __construct( $schema_config ) {
		// TODO: Validate params.
		$this->schema_config = $schema_config;
	}

	/**
	 * Generate insert sql queries for batches.
	 *
	 * @param array  $batch Data to generate queries for.
	 * @param string $insert_switch Insert switch to use.
	 *
	 * @return string
	 */
	public function generate_insert_sql_for_batch( $batch, $insert_switch ) {
		global $wpdb;

		$insert_query = MigrationHelper::get_insert_switch( $insert_switch );

		$meta_key_column   = MigrationHelper::escape_backtick( $this->schema_config['destination']['meta']['meta_key_column'] );
		$meta_value_column = MigrationHelper::escape_backtick( $this->schema_config['destination']['meta']['meta_value_column'] );
		$entity_id_column  = MigrationHelper::escape_backtick( $this->schema_config['destination']['meta']['entity_id_column'] );
		$column_sql        = "(`$entity_id_column`, `$meta_key_column`, `$meta_value_column`)";
		$table             = $this->schema_config['destination']['meta_table_name'];

		$entity_id_column_placeholder = MigrationHelper::get_wpdb_placeholder_for_type( $this->schema_config['destination']['meta']['entity_id_type'] );
		$placeholder_string           = "( $entity_id_column_placeholder, %s, %s )";
		$values                       = array();
		foreach ( array_values( $batch ) as $row ) {
			$query_params = array(
				$row['destination_entity_id'],
				$row['meta_key'],
				$row['meta_value'],
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholder_string is hardcoded.
			$value_sql = $wpdb->prepare( "( $placeholder_string )", $query_params );
			$values[]  = $value_sql;
		}

		$values_sql = implode( ',', $values );

		return "$insert_query INTO $table $column_sql VALUES $values_sql";
	}

	/**
	 * Fetch data for migration.
	 *
	 * @param string $where_clause Where conditions to use while selecting data from source table.
	 *
	 * @return array[] Data along with errors (if any), will of the form:
	 * array(
	 *  'data' => array(
	 *      'id_1' => array( 'column1' => value1, 'column2' => value2, ...),
	 *      ...,
	 *   ),
	 *  'errors' => array(
	 *      'id_1' => array( 'column1' => error1, 'column2' => value2, ...),
	 *      ...,
	 * )
	 */
	public function fetch_data_for_migration( $where_clause ) {
		global $wpdb;

		$meta_query = $this->build_meta_table_query( $where_clause );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Meta query has interpolated variables, but they should all be escaped for backticks.
		$meta_data_rows = $wpdb->get_results( $meta_query );
		if ( empty( $meta_data_rows ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}

		return $meta_data_rows;
	}

	/**
	 * Helper method to build query used to fetch data from source meta table.
	 *
	 * @param string $where_clause Where conditions to use while selecting data from source table.
	 *
	 * @return string Query that can be used to fetch data.
	 */
	private function build_meta_table_query( $where_clause ) {
		global $wpdb;
		$source_meta_key_column   = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_key_column'] );
		$source_meta_value_column = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_value_column'] );
		$source_entity_id_column  = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_entity_id_column'] );
		$source_meta_table_name   = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_table_name'] );
		$order_by                 = "`$source_meta_table_name`.`$source_entity_id_column` ASC";

		$destination_entity_table             = MigrationHelper::escape_backtick( $this->schema_config['destination']['entity_table_name'] );
		$destination_entity_id_column         = MigrationHelper::escape_backtick( $this->schema_config['destination']['entity_id_column'] );
		$destination_source_id_mapping_column = MigrationHelper::escape_backtick( $this->schema_config['destination']['source_id_column'] );

		if ( $this->schema_config['source']['excluded_keys'] ) {
			$key_placeholder = implode( ',', array_fill( 0, count( $this->schema_config['source']['excluded_keys'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $source_meta_key_column is escated for backticks, $key_placeholder is hardcoded.
			$exclude_clause  = $wpdb->prepare( "`$source_meta_key_column` NOT IN ( $key_placeholder )", $this->schema_config['source']['excluded_keys'] );
			$where_clause    = "$where_clause AND $exclude_clause";
		}

		$meta_data_sql = "
SELECT
	`$source_meta_table_name`.`$source_entity_id_column` as source_entity_id,
	`$destination_entity_table`.`$destination_entity_id_column` as destination_entity_id,
	`$source_meta_table_name`.`$source_meta_key_column` as meta_key,
	`$source_meta_table_name`.`$source_meta_value_column` as meta_value
FROM `$source_meta_table_name`
JOIN `$destination_entity_table` ON `$destination_entity_table`.`$destination_source_id_mapping_column` = `$source_meta_table_name`.`$source_entity_id_column`
WHERE $where_clause ORDER BY $order_by
";

		return $meta_data_sql;
	}

}

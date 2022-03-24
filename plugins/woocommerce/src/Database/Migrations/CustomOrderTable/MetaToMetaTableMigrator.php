<?php
/**
 * Generic Migration class to move any meta data associated to an entity, to a different meta table associated with a custom entity table.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use MigrationHelper;

class MetaToMetaTableMigrator {

	private $schema_config;

	/**
	 * MetaToMetaTableMigrator constructor.
	 *
	 * @param array $schema_config This parameters provides general but essential information about tables under migrations. Must be of the form-
	 * array(
	 *  'entity_schema' =>
	 *      array (
	 *          'primary_id' => 'primary_id column name of source table',
	 *          'table_name' => 'name of the source table'.
	 *      ),
	 *  'entity_meta_schema' =>
	 *      array (
	 *          'meta_key_column' => 'name of meta_key column in source meta table',
	 *          'meta_value_column' => 'name of meta_value column in source meta table',
	 *          'table_name' => 'name of source meta table',
	 *      ),
	 *  'destination_table' => 'name of destination custom table',
	 *  'entity_meta_relation' =>
	 *      array (
	 *          'entity' => 'name of column in source table which is used in source meta table',
	 *          'meta' => 'name of column in source meta table which contains key of records in source table',
	 *      )
	 *  )
	 * ).
	 * @param array $exclude_columns List of columns to exclude.
	 */
	public function __construct( $schema_config ) {
		$this->schema_config = $schema_config;
	}

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
				$row['meta_value']
			);
			$value_sql    = $wpdb->prepare( "( $placeholder_string )", $query_params );
			$values[]     = $value_sql;
		}

		$values_sql = implode( ',', $values );

		return "$insert_query INTO $table $column_sql VALUES $values_sql";
	}

	public function fetch_data_for_migration( $where_clause ) {
		global $wpdb;

		$meta_query = $this->build_meta_table_query( $where_clause );

		$meta_data_rows = $wpdb->get_results( $meta_query );
		if ( empty ( $meta_data_rows ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}

		return $meta_data_rows;
	}

	private function build_meta_table_query( $where_clause ) {
		global $wpdb;
		$source_meta_key_column   = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_key_column'] );
		$source_meta_value_column = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_value_column'] );
		$source_entity_id_column  = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_entity_id_column'] );
		$source_meta_table_name   = MigrationHelper::escape_backtick( $this->schema_config['source']['meta_table_name'] );
		$order_by                 = "`$source_meta_table_name`.`$source_entity_id_column` ASC";

		$destination_entity_table             = $this->schema_config['destination']['entity_table_name'];
		$destination_entity_id_column         = $this->schema_config['destination']['entity_id_column'];
		$destination_source_id_mapping_column = $this->schema_config['destination']['source_id_column'];

		if ( $this->schema_config['source']['excluded_keys'] ) {
			$key_placeholder = implode( ',', array_fill( 0, count( $this->schema_config['source']['excluded_keys'] ), '%s' ) );
			$exclude_clause  = $wpdb->prepare( "$source_meta_key_column NOT IN ( $key_placeholder )", $this->schema_config['source']['excluded_keys'] );
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

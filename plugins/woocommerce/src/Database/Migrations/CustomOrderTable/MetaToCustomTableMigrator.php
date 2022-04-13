<?php
/**
 * Generic migration class to move any entity, entity_meta table combination to custom table.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MigrationHelper;

/**
 * Class MetaToCustomTableMigrator.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
abstract class MetaToCustomTableMigrator {

	/**
	 * Config for tables being migrated and migrated from. See __construct() for detailed config.
	 *
	 * @var array
	 */
	protected $schema_config;

	/**
	 * Meta config, see __construct for detailed config.
	 *
	 * @var array
	 */
	protected $meta_column_mapping;

	/**
	 * Column mapping from source table to destination custom table. See __construct for detailed config.
	 *
	 * @var array
	 */
	protected $core_column_mapping;

	/**
	 * Store errors along with entity IDs from migrations.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * MetaToCustomTableMigrator constructor.
	 */
	public function __construct() {
		$this->schema_config       = MigrationHelper::escape_schema_for_backtick( $this->get_schema_config() );
		$this->meta_column_mapping = $this->get_meta_column_config();
		$this->core_column_mapping = $this->get_core_column_mapping();
		$this->errors              = array();
	}

	/**
	 * Specify schema config the source and destination table.
	 *
	 * @return array Schema, must of the form:
	 * array(
		'source' => array(
			'entity' => array(
				'table_name' => $source_table_name,
				'meta_rel_column' => $column_meta, Name of column in source table which is referenced by meta table.
				'destination_rel_column' => $column_dest, Name of column in source table which is refenced by destination table,
				'primary_key' => $primary_key, Primary key of the source table
			),
			'meta' => array(
				'table' => $meta_table_name,
				'meta_key_column' => $meta_key_column_name,
				'meta_value_column' => $meta_value_column_name,
				'entity_id_column' => $entity_id_column, Name of the column having entity IDs.
			),
		),
		'destination' => array(
			'table_name' => $table_name, Name of destination table,
			'source_rel_column' => $column_source_id, Name of the column in destination table which is referenced by source table.
			'primary_key' => $table_primary_key,
			'primary_key_type' => $type bool|int|string|decimal
		)
	 */
	abstract public function get_schema_config();

	/**
	 * Specify column config from the source table.
	 *
	 * @return array Config, must be of the form:
	 * array(
	 *  '$source_column_name_1' => array( // $source_column_name_1 is column name in source table, or a select statement.
	 *      'type' => 'type of value, could be string/int/date/float.',
	 *      'destination' => 'name of the column in column name where this data should be inserted in.',
	 *  ),
	 *  '$source_column_name_2' => array(
	 *          ......
	 *  ),
	 *  ....
	 * ).
	 */
	abstract public function get_core_column_mapping();

	/**
	 * Specify meta keys config from source meta table.
	 *
	 * @return array Config, must be of the form.
	 * array(
	 *  '$meta_key_1' => array(  // $meta_key_1 is the name of meta_key in source meta table.
	 *          'type' => 'type of value, could be string/int/date/float',
	 *          'destination' => 'name of the column in column name where this data should be inserted in.',
	 *  ),
	 *  '$meta_key_2' => array(
	 *          ......
	 *  ),
	 *  ....
	 * ).
	 */
	abstract public function get_meta_column_config();

	/**
	 * Generate SQL for data insertion.
	 *
	 * @param array $batch Data to generate queries for. Will be 'data' array returned by `$this->fetch_data_for_migration_for_ids()` method.
	 *
	 * @return string Generated queries for insertion for this batch, would be of the form:
	 * INSERT IGNORE INTO $table_name ($columns) values
	 *  ($value for row 1)
	 *  ($value for row 2)
	 * ...
	 */
	public function generate_insert_sql_for_batch( $batch ) {
		$table = $this->schema_config['destination']['table_name'];

		list( $value_sql, $column_sql ) = $this->generate_column_clauses( array_merge( $this->core_column_mapping, $this->meta_column_mapping ), $batch );

		return "INSERT IGNORE INTO $table (`$column_sql`) VALUES $value_sql;"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, -- $insert_query is hardcoded, $value_sql is already escaped.
	}

	/**
	 * Generate SQL for data updating.
	 *
	 * @param array $batch Data to generate queries for. Will be `data` array returned by fetch_data_for_migration_for_ids() method.
	 *
	 * @param array $entity_row_mapping Maps rows to update data with their original IDs. Will be returned by `generate_update_sql_for_batch`.
	 *
	 * @return string Generated queries for batch update. Would be of the form:
	 * INSERT INTO $table ( $columns ) VALUES
	 *  ($value for row 1)
	 *  ($valye for row 2)
	 * ...
	 * ON DUPLICATE KEY UPDATE
	 * $column1 = VALUES($column1)
	 * $column2 = VALUES($column2)
	 * ...
	 */
	public function generate_update_sql_for_batch( $batch, $entity_row_mapping ) {
		$table = $this->schema_config['destination']['table_name'];

		$destination_primary_id_schema = $this->get_destination_table_primary_id_schema();
		foreach ( $batch as $entity_id => $row ) {
			$batch[ $entity_id ][ $destination_primary_id_schema['destination_primary_key']['destination'] ] = $entity_row_mapping[ $entity_id ]->destination_id;
		}

		list( $value_sql, $column_sql, $columns ) = $this->generate_column_clauses(
			array_merge( $destination_primary_id_schema, $this->core_column_mapping, $this->meta_column_mapping ),
			$batch
		);

		$duplicate_update_key_statement = $this->generate_on_duplicate_statement_clause( $columns );

		return "INSERT INTO $table (`$column_sql`) VALUES $value_sql $duplicate_update_key_statement;";
	}

	/**
	 * Generate schema for primary ID column of destination table.
	 *
	 * @return array[] Schema for primary ID column.
	 */
	protected function get_destination_table_primary_id_schema() {
		return array(
			'destination_primary_key' => array(
				'destination' => $this->schema_config['destination']['primary_key'],
				'type'        => $this->schema_config['destination']['primary_key_type'],
			),
		);
	}

	/**
	 * Generate values and columns clauses to be used in INSERT and INSERT..ON DUPLICATE KEY UPDATE statements.
	 *
	 * @param array $columns_schema Columns config for destination table.
	 * @param array $batch Actual data to migrate as returned by `data` in `fetch_data_for_migration_for_ids` method.
	 *
	 * @return array SQL clause for values, columns placeholders, and columns.
	 */
	protected function generate_column_clauses( $columns_schema, $batch ) {
		global $wpdb;

		$columns      = array();
		$placeholders = array();
		foreach ( $columns_schema as $prev_column => $schema ) {
			$columns[]      = $schema['destination'];
			$placeholders[] = MigrationHelper::get_wpdb_placeholder_for_type( $schema['type'] );
		}
		$placeholders = "'" . implode( "', '", $placeholders ) . "'";

		$values = array();
		foreach ( array_values( $batch ) as $row ) {
			$query_params = array();
			foreach ( $columns as $column ) {
				$query_params[] = $row[ $column ] ?? null;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $placeholders can only contain combination of placeholders described in MigrationHelper::get_wpdb_placeholder_for_type
			$value_string = '(' . $wpdb->prepare( $placeholders, $query_params ) . ')';
			$values[]     = $value_string;
		}

		$value_sql = implode( ',', $values );

		$column_sql = implode( '`, `', $columns );

		return array( $value_sql, $column_sql, $columns );
	}

	/**
	 * Generates ON DUPLICATE KEY UPDATE clause to be used in migration.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return string SQL clause for INSERT...ON DUPLICATE KEY UPDATE
	 */
	private function generate_on_duplicate_statement_clause( $columns ) {
		$update_value_statements = array();
		foreach ( $columns as $column ) {
			$update_value_statements[] = "$column = VALUES( $column )";
		}
		$update_value_clause = implode( ', ', $update_value_statements );

		return "ON DUPLICATE KEY UPDATE $update_value_clause";
	}

	/**
	 * Process next migration batch, uses option `wc_cot_migration` to checkpoints of what have been processed so far.
	 *
	 * @param array $entity_ids List of entity IDs to perform migrations for.
	 *
	 * @return array List of errors happened during migration.
	 */
	public function process_migration_batch_for_ids( $entity_ids ) {
		$data = $this->fetch_data_for_migration_for_ids( $entity_ids );

		foreach ( $data['errors'] as $entity_id => $error ) {
			$this->errors[ $entity_id ] = "Error in importing post id $entity_id: " . $error->get_message();
		}

		if ( count( $data['data'] ) === 0 ) {
			return array();
		}

		$entity_ids       = array_keys( $data['data'] );
		$already_migrated = $this->get_already_migrated_records( $entity_ids );

		$to_insert = array_diff_key( $data['data'], $already_migrated );
		$this->process_insert_batch( $to_insert );

		$to_update = array_intersect_key( $data['data'], $already_migrated );
		$this->process_update_batch( $to_update, $already_migrated );

		return array(
			'errors' => $this->errors,
		);
	}

	/**
	 * Process batch for insertion into destination table.
	 *
	 * @param array $batch Data to insert, will be of the form as returned by `data` in `fetch_data_for_migration_for_ids`.
	 */
	protected function process_insert_batch( $batch ) {
		global $wpdb;
		if ( 0 === count( $batch ) ) {
			return;
		}
		$queries = $this->generate_insert_sql_for_batch( $batch );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Queries should already be prepared.
		$result = $wpdb->query( $queries );
		$wpdb->query( 'COMMIT;' ); // For some reason, this seems necessary on some hosts? Maybe a MySQL configuration?
		if ( count( $batch ) !== $result ) {
			$this->errors[] = 'Error with batch: ' . $wpdb->last_error;
		}
	}

	/**
	 * Process batch for update into destination table.
	 *
	 * @param array $batch Data to insert, will be of the form as returned by `data` in `fetch_data_for_migration_for_ids`.
	 * @param array $already_migrated Maps rows to update data with their original IDs.
	 */
	protected function process_update_batch( $batch, $already_migrated ) {
		global $wpdb;
		if ( 0 === count( $batch ) ) {
			return;
		}
		$queries = $this->generate_update_sql_for_batch( $batch, $already_migrated );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Queries should already be prepared.
		$result = $wpdb->query( $queries );
		$wpdb->query( 'COMMIT;' ); // For some reason, this seems necessary on some hosts? Maybe a MySQL configuration?
		if ( count( $batch ) !== $result ) {
			$this->errors[] = 'Error with batch: ' . $wpdb->last_error;
		}
	}


	/**
	 * Fetch data for migration.
	 *
	 * @param array $entity_ids Entity IDs to fetch data for.
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
	public function fetch_data_for_migration_for_ids( $entity_ids ) {
		global $wpdb;

		if ( empty( $entity_ids ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}

		$entity_table_query = $this->build_entity_table_query( $entity_ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Output of $this->build_entity_table_query is already prepared.
		$entity_data = $wpdb->get_results( $entity_table_query );
		if ( empty( $entity_data ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}
		$entity_meta_rel_ids = array_column( $entity_data, 'entity_meta_rel_id' );

		$meta_table_query = $this->build_meta_data_query( $entity_meta_rel_ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Output of $this->build_meta_data_query is already prepared.
		$meta_data = $wpdb->get_results( $meta_table_query );

		return $this->process_and_sanitize_data( $entity_data, $meta_data );
	}

	/**
	 * Fetch id mappings for records that are already inserted, or can be considered duplicates.
	 *
	 * @param array $entity_ids List of entity IDs to verify.
	 *
	 * @return array Already migrated entities, would be of the form
	 * array(
	 *      '$source_id1' => array(
	 *          'source_id' => $source_id1,
	 *          'destination_id' => $destination_id1,
	 *      ),
	 *      ...
	 * )
	 */
	public function get_already_migrated_records( $entity_ids ) {
		global $wpdb;
		$source_table                   = $this->schema_config['source']['entity']['table_name'];
		$source_destination_join_column = $this->schema_config['source']['entity']['destination_rel_column'];
		$source_primary_key_column      = $this->schema_config['source']['entity']['primary_key'];

		$destination_table              = $this->schema_config['destination']['table_name'];
		$destination_source_join_column = $this->schema_config['destination']['source_rel_column'];
		$destination_primary_key_column = $this->schema_config['destination']['primary_key'];

		$entity_id_placeholder = implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) );

		$already_migrated_entity_ids = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- All columns and table names are hardcoded.
				"
SELECT source.`$source_primary_key_column` as source_id, destination.`$destination_primary_key_column` as destination_id
FROM `$destination_table` destination
JOIN `$source_table` source ON source.`$source_destination_join_column` = destination.`$destination_source_join_column`
WHERE source.`$source_primary_key_column` IN ( $entity_id_placeholder )
				",
				$entity_ids
			)
			// phpcs:enable
		);

		return array_column( $already_migrated_entity_ids, null, 'source_id' );
	}


	/**
	 * Helper method to build query used to fetch data from core source table.
	 *
	 * @param array $entity_ids List of entity IDs to fetch.
	 *
	 * @return string Query that can be used to fetch data.
	 */
	protected function build_entity_table_query( $entity_ids ) {
		global $wpdb;
		$source_entity_table       = $this->schema_config['source']['entity']['table_name'];
		$source_meta_rel_id_column = "`$source_entity_table`.`{$this->schema_config['source']['entity']['meta_rel_column']}`";
		$source_primary_key_column = "`$source_entity_table`.`{$this->schema_config['source']['entity']['primary_key']}`";

		$where_clause = "$source_primary_key_column IN (" . implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) ) . ')';
		$entity_keys  = array();
		foreach ( $this->core_column_mapping as $column_name => $column_schema ) {
			if ( isset( $column_schema['select_clause'] ) ) {
				$select_clause = $column_schema['select_clause'];
				$entity_keys[] = "$select_clause AS $column_name";
			} else {
				$entity_keys[] = "$source_entity_table.$column_name";
			}
		}
		$entity_column_string = implode( ', ', $entity_keys );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $source_meta_rel_id_column, $source_destination_rel_id_column etc is escaped for backticks. $where clause and $order_by should already be escaped.
		$query = $wpdb->prepare(
			"
SELECT
	$source_meta_rel_id_column as entity_meta_rel_id,
	$entity_column_string
FROM `$source_entity_table`
WHERE $where_clause;
",
			$entity_ids
		);

		// phpcs:enable

		return $query;
	}

	/**
	 * Helper method to build query that will be used to fetch data from source meta table.
	 *
	 * @param array $entity_ids List of IDs to fetch metadata for.
	 *
	 * @return string Query for fetching meta data.
	 */
	protected function build_meta_data_query( $entity_ids ) {
		global $wpdb;
		$meta_table                = $this->schema_config['source']['meta']['table_name'];
		$meta_keys                 = array_keys( $this->meta_column_mapping );
		$meta_key_column           = $this->schema_config['source']['meta']['meta_key_column'];
		$meta_value_column         = $this->schema_config['source']['meta']['meta_value_column'];
		$meta_table_relational_key = $this->schema_config['source']['meta']['entity_id_column'];

		$meta_column_string = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
		$entity_id_string   = implode( ', ', array_fill( 0, count( $entity_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $meta_table_relational_key, $meta_key_column, $meta_value_column and $meta_table is escaped for backticks. $entity_id_string and $meta_column_string are placeholders.
		$query = $wpdb->prepare(
			"
SELECT `$meta_table_relational_key` as entity_id, `$meta_key_column` as meta_key, `$meta_value_column` as meta_value
FROM `$meta_table`
WHERE
	`$meta_table_relational_key` IN ( $entity_id_string )
	AND `$meta_key_column` IN ( $meta_column_string );
",
			array_merge(
				$entity_ids,
				$meta_keys
			)
		);

		// phpcs:enable

		return $query;
	}

	/**
	 * Helper function to validate and combine data before we try to insert.
	 *
	 * @param array $entity_data Data from source table.
	 * @param array $meta_data Data from meta table.
	 *
	 * @return array[] Validated and combined data with errors.
	 */
	private function process_and_sanitize_data( $entity_data, $meta_data ) {
		$sanitized_entity_data = array();
		$error_records         = array();
		$this->process_and_sanitize_entity_data( $sanitized_entity_data, $error_records, $entity_data );
		$this->processs_and_sanitize_meta_data( $sanitized_entity_data, $error_records, $meta_data );

		return array(
			'data'   => $sanitized_entity_data,
			'errors' => $error_records,
		);
	}

	/**
	 * Helper method to sanitize core source table.
	 *
	 * @param array $sanitized_entity_data Array containing sanitized data for insertion.
	 * @param array $error_records Error records.
	 * @param array $entity_data Original source data.
	 */
	private function process_and_sanitize_entity_data( &$sanitized_entity_data, &$error_records, $entity_data ) {
		foreach ( $entity_data as $entity ) {
			$row_data = array();
			foreach ( $this->core_column_mapping as $column_name => $schema ) {
				$custom_table_column_name = $schema['destination'] ?? $column_name;
				$value                    = $entity->$column_name;
				$value                    = $this->validate_data( $value, $schema['type'] );
				if ( is_wp_error( $value ) ) {
					$error_records[ $entity->primary_key_id ][ $custom_table_column_name ] = $value->get_error_message();
				} else {
					$row_data[ $custom_table_column_name ] = $value;
				}
			}
			$sanitized_entity_data[ $entity->entity_meta_rel_id ] = $row_data;
		}
	}

	/**
	 * Helper method to sanitize soure meta data.
	 *
	 * @param array $sanitized_entity_data Array containing sanitized data for insertion.
	 * @param array $error_records Error records.
	 * @param array $meta_data Original source data.
	 */
	private function processs_and_sanitize_meta_data( &$sanitized_entity_data, &$error_records, $meta_data ) {
		foreach ( $meta_data as $datum ) {
			$column_schema = $this->meta_column_mapping[ $datum->meta_key ];
			$value         = $this->validate_data( $datum->meta_value, $column_schema['type'] );
			if ( is_wp_error( $value ) ) {
				$error_records[ $datum->entity_id ][ $column_schema['destination'] ] = "{$value->get_error_code()}: {$value->get_error_message()}";
			} else {
				$sanitized_entity_data[ $datum->entity_id ][ $column_schema['destination'] ] = $value;
			}
		}
	}

	/**
	 * Validate and transform data so that we catch as many errors as possible before inserting.
	 *
	 * @param mixed  $value Actual data value.
	 * @param string $type Type of data, could be decimal, int, date, string.
	 *
	 * @return float|int|mixed|string|\WP_Error
	 */
	private function validate_data( $value, $type ) {
		switch ( $type ) {
			case 'decimal':
				$value = (float) $value;
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'bool':
				$value = wc_string_to_bool( $value );
				break;
			case 'date':
				try {
					if ( '' === $value ) {
						$value = null;
					} else {
						$value = ( new \DateTime( $value ) )->format( 'Y-m-d H:i:s' );
					}
				} catch ( \Exception $e ) {
					return new \WP_Error( $e->getMessage() );
				}
				break;
			case 'date_epoch':
				try {
					if ( '' === $value ) {
						$value = null;
					} else {
						$value = ( new \DateTime( "@$value" ) )->format( 'Y-m-d H:i:s' );
					}
				} catch ( \Exception $e ) {
					return new \WP_Error( $e->getMessage() );
				}
				break;
		}

		return $value;
	}
}

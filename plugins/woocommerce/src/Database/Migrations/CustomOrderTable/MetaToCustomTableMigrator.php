<?php
/**
 * Generic migration class to move any entity, entity_meta table combination to custom table.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

/**
 * Class MetaToCustomTableMigrator.
 *
 * @package Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable
 */
class MetaToCustomTableMigrator {

	/**
	 * Config for tables being migrated and migrated from. See __construct() for detailed config.
	 *
	 * @var array
	 */
	private $schema_config;

	/**
	 * Meta config, see __construct for detailed config.
	 *
	 * @var array
	 */
	private $meta_column_mapping;

	/**
	 * Column mapping from source table to destination custom table. See __construct for detailed config.
	 *
	 * @var array
	 */
	private $core_column_mapping;

	/**
	 * Placeholders that we will use in building $wpdb queries.
	 *
	 * @var string[]
	 */
	private $wpdb_placeholder_for_type = array(
		'int'        => '%d',
		'decimal'    => '%f',
		'string'     => '%s',
		'date'       => '%s',
		'date_epoch' => '%s',
		'bool'       => '%d',
	);

	/**
	 * MetaToCustomTableMigrator constructor.
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
	 *
	 * @param array $meta_column_mapping Mapping information of keys in source meta table. Must be of the form:
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
	 *
	 * @param array $core_column_mapping Mapping of keys in source table, similar to meta_column_mapping param, must be of the form:
	 * array(
	 *  '$source_column_name_1' => array( // $source_column_name_1 is column name in source table.
	 *      'type' => 'type of value, could be string/int/date/float.',
	 *      'destination' => 'name of the column in column name where this data should be inserted in.',
	 *  ),
	 *  '$source_column_name_2' => array(
	 *          ......
	 *  ),
	 *  ....
	 * ).
	 */
	public function __construct( $schema_config, $meta_column_mapping, $core_column_mapping ) {
		// TODO: Add code to validate params.
		$this->schema_config       = $schema_config;
		$this->meta_column_mapping = $meta_column_mapping;
		$this->core_column_mapping = $core_column_mapping;
	}

	/**
	 * Generate SQL for data insertion.
	 *
	 * @param array  $batch Data to generate queries for. Will be 'data' array returned by `$this->fetch_data_for_migration()` method.
	 * @param string $insert_switch Insert command to use in generating queries, could be insert, insert_ignore, or replace.
	 *
	 * @return string Generated queries for insertion for this batch, would be of the form:
	 * INSERT/INSERT IGNORE/REPLACE INTO $table_name ($columns) values
	 *  ($value for row 1)
	 *  ($value for row 2)
	 * ...
	 */
	public function generate_insert_sql_for_batch( $batch, $insert_switch ) {
		global $wpdb;

		// TODO: Add code to validate params.
		$table = $this->schema_config['destination_table'];

		switch ( $insert_switch ) {
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

		$columns      = array();
		$placeholders = array();
		foreach ( array_merge( $this->core_column_mapping, $this->meta_column_mapping ) as $prev_column => $schema ) {
			$columns[]      = $schema['destination'];
			$placeholders[] = $this->wpdb_placeholder_for_type[ $schema['type'] ];
		}
		$placeholders = "'" . implode( "', '", $placeholders ) . "'";

		$values = array();
		foreach ( array_values( $batch ) as $row ) {
			$query_params = array();
			foreach ( $columns as $column ) {
				$query_params[] = $row[ $column ] ?? null;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $placeholders can only contain combination of placeholders described in $this->>wpdb_placeholder_for_type.
			$value_string = '(' . $wpdb->prepare( $placeholders, $query_params ) . ')';
			$values[]     = $value_string;
		}

		$value_sql = implode( ',', $values );

		$column_sql = implode( '`, `', $this->escape_backtick( $columns ) );

		return "$insert_query INTO $table (`$column_sql`) VALUES $value_sql;"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, -- $insert_query is hardcoded, $value_sql is already escaped.
	}

	/**
	 * Fetch data for migration.
	 *
	 * @param string $where_clause Where conditions to use while selecting data from source table.
	 * @param string $batch_size Batch size, will be used in LIMIT clause.
	 * @param string $order_by Will be used in ORDER BY clause.
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
	public function fetch_data_for_migration( $where_clause, $batch_size, $order_by ) {
		global $wpdb;

		// TODO: Add code to validate params.
		$entity_table_query = $this->build_entity_table_query( $where_clause, $batch_size, $order_by );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Output of $this->build_entity_table_query is already prepared.
		$entity_data = $wpdb->get_results( $entity_table_query );
		if ( empty( $entity_data ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}
		$entity_ids = array_column( $entity_data, 'entity_rel_column' );

		$meta_table_query = $this->build_meta_data_query( $entity_ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Output of $this->build_meta_data_query is already prepared.
		$meta_data = $wpdb->get_results( $meta_table_query );

		return $this->process_and_sanitize_data( $entity_data, $meta_data );
	}

	/**
	 * Helper method to build query used to fetch data from core source table.
	 *
	 * @param string $where_clause Where conditions to use while selecting data from source table.
	 * @param string $batch_size Batch size, will be used in LIMIT clause.
	 * @param string $order_by Will be used in ORDER BY clause.
	 *
	 * @return string Query that can be used to fetch data.
	 */
	private function build_entity_table_query( $where_clause, $batch_size, $order_by ) {
		global $wpdb;
		$entity_table      = $this->escape_backtick( $this->schema_config['entity_schema']['table_name'] );
		$primary_id_column = $this->escape_backtick( $this->schema_config['entity_schema']['primary_id'] );
		$entity_rel_column = $this->escape_backtick( $this->schema_config['entity_meta_relation']['entity_rel_column'] );
		$entity_keys       = array();
		foreach ( $this->core_column_mapping as $column_name => $column_schema ) {
			if ( isset( $column_schema['select_clause'] ) ) {
				$select_clause = $column_schema['select_clause'];
				$entity_keys[] = "$select_clause AS $column_name";
			} else {
				$entity_keys[] = '`' . $this->escape_backtick( $column_name ) . '`';
			}
		}
		$entity_column_string = implode( ', ', $entity_keys );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $entity_table, $primary_id_column and $entity_column_string is escaped for backticks. $where clause and $order_by should already be escaped.
		$query = $wpdb->prepare(
			"
SELECT `$primary_id_column` as primary_key_id, `$entity_rel_column` AS entity_rel_column, $entity_column_string FROM $entity_table WHERE $where_clause ORDER BY $order_by LIMIT %d;
",
			array(
				$batch_size,
			)
		);

		// phpcs:enable

		return $query;
	}

	/**
	 * Helper method to escape backtick in column and table names.
	 * WP does not provide a method to escape table/columns names yet, but hopefully soon in @link https://core.trac.wordpress.org/ticket/52506
	 *
	 * @param string|array $identifier Column or table name.
	 *
	 * @return array|string|string[] Escaped identifier.
	 */
	private function escape_backtick( $identifier ) {
		return str_replace( '`', '``', $identifier );
	}

	/**
	 * Helper method to build query that will be used to fetch data from source meta table.
	 *
	 * @param array $entity_ids List of IDs to fetch metadata for.
	 *
	 * @return string|void Query for fetching meta data.
	 */
	private function build_meta_data_query( $entity_ids ) {
		global $wpdb;
		$meta_table                = $this->escape_backtick( $this->schema_config['entity_meta_schema']['table_name'] );
		$meta_keys                 = array_keys( $this->meta_column_mapping );
		$meta_key_column           = $this->escape_backtick( $this->schema_config['entity_meta_schema']['meta_key_column'] );
		$meta_value_column         = $this->escape_backtick( $this->schema_config['entity_meta_schema']['meta_value_column'] );
		$meta_table_relational_key = $this->escape_backtick( $this->schema_config['entity_meta_relation']['meta_rel_column'] );

		$meta_column_string = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
		$entity_id_string   = implode( ', ', array_fill( 0, count( $entity_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $meta_table_relational_key, $meta_key_column, $meta_value_column and $meta_table is escaped for backticks. $entity_id_string and $meta_column_string are placeholders.
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
		/**
		 * TODO: Add more validations for:
		 * 1. Column size
		 * 2. Value limits
		 */
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
			$sanitized_entity_data[ $entity->primary_key_id ] = $row_data;
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
				// TODO: Test this validation in unit tests.
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

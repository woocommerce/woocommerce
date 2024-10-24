<?php

namespace Automattic\WooCommerce\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for snapshot controllers.
 */
abstract class SnapshotsControllerBase {

	/**
	 * @var array|null Format strings for the snapshots table columns, to use with $wpdb functions.
	 */
	private ?array $snapshot_table_formats;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		global $wpdb;

		$this->snapshot_table_formats = $this->get_snapshot_table_formats();
		if ( is_null( $this->snapshot_table_formats ) ) {
			$column_count                 = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(1) FROM information_schema.columns WHERE table_name = %s',
					$this->get_table_name()
				)
			);
			$this->snapshot_table_formats = array_fill( 0, $column_count - 3, '%s' );
		}

		// Add formats for the common columns (entity id, creation date and data checksum).
		$this->snapshot_table_formats[] = '%d';
		$this->snapshot_table_formats[] = '%s';
		$this->snapshot_table_formats[] = '%s';
	}

	/**
	 * Gets the type of entities whose snapshots are stored.
	 *
	 * @return string
	 */
	abstract public function get_entity_type() : string;

	/**
	 * Gets the name of the database table where snapshots are stored.
	 *
	 * @return string
	 */
	public function get_table_name() : string {
		global $wpdb;

		return $wpdb->prefix . 'wc_' . $this->get_entity_type() . '_snapshots';
	}

	/**
	 * Gets the format strings for the snapshots table columns, to use with $wpdb functions,
	 * not including the common columns (entity id, creation date and data checksum).
	 *
	 * If null is returned, an array composed of as many '%s' as the column count
	 * of the database table minus 3 will be used.
	 *
	 * @return array|null
	 */
	protected function get_snapshot_table_formats(): ?array {
		return null;
	}

	/**
	 * Extract the id from an entity to be "snapshoted".
	 *
	 * @param object|array $entity
	 * @return int
	 */
	protected function get_id_from_entity( $entity ) : int {
		if ( is_object( $entity ) ) {
			return $entity->get_id();
		} else {
			return $entity['id'];
		}
	}

	/**
	 * Do any required processing to the snapshot data before being stored in the database.
	 *
	 * The passed data array will be the one returned by get_snapshot_data_from_entity,
	 * and after any processing it must end up being an associative array with one item per database column,
	 * where the keys are the column names (not including the common columns: entity id, creation date and data checksum).
	 *
	 * @param array $snapshot_data
	 * @return void
	 */
	protected function process_snapshot_data_for_db( array &$snapshot_data ): void {
	}

	/**
	 * Do any required processing to the snapshot data after being retrieved from the database.
	 *
	 * The passed data array will be the one obtained from the database table, not including the common columns
	 * (entity id, creation date and data checksum); after the processing it will be either returned directly
	 * to the caller (of get_snapshot or get_existing_snapshots) or passed to get_object_from_snapshot_data,
	 * depending on the value of the $return_data_as_object argument of these methods.
	 *
	 * @param int   $entity_id
	 * @param array $snapshot_data
	 * @return void
	 */
	protected function unprocess_snapshot_data_from_db( int $entity_id, array &$snapshot_data ): void {
	}

	/**
	 * Extract the necessary data to generate a snapshot from an entity.
	 *
	 * @param array|object $entity
	 * @return array
	 */
	abstract protected function get_snapshot_data_from_entity( $entity ) : array;

	/**
	 * Regenerate an entity from its entity id and a set of snapshot data.
	 *
	 * @param int   $entity_id
	 * @param array $snapshot_data
	 * @return object
	 */
	abstract protected function get_object_from_snapshot_data( int $entity_id, array $snapshot_data ) : object;

	/**
	 * Create a new snapshot for a given entity, only if no snapshot already exists having the same entity id
	 * and snapshot data checksum.
	 *
	 * @param object|array $entity The entity to maybe create a snapshot from.
	 * @param int|null     $entity_id The entity id, or null to get it using get_id_from_entity.
	 * @param string       $date_created The value for the 'date_created_gmt' field of the snapshot, or 'now' to use the current system time (used only if a new snapshot is created).
	 * @return array An associative array containing the following keys: entity_id, data_checksum, new_snapshot_created (boolean).
	 */
	public function maybe_create_snapshot( $entity, ?int $entity_id = null, $date_created = 'now' ) : array {
		global $wpdb;

		$entity_id   ??= $this->get_id_from_entity( $entity );
		$snapshot_data = $this->get_snapshot_data_from_entity( $entity );
		$this->process_snapshot_data_for_db( $snapshot_data );
		$snapshot_data_checksum = $this->calculate_checksum( $snapshot_data );

		$snapshot_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT EXISTS( SELECT 1 FROM {$this->get_table_name()} WHERE entity_id = %d AND data_checksum = %s )",
				$entity_id,
				$snapshot_data_checksum
			)
		);

		if ( ! $snapshot_exists ) {
			$snapshot_data['entity_id']        = $entity_id;
			$snapshot_data['date_created_gmt'] = 'now' === $date_created ? current_time( 'mysql', true ) : $date_created;
			$snapshot_data['data_checksum']    = $snapshot_data_checksum;

			$success = $wpdb->insert(
				$this->get_table_name(),
				$snapshot_data,
				$this->snapshot_table_formats
			);

			if ( ! $success ) {
				throw new \Exception( "Snapshot creation failed for entity of type '{$this->get_entity_type()}' and id $entity_id" );
			}
		}

		return array(
			'entity_id'             => $entity_id,
			'data_checksum'         => $snapshot_data_checksum,
			'new_snapshot_created ' => ! $snapshot_exists,
		);
	}

	/**
	 * Calculate the checksum for a set of snapshot data.
	 * These checksums will be used to uniquely identify existing snapshots.
	 *
	 * @param array $data
	 * @return string
	 */
	protected function calculate_checksum( array $data ): string {
		return hash( 'md4', serialize( $data ) );
	}

	/**
	 * Get a snapshot for a given entity.
	 *
	 * @param int         $entity_id The id of the entity to get the snapshot from.
	 * @param bool|null   $return_data_as_object True: convert the returned snapshot data to an object using get_object_from_snapshot_data; false: return raw snapshot data; null: don't return any snapshot data.
	 * @param string|null $data_checksum Checksum that identifies the snapshot data to retrieve, or null to retrieve the most recent snapshot available for the entity.
	 * @return array|null Null if no matches are found, or an associative array contaiing these keys: 'entity_id', 'created_at', 'data_checksum'; and if $return_data_as_object is not null, also 'data' (raw snapshot data or the regenerated entity object).
	 */
	public function get_snapshot( int $entity_id, ?bool $return_data_as_object = false, ?string $data_checksum = null ) {
		global $wpdb;

		$fields = is_null( $return_data_as_object ) ? 'entity_id,date_created_gmt,data_checksum' : '*';

		$db_row = $wpdb->get_row(
			is_null( $data_checksum ) ?
				$wpdb->prepare(
					"SELECT $fields FROM {$this->get_table_name()} WHERE entity_id = %d ORDER BY date_created_gmt DESC LIMIT 1",
					$entity_id,
				) :
				$wpdb->prepare(
					"SELECT $fields FROM {$this->get_table_name()} WHERE entity_id = %d AND data_checksum = %s LIMIT 1",
					$entity_id,
					$data_checksum
				),
			ARRAY_A
		);

		return $this->convert_db_row_to_returned_data( $db_row, $return_data_as_object );
	}

	/**
	 * Get a list of the entity ids stored in the snapshots table.
	 *
	 * @param int  $page Zero-based page, of size $page_size, of the results to return.
	 * @param int  $page_size How many results to return at most.
	 * @param bool $order_desc True to order the results descending, false to order the results ascending.
	 * @return array
	 */
	public function get_existing_ids( int $page = 0, int $page_size = 100, bool $order_desc = true ) : array {
		global $wpdb;

		$order  = $order_desc ? 'DESC' : 'ASC';
		$offset = $page_size * $page;
		$data   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT entity_id FROM {$this->get_table_name()} ORDER BY entity_id $order LIMIT $offset,$page_size",
			)
		);

		return array_map( 'absint', $data );
	}

	/**
	 * Get a list of the existing snapshots for a given entity id, sorted by creation date.
	 *
	 * @param int       $entity_id
	 * @param bool|null $return_data_as_object True: convert the returned snapshot data to an object using get_object_from_snapshot_data; false: return raw snapshot data; null: don't return any snapshot data.
	 * @param bool      $order_desc True to order the results descending, false to order the results ascending.
	 * @param int       $page Zero-based page, of size $page_size, of the results to return.
	 * @param int       $page_size True to order the results descending, false to order the results ascending.
	 * @return array An array of snapshots, each item will have the same format as the data returned by get_snapshot.
	 */
	public function get_existing_snapshots(
		int $entity_id,
		?bool $return_data_as_object = false,
		bool $order_desc = true,
		int $page = 0,
		int $page_size = 100
	) : array {

		global $wpdb;

		$fields = is_null( $return_data_as_object ) ? 'entity_id,date_created_gmt,data_checksum' : '*';
		$order  = $order_desc ? 'DESC' : 'ASC';
		$offset = $page_size * $page;

		$db_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT $fields FROM {$this->get_table_name()} WHERE entity_id = $entity_id ORDER BY date_created_gmt $order LIMIT $offset,$page_size",
				$entity_id,
			),
			ARRAY_A
		);

		return array_map( fn( $row) => $this->convert_db_row_to_returned_data( $row, $return_data_as_object ), $db_rows );
	}

	/**
	 * Auxiliary method to convert a raw snapshot row retrieved from the database to a value returned in the 'data' element of the results array.
	 *
	 * @param array     $db_row
	 * @param bool|null $return_data_as_object
	 * @return array|null
	 */
	private function convert_db_row_to_returned_data( array $db_row, ?bool $return_data_as_object = false ): ?array {
		if ( ! $db_row ) {
			return null;
		}

		$entity_id = $db_row['entity_id'];

		$returned = array(
			'entity_id'     => absint( $entity_id ),
			'created_at'    => $db_row['date_created_gmt'],
			'data_checksum' => $db_row['data_checksum'],
		);

		if ( is_null( $return_data_as_object ) ) {
			return $returned;
		}

		unset( $db_row['entity_id'] );
		unset( $db_row['date_created_gmt'] );
		unset( $db_row['data_checksum'] );

		$this->unprocess_snapshot_data_from_db( $entity_id, $db_row );

		$returned['data'] =
			$return_data_as_object ?
				$this->get_object_from_snapshot_data( $entity_id, $db_row ) :
				$db_row;

		return $returned;
	}
}

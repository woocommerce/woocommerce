<?php
/**
 * LocationDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Locations;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for Location against the wc_locations table.
 *
 * @internal
 */
class LocationDataStore extends \WC_Data_Store_WP implements \WC_Object_Data_Store_Interface {

	/**
	 * Get the table name.
	 */
	private function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_locations';
	}

	/**
	 * Create a location row.
	 *
	 * @param Location $location Location object.
	 * @throws \Exception When the insert fails.
	 */
	public function create( &$location ): void {
		global $wpdb;

		if ( ! $location->get_date_created() ) {
			$location->set_date_created( time() );
		}
		$location->set_date_modified( time() );

		$inserted = $wpdb->insert(
			$this->get_table_name(),
			array(
				'name'              => $location->get_name(),
				'type'              => $location->get_type(),
				'address_1'         => $location->get_address_1(),
				'address_2'         => $location->get_address_2(),
				'city'              => $location->get_city(),
				'state'             => $location->get_state(),
				'postcode'          => $location->get_postcode(),
				'country'           => $location->get_country(),
				'date_created_gmt'  => $this->to_gmt_string( $location->get_date_created() ),
				'date_modified_gmt' => $this->to_gmt_string( $location->get_date_modified() ),
				'date_deleted_gmt'  => $this->to_gmt_string( $location->get_date_deleted() ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \Exception( esc_html__( 'Failed to create location.', 'woocommerce' ) );
		}

		$location->set_id( (int) $wpdb->insert_id );
		$location->apply_changes();
		$location->set_object_read( true );
	}

	/**
	 * Read a location row by id (soft-deleted rows are still readable by id).
	 *
	 * @param Location $location Location object.
	 * @throws \Exception When the row is missing.
	 */
	public function read( &$location ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$this->get_table_name(),
				$location->get_id()
			),
			ARRAY_A
		);

		if ( ! $row ) {
			$location->set_id( 0 );
			throw new \Exception( esc_html__( 'Invalid location.', 'woocommerce' ) );
		}

		$location->set_props(
			array(
				'name'          => $row['name'],
				'type'          => $row['type'],
				'address_1'     => $row['address_1'],
				'address_2'     => $row['address_2'],
				'city'          => $row['city'],
				'state'         => $row['state'],
				'postcode'      => $row['postcode'],
				'country'       => $row['country'],
				'date_created'  => $this->to_utc_timestamp( $row['date_created_gmt'] ),
				'date_modified' => $this->to_utc_timestamp( $row['date_modified_gmt'] ),
				'date_deleted'  => $this->to_utc_timestamp( $row['date_deleted_gmt'] ),
			)
		);
		$location->set_object_read( true );
	}

	/**
	 * Update a location row.
	 *
	 * @param Location $location Location object.
	 */
	public function update( &$location ): void {
		global $wpdb;

		$location->set_date_modified( time() );

		$wpdb->update(
			$this->get_table_name(),
			array(
				'name'              => $location->get_name(),
				'type'              => $location->get_type(),
				'address_1'         => $location->get_address_1(),
				'address_2'         => $location->get_address_2(),
				'city'              => $location->get_city(),
				'state'             => $location->get_state(),
				'postcode'          => $location->get_postcode(),
				'country'           => $location->get_country(),
				'date_modified_gmt' => $this->to_gmt_string( $location->get_date_modified() ),
				'date_deleted_gmt'  => $this->to_gmt_string( $location->get_date_deleted() ),
			),
			array( 'id' => $location->get_id() ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		$location->apply_changes();
	}

	/**
	 * Soft-delete a location. force_delete is intentionally ignored; locations are soft-delete only.
	 *
	 * @param Location $location Location object.
	 * @param array    $args     Unused; present for interface compatibility.
	 * @return bool True on success.
	 */
	public function delete( &$location, $args = array() ): bool {
		global $wpdb;

		$now = time();
		$location->set_date_deleted( $now );
		$location->set_date_modified( $now );

		$result = $wpdb->update(
			$this->get_table_name(),
			array(
				'date_modified_gmt' => $this->to_gmt_string( $location->get_date_modified() ),
				'date_deleted_gmt'  => $this->to_gmt_string( $location->get_date_deleted() ),
			),
			array( 'id' => $location->get_id() ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$location->apply_changes();
		return false !== $result;
	}

	/**
	 * Whether an active (not soft-deleted) location with this id exists.
	 *
	 * @param int $id Location id.
	 */
	public function is_active_location( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM %i WHERE id = %d AND date_deleted_gmt IS NULL LIMIT 1',
				$this->get_table_name(),
				$id
			)
		);
	}

	/**
	 * Get the ids of all active (not soft-deleted) locations.
	 *
	 * @return int[]
	 */
	public function get_location_ids(): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE date_deleted_gmt IS NULL ORDER BY id ASC',
				$this->get_table_name()
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Convert a stored GMT datetime string to a UTC timestamp.
	 *
	 * @param string|null $value GMT 'Y-m-d H:i:s' string.
	 */
	private function to_utc_timestamp( $value ): ?int {
		if ( empty( $value ) ) {
			return null;
		}
		$timestamp = wc_string_to_timestamp( (string) $value );
		return $timestamp ? $timestamp : null;
	}

	/**
	 * Format a date prop as a GMT datetime string for storage.
	 *
	 * @param \WC_DateTime|null $date Date prop value.
	 */
	private function to_gmt_string( $date ): ?string {
		return $date instanceof \WC_DateTime ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
	}

	/*
	 * Locations have no meta table. These no-ops override WC_Data_Store_WP so Location meta can
	 * never route to the shared postmeta table. Add a wc_location_meta table here if meta is needed.
	 */

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param Location $location Location object.
	 * @return array
	 */
	public function read_meta( &$location ): array {
		return array();
	}

	/**
	 * Add new piece of meta.
	 *
	 * @param Location  $location Location object.
	 * @param \stdClass $meta (containing ->key and ->value).
	 * @return int|false Locations do not support meta; always false.
	 */
	public function add_meta( &$location, $meta ) {
		return false;
	}

	/**
	 * Update meta.
	 *
	 * @param Location  $location Location object.
	 * @param \stdClass $meta (containing ->id, ->key and ->value).
	 */
	public function update_meta( &$location, $meta ): void {
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param Location  $location Location object.
	 * @param \stdClass $meta (containing at least ->id).
	 * @return array Locations do not support meta; always empty.
	 */
	public function delete_meta( &$location, $meta ): array {
		return array();
	}
}

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

		$created = $location->get_date_created();
		if ( empty( $created ) ) {
			$created = gmdate( 'Y-m-d H:i:s' );
			$location->set_date_created( $created );
		}

		$inserted = $wpdb->insert(
			$this->get_table_name(),
			array(
				'name'           => $location->get_name(),
				'type'           => $location->get_type(),
				'address_1'      => $location->get_address_1(),
				'address_2'      => $location->get_address_2(),
				'city'           => $location->get_city(),
				'state'          => $location->get_state(),
				'postcode'       => $location->get_postcode(),
				'country'        => $location->get_country(),
				'created_at_gmt' => $created,
				'deleted_at_gmt' => $location->get_date_deleted() ? $location->get_date_deleted() : null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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
				'name'         => $row['name'],
				'type'         => $row['type'],
				'address_1'    => $row['address_1'],
				'address_2'    => $row['address_2'],
				'city'         => $row['city'],
				'state'        => $row['state'],
				'postcode'     => $row['postcode'],
				'country'      => $row['country'],
				'date_created' => $row['created_at_gmt'],
				'date_deleted' => $row['deleted_at_gmt'],
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

		$wpdb->update(
			$this->get_table_name(),
			array(
				'name'           => $location->get_name(),
				'type'           => $location->get_type(),
				'address_1'      => $location->get_address_1(),
				'address_2'      => $location->get_address_2(),
				'city'           => $location->get_city(),
				'state'          => $location->get_state(),
				'postcode'       => $location->get_postcode(),
				'country'        => $location->get_country(),
				'deleted_at_gmt' => $location->get_date_deleted() ? $location->get_date_deleted() : null,
			),
			array( 'id' => $location->get_id() ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		$location->apply_changes();
	}

	/**
	 * Soft delete a location.
	 *
	 * Declares a `bool` return (unlike the brief's `void`) because
	 * `WC_Object_Data_Store_Interface::delete()` documents a `bool` return;
	 * a `void` (or undeclared) return trips PHPStan's childReturnType check
	 * at level 8, and the baseline must not grow to accommodate it.
	 *
	 * force_delete is intentionally NOT honored — locations are soft-delete only
	 * (matching FulfillmentsDataStore).
	 *
	 * @param Location $location Location object.
	 * @param array    $args     Unused. Present to satisfy WC_Object_Data_Store_Interface.
	 * @return bool True on success, false on failure.
	 */
	public function delete( &$location, $args = array() ): bool {
		global $wpdb;

		$deleted_at = gmdate( 'Y-m-d H:i:s' );
		$result     = $wpdb->update(
			$this->get_table_name(),
			array(
				'deleted_at_gmt' => $deleted_at,
			),
			array( 'id' => $location->get_id() ),
			array( '%s' ),
			array( '%d' )
		);
		$location->set_date_deleted( $deleted_at );
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
				'SELECT 1 FROM %i WHERE id = %d AND deleted_at_gmt IS NULL LIMIT 1',
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
				'SELECT id FROM %i WHERE deleted_at_gmt IS NULL ORDER BY id ASC',
				$this->get_table_name()
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Locations do not support metadata (there is no wc_location_meta table). These override the
	 * inherited WC_Data_Store_WP meta methods so Location meta can never route to the shared postmeta
	 * table. If location meta is needed later, add a wc_location_meta table + proper $meta_type/get_db_info
	 * and remove these no-ops.
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

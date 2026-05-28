<?php
/**
 * ActorAccessRepository class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StoreActors;

defined( 'ABSPATH' ) || exit;

/**
 * Persistence for store actor access rows (wc_store_actor_access).
 *
 * Each row represents one actor's access in a given (context, location).
 * For the POC, only context='pos', location_id=0 is written. The schema
 * admits future contexts and location_id values without migration.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class ActorAccessRepository {

	public const CONTEXT_POS    = 'pos';
	public const LOCATION_NONE  = 0;
	public const STATUS_ACTIVE  = 'active';
	public const STATUS_INACTIVE = 'inactive';

	public const CREDENTIAL_TYPE_PIN = 'pin';

	/**
	 * Fully-qualified table name.
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_store_actor_access';
	}

	/**
	 * Fetch the access row for an actor in a given context/location.
	 *
	 * @param int    $actor_id    Actor ID.
	 * @param string $context     Context (default 'pos').
	 * @param int    $location_id Location ID (default 0).
	 * @return array<string, mixed>|null
	 */
	public function get_for_actor( int $actor_id, string $context = self::CONTEXT_POS, int $location_id = self::LOCATION_NONE ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->get_table_name()
					. ' WHERE actor_id = %d AND context = %s AND location_id = %d LIMIT 1',
				$actor_id,
				$context,
				$location_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Fetch all access rows for an actor across contexts/locations.
	 *
	 * @param int $actor_id Actor ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_actor( int $actor_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->get_table_name() . ' WHERE actor_id = %d ORDER BY access_id ASC',
				$actor_id
			),
			ARRAY_A
		);
		return $rows ?: array();
	}

	/**
	 * List active access rows for a given context, joined-friendly format.
	 *
	 * Returns one row per (actor_id, context) where status = active.
	 * The caller is expected to join against actors to filter on actor status.
	 *
	 * @param string $context Context.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_active_for_context( string $context = self::CONTEXT_POS ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->get_table_name()
					. ' WHERE context = %s AND status = %s'
					. ' ORDER BY access_id ASC',
				$context,
				self::STATUS_ACTIVE
			),
			ARRAY_A
		);
		return $rows ?: array();
	}

	/**
	 * Insert a new access row.
	 *
	 * @param array<string, mixed> $data Row fields.
	 * @return int New access_id, or 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;
		$now = gmdate( 'Y-m-d H:i:s' );

		$row = array(
			'actor_id'              => (int) ( $data['actor_id'] ?? 0 ),
			'context'               => (string) ( $data['context'] ?? self::CONTEXT_POS ),
			'location_id'           => (int) ( $data['location_id'] ?? self::LOCATION_NONE ),
			'access_profile_key'    => (string) ( $data['access_profile_key'] ?? '' ),
			'status'                => $data['status'] ?? self::STATUS_ACTIVE,
			'credential_type'       => $data['credential_type'] ?? null,
			'credential_algo'       => $data['credential_algo'] ?? null,
			'credential_iterations' => isset( $data['credential_iterations'] ) ? (int) $data['credential_iterations'] : null,
			'credential_salt'       => $data['credential_salt'] ?? null,
			'credential_hash'       => $data['credential_hash'] ?? null,
			'credential_updated_at' => $data['credential_updated_at'] ?? null,
			'created_by_user_id'    => isset( $data['created_by_user_id'] ) ? (int) $data['created_by_user_id'] : null,
			'updated_by_user_id'    => isset( $data['updated_by_user_id'] ) ? (int) $data['updated_by_user_id'] : null,
			'date_created_gmt'      => $data['date_created_gmt'] ?? $now,
			'date_updated_gmt'      => $data['date_updated_gmt'] ?? $now,
		);

		$inserted = $wpdb->insert( $this->get_table_name(), $row );
		if ( false === $inserted ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an access row in place.
	 *
	 * @param int                  $access_id Access ID.
	 * @param array<string, mixed> $data      Fields to set.
	 * @return bool True on success.
	 */
	public function update( int $access_id, array $data ): bool {
		global $wpdb;

		$allowed = array(
			'access_profile_key',
			'status',
			'credential_type',
			'credential_algo',
			'credential_iterations',
			'credential_salt',
			'credential_hash',
			'credential_updated_at',
			'updated_by_user_id',
		);

		$row = array_intersect_key( $data, array_flip( $allowed ) );
		if ( empty( $row ) ) {
			return false;
		}

		$row['date_updated_gmt'] = gmdate( 'Y-m-d H:i:s' );

		$result = $wpdb->update(
			$this->get_table_name(),
			$row,
			array( 'access_id' => $access_id )
		);

		return false !== $result;
	}

	/**
	 * Delete the access row for an actor in a context/location.
	 *
	 * @param int    $actor_id    Actor ID.
	 * @param string $context     Context.
	 * @param int    $location_id Location ID.
	 * @return bool
	 */
	public function delete_for_actor( int $actor_id, string $context = self::CONTEXT_POS, int $location_id = self::LOCATION_NONE ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$this->get_table_name(),
			array(
				'actor_id'    => $actor_id,
				'context'     => $context,
				'location_id' => $location_id,
			)
		);
		return false !== $result;
	}

	/**
	 * Delete all access rows for an actor (typically alongside actor soft-delete).
	 *
	 * @param int $actor_id Actor ID.
	 * @return bool
	 */
	public function delete_all_for_actor( int $actor_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'actor_id' => $actor_id )
		);
		return false !== $result;
	}

	/**
	 * Clear credential material on the POS access row for an actor.
	 *
	 * @param int $actor_id Actor ID.
	 * @return bool
	 */
	public function clear_pos_credential( int $actor_id ): bool {
		global $wpdb;
		$result = $wpdb->update(
			$this->get_table_name(),
			array(
				'credential_type'       => null,
				'credential_algo'       => null,
				'credential_iterations' => null,
				'credential_salt'       => null,
				'credential_hash'       => null,
				'credential_updated_at' => null,
				'date_updated_gmt'      => gmdate( 'Y-m-d H:i:s' ),
			),
			array(
				'actor_id'    => $actor_id,
				'context'     => self::CONTEXT_POS,
				'location_id' => self::LOCATION_NONE,
			)
		);
		return false !== $result;
	}
}

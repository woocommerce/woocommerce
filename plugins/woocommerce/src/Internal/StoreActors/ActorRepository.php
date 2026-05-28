<?php
/**
 * ActorRepository class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StoreActors;

defined( 'ABSPATH' ) || exit;

/**
 * Persistence for store actors (wc_store_actors).
 *
 * A store actor is any person who acts on the store — could be a WordPress user
 * (admin, shop_manager), could be a POS-only operator with no WP account, and
 * later could be a non-POS employee. The wp_user_id link is optional.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class ActorRepository {

	public const STATUS_ACTIVE   = 'active';
	public const STATUS_INACTIVE = 'inactive';

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_store_actors';
	}

	/**
	 * Returns the CREATE TABLE statements for the store-actor identity table
	 * and its sibling access table. WC_Install pulls this in via the container
	 * and concatenates it into the global dbDelta schema.
	 *
	 * Schema is paired (actors + access) because the two tables are always
	 * installed together; the access satellite has no meaning without an actor.
	 *
	 * @return string
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$actors_table = $this->get_table_name();
		$access_table = $wpdb->prefix . 'wc_store_actor_access';

		return "
CREATE TABLE $actors_table (
  actor_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  actor_uuid char(36) NOT NULL,
  wp_user_id bigint(20) unsigned DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  display_name varchar(200) NOT NULL,
  first_name varchar(100) DEFAULT NULL,
  last_name varchar(100) DEFAULT NULL,
  email varchar(320) DEFAULT NULL,
  created_by_user_id bigint(20) unsigned DEFAULT NULL,
  updated_by_user_id bigint(20) unsigned DEFAULT NULL,
  date_created_gmt datetime NOT NULL,
  date_updated_gmt datetime NOT NULL,
  date_deleted_gmt datetime DEFAULT NULL,
  PRIMARY KEY  (actor_id),
  UNIQUE KEY actor_uuid (actor_uuid),
  KEY wp_user_id (wp_user_id),
  KEY status (status)
) $collate;
CREATE TABLE $access_table (
  access_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  actor_id bigint(20) unsigned NOT NULL,
  context varchar(32) NOT NULL,
  location_id bigint(20) unsigned NOT NULL DEFAULT 0,
  access_profile_key varchar(64) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  credential_type varchar(32) DEFAULT NULL,
  credential_algo varchar(50) DEFAULT NULL,
  credential_iterations int unsigned DEFAULT NULL,
  credential_salt varchar(255) DEFAULT NULL,
  credential_hash varchar(255) DEFAULT NULL,
  credential_updated_at datetime DEFAULT NULL,
  created_by_user_id bigint(20) unsigned DEFAULT NULL,
  updated_by_user_id bigint(20) unsigned DEFAULT NULL,
  date_created_gmt datetime NOT NULL,
  date_updated_gmt datetime NOT NULL,
  PRIMARY KEY  (access_id),
  UNIQUE KEY actor_context_location (actor_id, context, location_id),
  KEY context_profile_status (context, access_profile_key, status),
  KEY location_context_status (location_id, context, status)
) $collate;
";
	}

	/**
	 * Fetch a single actor row by ID.
	 *
	 * @param int $actor_id Actor ID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( int $actor_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name() . ' WHERE actor_id = %d', $actor_id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Fetch a single actor row by UUID.
	 *
	 * @param string $actor_uuid Actor UUID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_uuid( string $actor_uuid ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name() . ' WHERE actor_uuid = %s', $actor_uuid ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Fetch the actor linked to a given WP user, if any.
	 *
	 * @param int $wp_user_id WordPress user ID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_wp_user_id( int $wp_user_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->get_table_name() . ' WHERE wp_user_id = %d AND date_deleted_gmt IS NULL LIMIT 1',
				$wp_user_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * List active, not-soft-deleted actors.
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_active( int $limit = 100, int $offset = 0 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->get_table_name()
					. " WHERE status = %s AND date_deleted_gmt IS NULL"
					. ' ORDER BY actor_id ASC LIMIT %d OFFSET %d',
				self::STATUS_ACTIVE,
				$limit,
				$offset
			),
			ARRAY_A
		);
		return $rows ?: array();
	}

	/**
	 * Insert a new actor.
	 *
	 * Caller is responsible for input sanitization. `actor_uuid` is generated
	 * here if not supplied. Returns the new actor_id, or 0 on failure.
	 *
	 * @param array<string, mixed> $data Actor fields.
	 * @return int
	 */
	public function insert( array $data ): int {
		global $wpdb;
		$now = gmdate( 'Y-m-d H:i:s' );

		$row = array(
			'actor_uuid'         => $data['actor_uuid'] ?? wp_generate_uuid4(),
			'wp_user_id'         => isset( $data['wp_user_id'] ) ? (int) $data['wp_user_id'] : null,
			'status'             => $data['status'] ?? self::STATUS_ACTIVE,
			'display_name'       => (string) ( $data['display_name'] ?? '' ),
			'first_name'         => $data['first_name'] ?? null,
			'last_name'          => $data['last_name'] ?? null,
			'email'              => $data['email'] ?? null,
			'created_by_user_id' => isset( $data['created_by_user_id'] ) ? (int) $data['created_by_user_id'] : null,
			'updated_by_user_id' => isset( $data['updated_by_user_id'] ) ? (int) $data['updated_by_user_id'] : null,
			'date_created_gmt'   => $data['date_created_gmt'] ?? $now,
			'date_updated_gmt'   => $data['date_updated_gmt'] ?? $now,
		);

		$inserted = $wpdb->insert( $this->get_table_name(), $row );
		if ( false === $inserted ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an actor row in place. Always refreshes date_updated_gmt.
	 *
	 * @param int                  $actor_id Actor ID.
	 * @param array<string, mixed> $data     Fields to set.
	 * @return bool True on success.
	 */
	public function update( int $actor_id, array $data ): bool {
		global $wpdb;

		$allowed = array(
			'wp_user_id',
			'status',
			'display_name',
			'first_name',
			'last_name',
			'email',
			'updated_by_user_id',
			'date_deleted_gmt',
		);

		$row = array_intersect_key( $data, array_flip( $allowed ) );
		if ( empty( $row ) ) {
			return false;
		}

		$row['date_updated_gmt'] = gmdate( 'Y-m-d H:i:s' );

		$result = $wpdb->update(
			$this->get_table_name(),
			$row,
			array( 'actor_id' => $actor_id )
		);

		return false !== $result;
	}

	/**
	 * Hard-delete an actor row by ID. Caller is responsible for removing
	 * associated rows (e.g. wc_store_actor_access) first to avoid orphans.
	 *
	 * Use this for atomic-create rollback (where the actor was inserted
	 * moments ago and the follow-up step failed). For everyday deletion
	 * from the merchant UI, use soft_delete() so historical order
	 * attribution can still resolve the actor's name.
	 *
	 * @param int $actor_id Actor ID.
	 * @return bool True if a row was deleted.
	 */
	public function delete( int $actor_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'actor_id' => $actor_id )
		);
		return false !== $result && $result > 0;
	}

	/**
	 * Soft-delete an actor: set date_deleted_gmt and status=inactive.
	 *
	 * @param int $actor_id Actor ID.
	 * @return bool
	 */
	public function soft_delete( int $actor_id ): bool {
		return $this->update(
			$actor_id,
			array(
				'status'           => self::STATUS_INACTIVE,
				'date_deleted_gmt' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Cascade handler for wp user deletion. Sets wp_user_id NULL and status
	 * inactive on any actor linked to the deleted WP user.
	 *
	 * @param int $wp_user_id Deleted WordPress user ID.
	 * @return int Rows affected.
	 */
	public function detach_wp_user( int $wp_user_id ): int {
		global $wpdb;
		$now    = gmdate( 'Y-m-d H:i:s' );
		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . $this->get_table_name()
					. ' SET wp_user_id = NULL, status = %s, date_updated_gmt = %s'
					. ' WHERE wp_user_id = %d',
				self::STATUS_INACTIVE,
				$now,
				$wp_user_id
			)
		);
		return (int) $result;
	}
}

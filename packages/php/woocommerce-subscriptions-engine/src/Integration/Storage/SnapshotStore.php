<?php
/**
 * Per-contract typed storage for cycle snapshot payloads. Does the one thing
 * copy-forward dedup leaves it - insert a typed snapshot row and return its id;
 * deciding when to reuse an existing id is the caller's job (repository / factory),
 * not this store's. There is no content hash or uniqueness constraint: copy-forward,
 * not a UNIQUE index, is what keeps identical consecutive snapshots from duplicating.
 *
 * A row is per contract and typed (`contract_id`, `snapshot_type`, a weak `parent_id`
 * link to the source, and a `schema_version` that is the payload FORMAT version, NOT
 * the plan's content version). Integration layer: JSON encoding happens here, never
 * in `Core\`. No foreign keys (MySQL 5.6 floor).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Per-contract typed snapshot store.
 */
final class SnapshotStore {

	const TYPE_PLAN  = 'plan';
	const TYPE_ITEMS = 'items';

	/**
	 * Insert a typed snapshot row and return its id. The payload (the value object's
	 * WP-free serialized form) is JSON-encoded here into the LONGTEXT column. No dedup
	 * at insert time - copy-forward reuse is the caller's job.
	 *
	 * @param int                      $contract_id    Owning contract id.
	 * @param string                   $snapshot_type  Snapshot type (`plan` | `items`).
	 * @param int|null                 $parent_id      Weak link to the source (the plan id for a plan snapshot; null for items).
	 * @param array<int|string, mixed> $payload        Snapshot payload to serialize.
	 * @param int                      $schema_version Payload-format version.
	 * @return int The inserted snapshot row id.
	 * @throws \RuntimeException If the payload cannot be encoded or the insert fails.
	 */
	public function insert( int $contract_id, string $snapshot_type, ?int $parent_id, array $payload, int $schema_version ): int {
		global $wpdb;

		$json = wp_json_encode( $payload );
		if ( false === $json ) {
			throw new \RuntimeException( 'Failed to JSON-encode snapshot payload.' );
		}

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$table,
			array(
				'contract_id'      => $contract_id,
				'snapshot_type'    => $snapshot_type,
				'parent_id'        => $parent_id,
				'schema_version'   => $schema_version,
				'payload'          => $json,
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert snapshot.' );
		}

		return (int) $wpdb->insert_id;
	}
}

<?php
/**
 * SnapshotStore - per-contract typed storage for cycle snapshot payloads.
 *
 * Cycles reference a plan snapshot and an items snapshot by id. Snapshots are
 * NOT content-addressed: a new cycle reuses the previous cycle's snapshot id
 * unless the plan/items actually changed (copy-forward dedup), so identical
 * consecutive cycles share a snapshot row by construction. This store therefore
 * does the one thing copy-forward leaves it: insert a typed snapshot row and
 * return its id. Deciding when to reuse an existing id instead of inserting is
 * the caller's job (the repository / factory), not this store's.
 *
 * A row is per contract and typed: `contract_id` scopes it, `snapshot_type`
 * (`plan` | `items`) labels it, `parent_id` is the weak link back to the source
 * (the plan a plan snapshot was taken from; null for items), and
 * `schema_version` is the payload-FORMAT version a reader parses/upcasts by - it
 * is explicitly NOT the plan's content version.
 *
 * Lives in the integration layer: JSON encoding happens here, never in `Core\`.
 * No foreign keys (MySQL 5.6 floor); there is no content hash and no
 * uniqueness constraint - copy-forward, not a UNIQUE index, is what keeps
 * identical consecutive snapshots from duplicating.
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
	 * Insert a typed snapshot row and return its id.
	 *
	 * The payload is the typed value object's serialized form (produced WP-free
	 * in `Core\`); it is JSON-encoded here into the row's LONGTEXT column. No
	 * dedup happens at insert time - copy-forward reuse is decided by the caller,
	 * which passes the prior cycle's snapshot id straight through when the
	 * plan/items are unchanged rather than calling this method.
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

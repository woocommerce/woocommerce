<?php
/**
 * ItemsSnapshot - an immutable, point-in-time copy of the line items a cycle was
 * billed for. Companion to {@see PlanSnapshot}, referenced by cycles by id.
 *
 * NOT content-addressed: identical consecutive item sets are shared by copy-forward,
 * so this VO carries no canonicalization or hashing. The item list is ordered and
 * kept as given. `schema_version` is the payload-FORMAT version (how to parse/upcast),
 * not a content version. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

use DomainException;

defined( 'ABSPATH' ) || exit;

/**
 * ItemsSnapshot value object.
 *
 * Immutable. Construct via {@see self::from_items()} (typed data) or
 * {@see self::from_payload()} (a serialized payload).
 */
final class ItemsSnapshot {

	/**
	 * The ordered line items, each a plain associative array.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $items;

	/**
	 * Payload-format version, recorded on the snapshot row. NOT a content version.
	 *
	 * @var int
	 */
	private $schema_version;

	/**
	 * Use {@see self::from_items()} or {@see self::from_payload()}.
	 *
	 * @param array<int, array<string, mixed>> $items          Ordered line items.
	 * @param int                              $schema_version Payload-format version.
	 * @throws DomainException If the schema version is not positive.
	 */
	private function __construct( array $items, int $schema_version ) {
		if ( $schema_version < 1 ) {
			throw new DomainException(
				sprintf( 'ItemsSnapshot: schema_version must be 1 or greater, got %d.', $schema_version )
			);
		}

		$this->items          = array_values( $items );
		$this->schema_version = $schema_version;
	}

	/**
	 * Build an items snapshot from an ordered list of items.
	 *
	 * @param array<int, array<string, mixed>> $items          Ordered line items.
	 * @param int                              $schema_version Payload-format version. Defaults to 1.
	 * @throws DomainException If the schema version is not positive.
	 */
	public static function from_items( array $items, int $schema_version = 1 ): self {
		return new self( $items, $schema_version );
	}

	/**
	 * Reconstruct an items snapshot from a serialized payload. Companion to
	 * {@see self::to_payload()}.
	 *
	 * @param array<int, array<string, mixed>> $payload        Serialized ordered line items.
	 * @param int                              $schema_version Payload-format version the payload was written in.
	 * @throws DomainException If the schema version is not positive.
	 */
	public static function from_payload( array $payload, int $schema_version = 1 ): self {
		return new self( $payload, $schema_version );
	}

	/**
	 * Payload-format version (NOT a content version).
	 */
	public function get_schema_version(): int {
		return $this->schema_version;
	}

	/**
	 * The line items, in their original order.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * The serialized payload for storage: the ordered line items, no
	 * canonicalization (dedup is by copy-forward, not a content hash).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function to_payload(): array {
		return $this->items;
	}
}

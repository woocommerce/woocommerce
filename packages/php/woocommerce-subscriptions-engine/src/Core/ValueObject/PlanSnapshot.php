<?php
/**
 * PlanSnapshot - an immutable, point-in-time copy of the plan terms a cycle was
 * billed (or is being billed) under.
 *
 * Cycles reference a plan snapshot by id. Snapshots are NOT content-addressed:
 * identical consecutive plan terms are shared by copy-forward (a new cycle reuses
 * the previous cycle's snapshot id unless the terms actually changed), so this VO
 * carries no canonicalization or hashing. It is the typed, in-memory form; the
 * Integration binding serializes {@see self::to_payload()} to its stored column
 * and reconstructs via {@see self::from_payload()}.
 *
 * The `schema_version` is the payload-FORMAT version: it tells a reader how to
 * parse and, if needed, upcast the payload. It is explicitly NOT the plan's
 * content version (a plan changing its terms does not bump this).
 *
 * Lives in `Core\`: WordPress-free, no encoding, no hashing.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * PlanSnapshot value object.
 *
 * Immutable. Construct via {@see self::from_array()} (typed data) or
 * {@see self::from_payload()} (a serialized payload).
 */
final class PlanSnapshot {

	use ScalarCoercion;

	/**
	 * The plan terms payload, as stored on the snapshot row.
	 *
	 * @var array<string, mixed>
	 */
	private $data;

	/**
	 * Payload-format version, recorded on the snapshot row. NOT the plan's content
	 * version - it tells a reader how to parse and upcast the payload.
	 *
	 * @var int
	 */
	private $schema_version;

	/**
	 * Use {@see self::from_array()} or {@see self::from_payload()}.
	 *
	 * @param array<string, mixed> $data           Plan terms payload.
	 * @param int                  $schema_version Payload-format version.
	 * @throws DomainException If the schema version is not positive.
	 */
	private function __construct( array $data, int $schema_version ) {
		if ( $schema_version < 1 ) {
			throw new DomainException(
				sprintf( 'PlanSnapshot: schema_version must be 1 or greater, got %d.', $schema_version )
			);
		}

		$this->data           = $data;
		$this->schema_version = $schema_version;
	}

	/**
	 * Build a plan snapshot from a payload array.
	 *
	 * @param array<string, mixed> $data           Plan terms payload.
	 * @param int                  $schema_version Payload-format version. Defaults to 1.
	 * @throws DomainException If the schema version is not positive.
	 */
	public static function from_array( array $data, int $schema_version = 1 ): self {
		return new self( $data, $schema_version );
	}

	/**
	 * Reconstruct a plan snapshot from a previously serialized payload.
	 *
	 * The companion to {@see self::to_payload()}: the Integration binding decodes
	 * the stored payload and its `schema_version` column and rebuilds the VO.
	 *
	 * @param array<string, mixed> $payload        Serialized plan terms payload.
	 * @param int                  $schema_version Payload-format version the payload was written in.
	 * @throws DomainException If the schema version is not positive.
	 */
	public static function from_payload( array $payload, int $schema_version = 1 ): self {
		return new self( $payload, $schema_version );
	}

	/**
	 * Payload-format version (NOT the plan's content version).
	 */
	public function get_schema_version(): int {
		return $this->schema_version;
	}

	/**
	 * The id of the plan these terms were snapshotted from, or null when absent.
	 *
	 * The snapshot's weak link back to its source plan. Read from the typed
	 * payload rather than by indexing the raw array at the call site, so a renamed
	 * or missing key surfaces here (as null) instead of silently breaking the link.
	 */
	public function get_selling_plan_id(): ?int {
		return isset( $this->data['selling_plan_id'] ) ? self::coerce_int( $this->data['selling_plan_id'] ) : null;
	}

	/**
	 * The plan terms payload, in its original key order.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return $this->data;
	}

	/**
	 * The serialized payload for storage.
	 *
	 * The Integration binding encodes this to the stored column. There is no key
	 * canonicalization: dedup is by copy-forward, not by a content hash, so the
	 * payload is the plan terms as given.
	 *
	 * @return array<string, mixed>
	 */
	public function to_payload(): array {
		return $this->data;
	}
}

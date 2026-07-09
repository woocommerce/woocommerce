<?php
/**
 * PlanSnapshot - an immutable, point-in-time copy of the plan terms a cycle was
 * billed under. Companion to {@see ItemsSnapshot}, referenced by cycles by id.
 *
 * NOT content-addressed: identical consecutive plan terms are shared by copy-forward,
 * so this VO carries no canonicalization or hashing. The typed in-memory form; the
 * Integration binding serializes via {@see self::to_payload()} and reconstructs via
 * {@see self::from_payload()}. `schema_version` is the payload-FORMAT version (how to
 * parse/upcast), not the plan's content version. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

use DomainException;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * PlanSnapshot value object.
 *
 * Immutable. Construct via {@see self::from_array()} (typed data) or
 * {@see self::from_payload()} (a serialized payload).
 */
final class PlanSnapshot {

	/**
	 * The plan terms payload, as stored on the snapshot row.
	 *
	 * @var array<string, mixed>
	 */
	private $data;

	/**
	 * Payload-format version, recorded on the snapshot row. NOT the plan's content
	 * version.
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
	 * Reconstruct a plan snapshot from a serialized payload. Companion to
	 * {@see self::to_payload()}.
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
	 * A weak link back to the source plan; a missing key surfaces here as null.
	 */
	public function get_selling_plan_id(): ?int {
		return isset( $this->data['selling_plan_id'] ) ? ScalarCoercion::coerce_int( $this->data['selling_plan_id'] ) : null;
	}

	/**
	 * The frozen billing cadence, reconstructed from the snapshot payload.
	 *
	 * Sourced from the `billing_policy` entry captured at signup, NOT the live plan -
	 * so a consumer reads the cadence a contract is billed under straight off the
	 * snapshot, even after the plan it came from is edited or deleted, with no live
	 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository}
	 * join. Returns null when the payload carries no (or an unreadable) billing policy,
	 * so a caller degrades to "no cadence" rather than fataling.
	 */
	public function get_billing_policy(): ?BillingPolicy {
		$policy = $this->data['billing_policy'] ?? null;
		if ( ! is_array( $policy ) ) {
			return null;
		}

		try {
			return BillingPolicy::from_array( self::string_keyed( $policy ) );
		} catch ( DomainException $e ) {
			// A structurally-invalid stored policy degrades to "no cadence" rather than
			// fataling the read; snapshots this engine writes always carry a valid policy.
			unset( $e );
			return null;
		}
	}

	/**
	 * The frozen pricing policy, reconstructed from the snapshot payload.
	 *
	 * Sourced from the `pricing_policy` entry captured at signup, NOT the live plan -
	 * the same frozen-terms contract as {@see self::get_billing_policy()}. Returns
	 * null when the payload carries no pricing policy (the plan had none at signup,
	 * or the snapshot predates the key) or an unreadable one, so a caller degrades
	 * to "no price adjustments" rather than fataling.
	 */
	public function get_pricing_policy(): ?PricingPolicy {
		$policy = $this->data['pricing_policy'] ?? null;
		if ( ! is_array( $policy ) ) {
			return null;
		}

		try {
			return PricingPolicy::from_array( self::string_keyed( $policy ) );
		} catch ( InvalidArgumentException $e ) {
			// A structurally-invalid stored policy degrades to "no price adjustments"
			// rather than fataling the read (same fail-soft rationale as the billing
			// accessor); snapshots this engine writes always carry a valid policy.
			unset( $e );
			return null;
		}
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
	 * The serialized payload for storage: the plan terms as given, no
	 * canonicalization (dedup is by copy-forward, not a content hash).
	 *
	 * @return array<string, mixed>
	 */
	public function to_payload(): array {
		return $this->data;
	}

	/**
	 * Re-key a nested payload array as string-keyed for the typed value-object factory.
	 * A no-op at runtime (decoded JSON object keys are already strings); it recovers the
	 * string-keyed type that erases to `array<int|string, mixed>`.
	 *
	 * @param array<int|string, mixed> $value Nested payload array.
	 * @return array<string, mixed>
	 */
	private static function string_keyed( array $value ): array {
		$out = array();
		foreach ( $value as $key => $item ) {
			$out[ (string) $key ] = $item;
		}

		return $out;
	}
}

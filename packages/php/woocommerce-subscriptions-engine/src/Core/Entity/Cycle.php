<?php
/**
 * Cycle - one period in a chain `(contract_id, kind)`.
 *
 * A chain is not a stored entity: it is the pair `(contract_id, kind)`, and its
 * head and counters are derived from the cycle rows. A cycle therefore knows the
 * contract it belongs to and its `kind`, plus its position (`sequence_no`,
 * monotonic from 1) and its chargeable number (`count`).
 *
 * `count` is the chargeable number within the chain and the per-charge
 * idempotency anchor `(contract_id, kind, count)`. It is nullable: a non-counting
 * cycle (for example a future trial period) carries no chargeable number, so
 * several null-count cycles may coexist in a chain.
 *
 * A cycle is an immutable billing record: it is created at billing, freezing the
 * contract's then-current snapshot references, period boundaries, and computed
 * `expected_total`. Its core values are frozen from construction - only `status`,
 * `order_id`, and the open-ended `reason` annotation may change afterwards. A
 * billing cycle is born `pending` and settles to `billed` or `failed`; the
 * checkout signup cycle is created directly `billed`. The snapshot-row ids and
 * the in-memory snapshot value objects are persistence bindings stamped once (the
 * repository records where a snapshot landed, or attaches the decoded value object
 * on load); they are not domain mutations and cannot be re-pointed once set.
 *
 * Money is a decimal-safe string; timestamps are GMT strings (`Y-m-d H:i:s`).
 * The cycle references its plan and items snapshots by id once persisted, and
 * may also carry the typed snapshot value objects in memory. Status is a typed
 * {@see CycleStatus}; `order_id` links the cycle to its WooCommerce order
 * (billing or shipping); `extension_slug` records the owner.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Cycle entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) cycle or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Cycle {

	use ScalarCoercion;
	use MoneyScale;

	const KIND_BILLING = 'billing';

	/**
	 * Cycle id, or null before it is persisted.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Owning contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Position within the chain, monotonic from 1.
	 *
	 * @var int
	 */
	private $sequence_no;

	/**
	 * Chargeable number within the chain and the per-charge idempotency anchor,
	 * or null for a non-counting cycle.
	 *
	 * @var int|null
	 */
	private $count;

	/**
	 * Chain kind. Known-but-extensible; only the billing kind is written for now.
	 *
	 * @var string
	 */
	private $kind;

	/**
	 * Lifecycle status as a typed value. See {@see CycleStatus}.
	 *
	 * @var CycleStatus
	 */
	private $status;

	/**
	 * Open-ended human/audit annotation, or null. Any cycle may carry one.
	 *
	 * @var string|null
	 */
	private $reason;

	/**
	 * Period start (and, billing-in-advance, the charge date). GMT string.
	 *
	 * @var string
	 */
	private $starts_at_gmt;

	/**
	 * Period end. GMT string.
	 *
	 * @var string
	 */
	private $ends_at_gmt;

	/**
	 * Amount expected to be billed for this cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $expected_total;

	/**
	 * ISO-4217 currency code.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Plan snapshot row id, or null until the snapshot is stored.
	 *
	 * @var int|null
	 */
	private $plan_snapshot_id;

	/**
	 * Items snapshot row id, or null until the snapshot is stored.
	 *
	 * @var int|null
	 */
	private $items_snapshot_id;

	/**
	 * Order id this cycle is linked to (billing or shipping), or null. A plain
	 * reference - not 1:1, since an aggregate order may serve many cycles.
	 *
	 * @var int|null
	 */
	private $order_id;

	/**
	 * Owning extension slug, or null.
	 *
	 * @var string|null
	 */
	private $extension_slug;

	/**
	 * Typed plan snapshot held in memory, or null.
	 *
	 * @var PlanSnapshot|null
	 */
	private $plan_snapshot;

	/**
	 * Typed items snapshot held in memory, or null.
	 *
	 * @var ItemsSnapshot|null
	 */
	private $items_snapshot;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}.
	 *
	 * Coerces each attribute from `$data` (keyed by property name) to its property
	 * type, so callers build a cycle from a flat associative array rather than a long
	 * positional argument list. Unknown keys are ignored; missing keys take the
	 * documented per-field default.
	 *
	 * The factories resolve the two fields the array shape cannot carry directly: the
	 * typed {@see CycleStatus} (a `CycleStatus` instance or a status string, defaulting
	 * to `pending`) and the chargeable `count` (already an `int|null`, since the
	 * absent-vs-explicit-null distinction is settled per factory). The snapshot value
	 * objects are likewise passed already typed.
	 *
	 * @param array<string, mixed> $data Raw attributes keyed by property name.
	 */
	private function __construct( array $data ) {
		$this->id                = self::coerce_nullable_int( $data['id'] ?? null );
		$this->contract_id       = self::coerce_int( $data['contract_id'] ?? null );
		$this->sequence_no       = self::coerce_int( $data['sequence_no'] ?? null );
		$this->count             = isset( $data['count'] ) ? self::coerce_int( $data['count'] ) : null;
		$this->kind              = self::coerce_string( $data['kind'] ?? null, self::KIND_BILLING );
		$this->status            = self::coerce_status( $data['status'] ?? null );
		$this->reason            = self::coerce_nullable_string( $data['reason'] ?? null );
		$this->starts_at_gmt     = self::coerce_string( $data['starts_at_gmt'] ?? null );
		$this->ends_at_gmt       = self::coerce_string( $data['ends_at_gmt'] ?? null );
		$this->expected_total    = self::normalize_money( $data['expected_total'] ?? '0' );
		$this->currency          = self::coerce_string( $data['currency'] ?? null );
		$this->plan_snapshot_id  = self::coerce_nullable_int( $data['plan_snapshot_id'] ?? null );
		$this->items_snapshot_id = self::coerce_nullable_int( $data['items_snapshot_id'] ?? null );
		$this->order_id          = self::coerce_nullable_int( $data['order_id'] ?? null );
		$this->extension_slug    = self::coerce_nullable_string( $data['extension_slug'] ?? null );
		$this->plan_snapshot     = ( $data['plan_snapshot'] ?? null ) instanceof PlanSnapshot ? $data['plan_snapshot'] : null;
		$this->items_snapshot    = ( $data['items_snapshot'] ?? null ) instanceof ItemsSnapshot ? $data['items_snapshot'] : null;
	}

	/**
	 * Build a new, unsaved cycle.
	 *
	 * Required keys: `contract_id`, `sequence_no`, `starts_at_gmt`, `ends_at_gmt`,
	 * `expected_total`, `currency`. Optional: `count` (defaults to 1; pass null for
	 * a non-counting cycle), `status` (defaults to `pending`; a checkout signup
	 * cycle is created directly `billed`), `kind` (defaults to billing), `reason`,
	 * `order_id`, `extension_slug`, `plan_snapshot_id`, `items_snapshot_id`,
	 * `plan_snapshot`, `items_snapshot`.
	 *
	 * @param array<string, mixed> $args Cycle attributes.
	 * @throws DomainException If the attributes are not valid.
	 */
	public static function create( array $args ): self {
		if ( ! isset( $args['contract_id'] ) ) {
			throw new DomainException( 'Cycle: contract_id is required.' );
		}

		// A new cycle is always unsaved; never adopt a caller-supplied id.
		unset( $args['id'] );

		// Distinguish an absent count (defaults to 1, a counting cycle) from an
		// explicit null (a non-counting cycle), which `?? 1` would conflate.
		$args['count'] = self::normalize_count( array_key_exists( 'count', $args ) ? $args['count'] : 1 );

		$cycle = new self( $args );

		self::assert_valid_kind( $cycle->kind );
		self::assert_valid_sequence_no( $cycle->sequence_no );

		return $cycle;
	}

	/**
	 * Hydrate from a stored row.
	 *
	 * @param array<string, mixed> $row Cycle row.
	 * @throws DomainException If the stored status, kind, or sequence_no is invalid.
	 */
	public static function from_storage( array $row ): self {
		$kind = self::coerce_string( $row['kind'] ?? null, self::KIND_BILLING );
		self::assert_valid_kind( $kind );

		$sequence_no = self::coerce_int( $row['sequence_no'] ?? null );
		self::assert_valid_sequence_no( $sequence_no );

		// The typed snapshot value objects are attached on load, never hydrated here.
		unset( $row['plan_snapshot'], $row['items_snapshot'] );

		$row['count'] = array_key_exists( 'count', $row ) ? self::normalize_count( $row['count'] ) : null;

		return new self( $row );
	}

	/**
	 * Cycle id, or null before save.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Assign the id after a successful insert.
	 *
	 * @param int $id Cycle id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Owning contract id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * Set the owning contract id.
	 *
	 * A cycle may be built with a placeholder contract id (0) before its contract
	 * is persisted; the repository stamps the real id once the contract row has
	 * one.
	 *
	 * @param int $contract_id Owning contract id.
	 */
	public function set_contract_id( int $contract_id ): void {
		$this->contract_id = $contract_id;
	}

	/**
	 * Position within the chain.
	 */
	public function get_sequence_no(): int {
		return $this->sequence_no;
	}

	/**
	 * Set the position within the chain.
	 *
	 * The chain `(contract_id, kind)` owns sequencing; the append path assigns the
	 * next monotonic value, so this is intentionally simple.
	 *
	 * @param int $sequence_no Position, monotonic from 1.
	 * @throws DomainException If `$sequence_no` is not positive.
	 */
	public function set_sequence_no( int $sequence_no ): void {
		self::assert_valid_sequence_no( $sequence_no );

		$this->sequence_no = $sequence_no;
	}

	/**
	 * Chargeable number within the chain, or null for a non-counting cycle.
	 */
	public function get_count(): ?int {
		return $this->count;
	}

	/**
	 * Cycle kind.
	 */
	public function get_kind(): string {
		return $this->kind;
	}

	/**
	 * Lifecycle status.
	 */
	public function get_status(): CycleStatus {
		return $this->status;
	}

	/**
	 * Transition the cycle to a new status.
	 *
	 * @param CycleStatus $status Target status.
	 * @throws DomainException If the transition is not allowed by CycleStatus.
	 */
	public function set_status( CycleStatus $status ): void {
		if ( $this->status->equals( $status ) ) {
			return;
		}

		$this->status = $this->status->transition_to( $status );
	}

	/**
	 * Open-ended annotation, or null.
	 */
	public function get_reason(): ?string {
		return $this->reason;
	}

	/**
	 * Annotate the cycle with an open-ended reason.
	 *
	 * The annotation is always writable (any cycle may carry one): it is one of the
	 * few fields that may change after construction, alongside `status` and
	 * `order_id`.
	 *
	 * @param string|null $reason Reason text, or null to clear.
	 */
	public function set_reason( ?string $reason ): void {
		$this->reason = $reason;
	}

	/**
	 * Period start (GMT string). Frozen at construction.
	 */
	public function get_starts_at_gmt(): string {
		return $this->starts_at_gmt;
	}

	/**
	 * Period end (GMT string). Frozen at construction.
	 */
	public function get_ends_at_gmt(): string {
		return $this->ends_at_gmt;
	}

	/**
	 * Amount expected to be billed (decimal-safe string).
	 */
	public function get_expected_total(): string {
		return $this->expected_total;
	}

	/**
	 * ISO-4217 currency code.
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Plan snapshot row id, or null.
	 */
	public function get_plan_snapshot_id(): ?int {
		return $this->plan_snapshot_id;
	}

	/**
	 * Record the plan snapshot row id once it is stored.
	 *
	 * A write-once persistence binding (like {@see self::set_id()}): the repository
	 * stamps where the frozen plan snapshot landed in storage. It only fills an
	 * unset id and never re-points an already-recorded one, so the frozen reference
	 * cannot change.
	 *
	 * @param int $plan_snapshot_id Snapshot row id.
	 * @throws DomainException If a plan snapshot id is already recorded.
	 */
	public function set_plan_snapshot_id( int $plan_snapshot_id ): void {
		$this->assert_snapshot_ref_unset( 'plan_snapshot_id', $this->plan_snapshot_id );
		$this->plan_snapshot_id = $plan_snapshot_id;
	}

	/**
	 * Items snapshot row id, or null.
	 */
	public function get_items_snapshot_id(): ?int {
		return $this->items_snapshot_id;
	}

	/**
	 * Record the items snapshot row id once it is stored.
	 *
	 * The items companion to {@see self::set_plan_snapshot_id()}: a write-once
	 * persistence binding that only fills an unset id.
	 *
	 * @param int $items_snapshot_id Snapshot row id.
	 * @throws DomainException If an items snapshot id is already recorded.
	 */
	public function set_items_snapshot_id( int $items_snapshot_id ): void {
		$this->assert_snapshot_ref_unset( 'items_snapshot_id', $this->items_snapshot_id );
		$this->items_snapshot_id = $items_snapshot_id;
	}

	/**
	 * Order id linked to this cycle, or null.
	 */
	public function get_order_id(): ?int {
		return $this->order_id;
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->extension_slug;
	}

	/**
	 * Typed plan snapshot held in memory, or null.
	 */
	public function get_plan_snapshot(): ?PlanSnapshot {
		return $this->plan_snapshot;
	}

	/**
	 * Attach the typed plan snapshot value object once.
	 *
	 * A write-once in-memory binding: the repository attaches the decoded snapshot
	 * on load. It only fills an unset value object and never replaces one, so the
	 * frozen snapshot a cycle carries cannot be swapped.
	 *
	 * @param PlanSnapshot $plan_snapshot Snapshot value object.
	 * @throws DomainException If a plan snapshot value object is already attached.
	 */
	public function set_plan_snapshot( PlanSnapshot $plan_snapshot ): void {
		if ( null !== $this->plan_snapshot ) {
			throw new DomainException( 'Cycle: the plan snapshot is frozen and cannot be replaced.' );
		}
		$this->plan_snapshot = $plan_snapshot;
	}

	/**
	 * Typed items snapshot held in memory, or null.
	 */
	public function get_items_snapshot(): ?ItemsSnapshot {
		return $this->items_snapshot;
	}

	/**
	 * Attach the typed items snapshot value object once.
	 *
	 * The items companion to {@see self::set_plan_snapshot()}: a write-once
	 * in-memory binding that only fills an unset value object.
	 *
	 * @param ItemsSnapshot $items_snapshot Snapshot value object.
	 * @throws DomainException If an items snapshot value object is already attached.
	 */
	public function set_items_snapshot( ItemsSnapshot $items_snapshot ): void {
		if ( null !== $this->items_snapshot ) {
			throw new DomainException( 'Cycle: the items snapshot is frozen and cannot be replaced.' );
		}
		$this->items_snapshot = $items_snapshot;
	}

	/**
	 * Serialize the cycle row (excluding the generated id/timestamps).
	 *
	 * The status is serialized as its plain string value; the typed
	 * {@see CycleStatus} is an in-memory concern only.
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'contract_id'       => $this->contract_id,
			'sequence_no'       => $this->sequence_no,
			'count'             => $this->count,
			'kind'              => $this->kind,
			'status'            => $this->status->get_value(),
			'reason'            => $this->reason,
			'starts_at_gmt'     => $this->starts_at_gmt,
			'ends_at_gmt'       => $this->ends_at_gmt,
			'expected_total'    => $this->expected_total,
			'currency'          => $this->currency,
			'plan_snapshot_id'  => $this->plan_snapshot_id,
			'items_snapshot_id' => $this->items_snapshot_id,
			'order_id'          => $this->order_id,
			'extension_slug'    => $this->extension_slug,
		);
	}

	/**
	 * Guard a snapshot row-id stamp so it is write-once.
	 *
	 * Snapshot references are frozen: the repository records where a snapshot landed
	 * exactly once. A second stamp would re-point a frozen reference, so it is
	 * rejected.
	 *
	 * @param string   $field   Field being stamped, for the error message.
	 * @param int|null $current The currently recorded id (must be null to stamp).
	 * @throws DomainException If the id is already recorded.
	 */
	private function assert_snapshot_ref_unset( string $field, ?int $current ): void {
		if ( null !== $current ) {
			throw new DomainException(
				sprintf( 'Cycle: "%s" is frozen and cannot be re-pointed.', $field )
			);
		}
	}

	/**
	 * Validate a cycle kind.
	 *
	 * The kind is known-but-extensible: the billing kind is the one written today,
	 * but a third party may introduce its own kind, so any non-empty kind is
	 * accepted and only an empty kind is rejected. It is deliberately not a sealed
	 * enum.
	 *
	 * @param string $kind Kind to validate.
	 * @throws DomainException If `$kind` is empty.
	 */
	private static function assert_valid_kind( string $kind ): void {
		if ( '' === $kind ) {
			throw new DomainException( 'Cycle: kind must not be empty.' );
		}
	}

	/**
	 * Validate a sequence number.
	 *
	 * @param int $sequence_no Sequence number to validate.
	 * @throws DomainException If `$sequence_no` is not positive.
	 */
	private static function assert_valid_sequence_no( int $sequence_no ): void {
		if ( $sequence_no < 1 ) {
			throw new DomainException(
				sprintf( 'Cycle: sequence_no must be 1 or greater, got %d.', $sequence_no )
			);
		}
	}

	/**
	 * Resolve a status input into a typed {@see CycleStatus}.
	 *
	 * Accepts an existing `CycleStatus` instance (passed through unchanged), null or
	 * an absent value (defaults to `pending`, matching a newly billing cycle), or a
	 * status string (validated via {@see CycleStatus::from()}).
	 *
	 * @param mixed $status Raw status value (a CycleStatus, null, or a status string).
	 * @return CycleStatus
	 * @throws DomainException If a status string is not a known status.
	 */
	private static function coerce_status( $status ): CycleStatus {
		if ( $status instanceof CycleStatus ) {
			return $status;
		}

		if ( null === $status ) {
			return CycleStatus::pending();
		}

		return CycleStatus::from( self::coerce_string( $status ) );
	}

	/**
	 * Normalize and validate a chargeable count.
	 *
	 * Null passes through (a non-counting cycle); a present value must be a
	 * positive integer.
	 *
	 * @param mixed $count Raw count value (null, or coercible to int).
	 * @return int|null
	 * @throws DomainException If a present count is not positive.
	 */
	private static function normalize_count( $count ): ?int {
		if ( null === $count ) {
			return null;
		}

		$count = self::coerce_int( $count );
		if ( $count < 1 ) {
			throw new DomainException(
				sprintf( 'Cycle: count must be 1 or greater when set, got %d.', $count )
			);
		}

		return $count;
	}
}

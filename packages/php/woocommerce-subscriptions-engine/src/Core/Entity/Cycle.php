<?php
/**
 * Cycle - one period in a chain `(contract_id, kind)`, where a chain is the pair
 * `(contract_id, kind)` with counters derived from its rows (not a stored entity).
 *
 * An immutable billing record frozen at billing (snapshot references, period
 * boundaries, `expected_total`); only `status`, `order_id`, and `reason` may change
 * afterwards. The `count` is the chargeable number and idempotency anchor, nullable
 * for non-counting cycles (e.g. a future trial). Money is a decimal-safe string;
 * timestamps are GMT strings (`Y-m-d H:i:s`).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
use LogicException;
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

	public const KIND_BILLING = 'billing';

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
	 * Position within the chain, monotonic from 1, or null until the append assigns it.
	 *
	 * @var int|null
	 */
	private $sequence_no;

	/**
	 * Chargeable number and per-charge idempotency anchor, or null for a
	 * non-counting cycle.
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
	 * Lifecycle status. See {@see CycleStatus}.
	 *
	 * @var CycleStatus
	 */
	private $status;

	/**
	 * Open-ended annotation, or null.
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
	 * Linked order id (billing or shipping), or null. Not 1:1 - an aggregate order
	 * may serve many cycles.
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
	 * Crash-recovery lease expiry (GMT string), or null. Set when a `pending` cycle is
	 * claimed; a stuck pending cycle past this moment is reclaimable. The create-as-claim
	 * UNIQUE index is the primary concurrency guard - this only recovers a crashed charge.
	 *
	 * @var string|null
	 */
	private $claimed_until_gmt;

	/**
	 * Reserved retry moment (GMT string), or null. Additive column for a later
	 * retry/dunning pass; not wired by the dispatcher yet.
	 *
	 * @var string|null
	 */
	private $retry_at_gmt;

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
	 * Use {@see self::create()} or {@see self::from_storage()}. Coerces each attribute
	 * to its property type; unknown keys are ignored, missing keys take the default.
	 *
	 * @param array<string, mixed> $data Raw attributes keyed by property name.
	 */
	private function __construct( array $data ) {
		$this->id                = ScalarCoercion::coerce_nullable_int( $data['id'] ?? null );
		$this->contract_id       = ScalarCoercion::coerce_int( $data['contract_id'] ?? null );
		$this->sequence_no       = isset( $data['sequence_no'] ) ? ScalarCoercion::coerce_int( $data['sequence_no'] ) : null;
		$this->count             = isset( $data['count'] ) ? ScalarCoercion::coerce_int( $data['count'] ) : null;
		$this->kind              = ScalarCoercion::coerce_string( $data['kind'] ?? null, self::KIND_BILLING );
		$this->status            = self::coerce_status( $data['status'] ?? null );
		$this->reason            = ScalarCoercion::coerce_nullable_string( $data['reason'] ?? null );
		$this->starts_at_gmt     = ScalarCoercion::coerce_string( $data['starts_at_gmt'] ?? null );
		$this->ends_at_gmt       = ScalarCoercion::coerce_string( $data['ends_at_gmt'] ?? null );
		$this->expected_total    = MoneyScale::normalize_money( $data['expected_total'] ?? '0' );
		$this->currency          = ScalarCoercion::coerce_string( $data['currency'] ?? null );
		$this->plan_snapshot_id  = ScalarCoercion::coerce_nullable_int( $data['plan_snapshot_id'] ?? null );
		$this->items_snapshot_id = ScalarCoercion::coerce_nullable_int( $data['items_snapshot_id'] ?? null );
		$this->order_id          = ScalarCoercion::coerce_nullable_int( $data['order_id'] ?? null );
		$this->extension_slug    = ScalarCoercion::coerce_nullable_string( $data['extension_slug'] ?? null );
		$this->claimed_until_gmt = ScalarCoercion::coerce_nullable_string( $data['claimed_until'] ?? null );
		$this->retry_at_gmt      = ScalarCoercion::coerce_nullable_string( $data['retry_at'] ?? null );
		$this->plan_snapshot     = ( $data['plan_snapshot'] ?? null ) instanceof PlanSnapshot ? $data['plan_snapshot'] : null;
		$this->items_snapshot    = ( $data['items_snapshot'] ?? null ) instanceof ItemsSnapshot ? $data['items_snapshot'] : null;
	}

	/**
	 * Build a new, unsaved cycle.
	 *
	 * Required keys: `contract_id`, `starts_at_gmt`, `ends_at_gmt`, `expected_total`,
	 * `currency`. Optional: `sequence_no` (absent or null: the append assigns the chain's
	 * next position), `count` (absent or null for a non-counting cycle; the caller owns
	 * the numbering), `status` (defaults to `pending`), `kind` (defaults to billing), `reason`,
	 * `order_id`, `extension_slug`, `plan_snapshot_id`, `items_snapshot_id`,
	 * `plan_snapshot`, `items_snapshot`.
	 *
	 * @param array<string, mixed> $args Cycle attributes.
	 * @throws DomainException If the attributes are not valid.
	 */
	public static function create( array $args ): self {
		// A new cycle is always unsaved; never adopt a caller-supplied id.
		unset( $args['id'] );

		$cycle = new self( $args );

		self::assert_contract_id( $cycle->contract_id );
		self::assert_kind( $cycle->kind );
		if ( null !== $cycle->sequence_no ) {
			self::assert_sequence_no( $cycle->sequence_no );
		}
		self::assert_period( $cycle->starts_at_gmt, $cycle->ends_at_gmt );
		self::assert_currency( $cycle->currency );
		self::assert_count( $cycle->count );
		self::assert_status( $cycle->status );

		return $cycle;
	}

	/**
	 * Hydrate from a stored row.
	 *
	 * Stored values are not validated (invariants are checked only where a value is
	 * written), so a value written by a since-deactivated extension round-trips unchanged.
	 *
	 * @param array<string, mixed> $row Cycle row.
	 * @throws DomainException If the stored status is not a well-formed status slug.
	 */
	public static function from_storage( array $row ): self {
		// The typed snapshot value objects are attached on load, never hydrated here.
		unset( $row['plan_snapshot'], $row['items_snapshot'] );

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
	 * Position within the chain.
	 *
	 * @throws LogicException If the position is not assigned yet (a new cycle before its append).
	 */
	public function get_sequence_no(): int {
		if ( null === $this->sequence_no ) {
			throw new LogicException( 'Cycle: sequence_no is assigned on append.' );
		}

		return $this->sequence_no;
	}

	/**
	 * Whether the append still has to assign the position within the chain.
	 *
	 * @internal Used by the repository's append.
	 */
	public function awaits_sequence_no(): bool {
		return null === $this->sequence_no;
	}

	/**
	 * Assign the position within the chain. Only while it is unassigned.
	 *
	 * @internal Used by the repository's append.
	 *
	 * @param int $sequence_no Position, monotonic from 1.
	 * @throws DomainException If the position is already assigned or `$sequence_no` is not positive.
	 */
	public function assign_sequence_no( int $sequence_no ): void {
		if ( null !== $this->sequence_no ) {
			throw new DomainException( 'Cycle: sequence_no is already assigned.' );
		}
		self::assert_sequence_no( $sequence_no );

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
	 * Set the cycle status.
	 *
	 * Any status may follow any other: the engine enforces no transition table. A
	 * changed status must be registered (an unregistered value, such as one built
	 * through {@see new CycleStatus()}, is rejected); setting the current status
	 * is a no-op, so a hydrated unregistered status can be saved unchanged.
	 *
	 * @param CycleStatus $status Target status.
	 * @throws DomainException If the status changes to an unregistered value.
	 */
	public function set_status( CycleStatus $status ): void {
		if ( $this->status->equals( $status ) ) {
			return;
		}

		self::assert_status( $status );

		$this->status = $status;
	}

	/**
	 * Open-ended annotation, or null.
	 */
	public function get_reason(): ?string {
		return $this->reason;
	}

	/**
	 * Annotate the cycle with an open-ended reason. Always writable.
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
	 * Record the plan snapshot row id once it is stored. Write-once: only fills an
	 * unset id, never re-points the frozen reference.
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
	 * Record the items snapshot row id once it is stored. Write-once companion to
	 * {@see self::set_plan_snapshot_id()}.
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
	 * Link the cycle to its order. One of the few post-freeze-mutable fields (with
	 * `status` and `reason`): the order id is stamped once the charge order exists.
	 *
	 * @param int $order_id Linked order id.
	 */
	public function set_order_id( int $order_id ): void {
		$this->order_id = $order_id;
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->extension_slug;
	}

	/**
	 * Crash-recovery lease expiry (GMT string), or null.
	 */
	public function get_claimed_until_gmt(): ?string {
		return $this->claimed_until_gmt;
	}

	/**
	 * Set (or clear) the crash-recovery lease expiry. Post-freeze-mutable (with `status`,
	 * `order_id`, `reason`): a claim stamps it, and a reclaim/resolve may re-stamp or clear it.
	 *
	 * @param string|null $claimed_until_gmt Lease expiry GMT string, or null to clear.
	 */
	public function set_claimed_until_gmt( ?string $claimed_until_gmt ): void {
		$this->claimed_until_gmt = $claimed_until_gmt;
	}

	/**
	 * Reserved retry moment (GMT string), or null.
	 */
	public function get_retry_at_gmt(): ?string {
		return $this->retry_at_gmt;
	}

	/**
	 * Set (or clear) the reserved retry moment. Reserved for a later retry/dunning pass.
	 *
	 * @param string|null $retry_at_gmt Retry moment GMT string, or null to clear.
	 */
	public function set_retry_at_gmt( ?string $retry_at_gmt ): void {
		$this->retry_at_gmt = $retry_at_gmt;
	}

	/**
	 * Typed plan snapshot held in memory, or null.
	 */
	public function get_plan_snapshot(): ?PlanSnapshot {
		return $this->plan_snapshot;
	}

	/**
	 * Attach the typed plan snapshot value object once. Write-once: only fills an
	 * unset value object, never swaps the frozen snapshot.
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
	 * Attach the typed items snapshot value object once. Write-once companion to
	 * {@see self::set_plan_snapshot()}.
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
	 * Serialize the cycle row (excluding the generated id/timestamps). Status is
	 * stored as its plain string value.
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
			'claimed_until'     => $this->claimed_until_gmt,
			'retry_at'          => $this->retry_at_gmt,
		);
	}

	/**
	 * Guard a snapshot row-id stamp so it is write-once; a second stamp would
	 * re-point a frozen reference.
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
	 * Refuse a missing (non-positive) owning contract id.
	 *
	 * @param int $contract_id Contract id to check.
	 * @throws DomainException If `$contract_id` is not positive.
	 */
	private static function assert_contract_id( int $contract_id ): void {
		if ( $contract_id < 1 ) {
			throw new DomainException( 'Cycle: contract_id is required.' );
		}
	}

	/**
	 * Validate a cycle kind. Known-but-extensible (deliberately not a sealed enum):
	 * any non-empty kind is accepted so a third party may introduce its own.
	 *
	 * @param string $kind Kind to validate.
	 * @throws DomainException If `$kind` is empty.
	 */
	private static function assert_kind( string $kind ): void {
		if ( '' === $kind ) {
			throw new DomainException( 'Cycle: kind must not be empty.' );
		}
	}

	/**
	 * Refuse a cycle without a start or end moment.
	 *
	 * @param string $starts_at_gmt Start (GMT).
	 * @param string $ends_at_gmt   End (GMT).
	 * @throws DomainException If either moment is missing.
	 */
	private static function assert_period( string $starts_at_gmt, string $ends_at_gmt ): void {
		if ( '' === $starts_at_gmt || '' === $ends_at_gmt ) {
			throw new DomainException( 'Cycle: starts_at_gmt and ends_at_gmt are required.' );
		}
	}

	/**
	 * Refuse a cycle without a currency.
	 *
	 * @param string $currency Currency code.
	 * @throws DomainException If the currency is missing.
	 */
	private static function assert_currency( string $currency ): void {
		if ( '' === $currency ) {
			throw new DomainException( 'Cycle: currency is required.' );
		}
	}

	/**
	 * Validate a sequence number.
	 *
	 * @param int $sequence_no Sequence number to validate.
	 * @throws DomainException If `$sequence_no` is not positive.
	 */
	private static function assert_sequence_no( int $sequence_no ): void {
		if ( $sequence_no < 1 ) {
			throw new DomainException(
				sprintf( 'Cycle: sequence_no must be 1 or greater, got %d.', $sequence_no )
			);
		}
	}

	/**
	 * Reject a status value that is not registered. Write paths only
	 * ({@see self::create()}, {@see self::set_status()}); storage hydration keeps an
	 * unregistered stored value verbatim.
	 *
	 * @param CycleStatus $status Status to check.
	 * @throws DomainException If the status is not registered.
	 */
	private static function assert_status( CycleStatus $status ): void {
		if ( ! CycleStatus::is_registered( $status->get_value() ) ) {
			throw new DomainException(
				sprintf( 'Cycle: status "%s" is not registered.', $status->get_value() )
			);
		}
	}

	/**
	 * Resolve a status input into a typed {@see CycleStatus}. A `CycleStatus` passes
	 * through; null defaults to `pending`; a string is wrapped (slug format checked).
	 * Registration is checked by {@see self::create()} and {@see self::set_status()},
	 * not here, so storage hydration keeps an unregistered stored value.
	 *
	 * @param mixed $status Raw status value (a CycleStatus, null, or a status string).
	 * @return CycleStatus
	 * @throws DomainException If a status string is not a well-formed status slug.
	 */
	private static function coerce_status( $status ): CycleStatus {
		if ( $status instanceof CycleStatus ) {
			return $status;
		}

		if ( null === $status ) {
			return new CycleStatus( CycleStatus::PENDING );
		}

		return new CycleStatus( ScalarCoercion::coerce_string( $status ) );
	}

	/**
	 * Validate a chargeable count: null (a non-counting cycle) or a positive integer.
	 *
	 * @param int|null $count Count to validate.
	 * @throws DomainException If a present count is not positive.
	 */
	private static function assert_count( ?int $count ): void {
		if ( null !== $count && $count < 1 ) {
			throw new DomainException(
				sprintf( 'Cycle: count must be 1 or greater when set, got %d.', $count )
			);
		}
	}
}

<?php
/**
 * Cycle - one period in a chain `(contract_id, kind)`, where a chain is the pair
 * `(contract_id, kind)` with counters derived from its rows (not a stored entity).
 *
 * An immutable billing record frozen at billing (snapshot references, period
 * boundaries, `expected_total`). Only the repository's compare-and-set tools change a
 * stored cycle's `status`, `order_id` and `reason` afterwards. The `count` is the chargeable number and idempotency anchor, nullable
 * for non-counting cycles (e.g. a future trial). Money is a decimal-safe string;
 * timestamps are GMT strings (`Y-m-d H:i:s`).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use LogicException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
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
	 * Reserved retry moment (GMT string), or null. Additive column for a later
	 * retry/dunning pass; not wired by the dispatcher yet.
	 *
	 * @var string|null
	 */
	private $retry_at_gmt;

	/**
	 * Typed plan snapshot attached on read from `plan_snapshot_id`, or null.
	 *
	 * @var PlanSnapshot|null
	 */
	private $plan_snapshot;

	/**
	 * Typed items snapshot attached on read from `items_snapshot_id`, or null.
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
		$this->id                = Coercion::coerce_nullable_int( $data['id'] ?? null );
		$this->contract_id       = Coercion::coerce_int( $data['contract_id'] ?? null );
		$this->sequence_no       = isset( $data['sequence_no'] ) ? Coercion::coerce_int( $data['sequence_no'] ) : null;
		$this->count             = isset( $data['count'] ) ? Coercion::coerce_int( $data['count'] ) : null;
		$this->kind              = Coercion::coerce_string( $data['kind'] ?? null, self::KIND_BILLING );
		$this->status            = self::coerce_status( $data['status'] ?? null );
		$this->reason            = Coercion::coerce_nullable_string( $data['reason'] ?? null );
		$this->starts_at_gmt     = Coercion::coerce_string( $data['starts_at_gmt'] ?? null );
		$this->ends_at_gmt       = Coercion::coerce_string( $data['ends_at_gmt'] ?? null );
		$this->expected_total    = MoneyScale::normalize_money( $data['expected_total'] ?? '0' );
		$this->currency          = Coercion::coerce_string( $data['currency'] ?? null );
		$this->plan_snapshot_id  = Coercion::coerce_nullable_int( $data['plan_snapshot_id'] ?? null );
		$this->items_snapshot_id = Coercion::coerce_nullable_int( $data['items_snapshot_id'] ?? null );
		$this->order_id          = Coercion::coerce_nullable_int( $data['order_id'] ?? null );
		$this->extension_slug    = Coercion::coerce_nullable_string( $data['extension_slug'] ?? null );
		$this->retry_at_gmt      = Coercion::coerce_nullable_string( $data['retry_at'] ?? null );
	}

	/**
	 * Build a new, unsaved cycle.
	 *
	 * Required keys: `contract_id`, `starts_at_gmt`, `ends_at_gmt`, `expected_total`,
	 * `currency`. Optional: `sequence_no` (absent or null: the append assigns the chain's
	 * next position), `count` (absent or null for a non-counting cycle; the caller owns
	 * the numbering), `status` (defaults to `pending`), `kind` (defaults to billing), `reason`,
	 * `order_id`, `extension_slug`, `plan_snapshot_id`, `items_snapshot_id`. Snapshot value
	 * objects are attached only when a stored cycle is read. Every invalid attribute is
	 * reported in one exception.
	 *
	 * @param array<string, mixed> $args Cycle attributes.
	 * @throws InvalidInputException If the attributes are not valid.
	 */
	public static function create( array $args ): self {
		// A new cycle is always unsaved; never adopt a caller-supplied id.
		unset( $args['id'] );

		// Checked before construction, which refuses a malformed status slug; a refused
		// status is dropped so construction can still check the other attributes.
		$status_error = self::get_status_error( $args['status'] ?? null );
		if ( null !== $status_error ) {
			unset( $args['status'] );
		}

		$cycle = new self( $args );

		$errors = array_filter(
			array(
				self::get_contract_id_error( $cycle->contract_id ),
				self::get_kind_error( $cycle->kind ),
				// Null is allowed here for auto-assign sequence_no during append operation.
				null === $cycle->sequence_no ? null : self::get_sequence_no_error( $cycle->sequence_no ),
				self::get_starts_at_gmt_error( $cycle->starts_at_gmt ),
				self::get_ends_at_gmt_error( $cycle->ends_at_gmt ),
				self::get_currency_error( $cycle->currency ),
				self::get_count_error( $cycle->count ),
				$status_error,
			)
		);
		if ( array() !== $errors ) {
			throw InvalidInputException::from_errors( array_values( $errors ) );
		}

		return $cycle;
	}

	/**
	 * Hydrate from a stored row.
	 *
	 * Stored values are not validated (invariants are checked only where a value is
	 * written), so a value written by a since-deactivated extension round-trips unchanged.
	 *
	 * @param array<string, mixed> $row Cycle row.
	 * @throws InvalidInputException If the stored status is not a well-formed status slug.
	 */
	public static function from_storage( array $row ): self {
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
	 * @throws LogicException If the position is already assigned ({@see InvalidInputException}, a LogicException, if `$sequence_no` is not positive).
	 */
	public function assign_sequence_no( int $sequence_no ): void {
		if ( null !== $this->sequence_no ) {
			throw new LogicException( 'Cycle: sequence_no is already assigned.' );
		}
		$error = self::get_sequence_no_error( $sequence_no );
		if ( null !== $error ) {
			throw $error;
		}

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
	 * Open-ended annotation, or null.
	 */
	public function get_reason(): ?string {
		return $this->reason;
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
	 * Items snapshot row id, or null.
	 */
	public function get_items_snapshot_id(): ?int {
		return $this->items_snapshot_id;
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
	 * Reserved retry moment (GMT string), or null.
	 */
	public function get_retry_at_gmt(): ?string {
		return $this->retry_at_gmt;
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
	 * @throws LogicException If a plan snapshot value object is already attached.
	 */
	public function set_plan_snapshot( PlanSnapshot $plan_snapshot ): void {
		if ( null !== $this->plan_snapshot ) {
			throw new LogicException( 'Cycle: the plan snapshot is frozen and cannot be replaced.' );
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
	 * @throws LogicException If an items snapshot value object is already attached.
	 */
	public function set_items_snapshot( ItemsSnapshot $items_snapshot ): void {
		if ( null !== $this->items_snapshot ) {
			throw new LogicException( 'Cycle: the items snapshot is frozen and cannot be replaced.' );
		}
		$this->items_snapshot = $items_snapshot;
	}

	/**
	 * Serialize the cycle row (excluding the generated id/timestamps). Status is
	 * stored as its plain string value.
	 *
	 * @return array<string, int|string|null>
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
			'retry_at'          => $this->retry_at_gmt,
		);
	}

	/**
	 * The error for a missing (non-positive) owning contract id, or null.
	 *
	 * @param int $contract_id Contract id to check.
	 */
	private static function get_contract_id_error( int $contract_id ): ?InvalidInputException {
		if ( $contract_id < 1 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'contract_id',
				sprintf( 'Cycle: contract_id must be 1 or greater, got %d.', $contract_id ),
				array( 'min' => 1 )
			);
		}

		return null;
	}

	/**
	 * The error for an empty cycle kind, or null. Known-but-extensible (deliberately not a
	 * sealed enum): any non-empty kind is accepted so a third party may introduce its own.
	 *
	 * @param string $kind Kind to check.
	 */
	private static function get_kind_error( string $kind ): ?InvalidInputException {
		if ( '' === $kind ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'kind', 'Cycle: kind must not be empty.' );
		}

		return null;
	}

	/**
	 * The error for a missing start moment, or null.
	 *
	 * @param string $starts_at_gmt Start (GMT).
	 */
	private static function get_starts_at_gmt_error( string $starts_at_gmt ): ?InvalidInputException {
		if ( '' === $starts_at_gmt ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'starts_at_gmt', 'Cycle: starts_at_gmt is required.' );
		}

		return null;
	}

	/**
	 * The error for a missing end moment, or null.
	 *
	 * @param string $ends_at_gmt End (GMT).
	 */
	private static function get_ends_at_gmt_error( string $ends_at_gmt ): ?InvalidInputException {
		if ( '' === $ends_at_gmt ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'ends_at_gmt', 'Cycle: ends_at_gmt is required.' );
		}

		return null;
	}

	/**
	 * The error for a missing currency, or null.
	 *
	 * @param string $currency Currency code.
	 */
	private static function get_currency_error( string $currency ): ?InvalidInputException {
		if ( '' === $currency ) {
			return new InvalidInputException( ValidationErrorCode::REQUIRED, 'currency', 'Cycle: currency is required.' );
		}

		return null;
	}

	/**
	 * The error for a non-positive sequence number, or null.
	 *
	 * @param int $sequence_no Sequence number to check.
	 */
	private static function get_sequence_no_error( int $sequence_no ): ?InvalidInputException {
		if ( $sequence_no < 1 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'sequence_no',
				sprintf( 'Cycle: sequence_no must be 1 or greater, got %d.', $sequence_no ),
				array( 'min' => 1 )
			);
		}

		return null;
	}

	/**
	 * The error for a status input a new cycle cannot take, or null: a malformed status slug
	 * (`invalid_value`) or one that is not registered (`not_registered`). Null means the
	 * default `pending`. Write path only ({@see self::create()}); storage hydration keeps an
	 * unregistered stored value verbatim.
	 *
	 * @param mixed $status Raw status value (a CycleStatus, null, or a status string).
	 */
	private static function get_status_error( $status ): ?InvalidInputException {
		if ( null === $status ) {
			return null;
		}

		$value = $status instanceof CycleStatus ? $status->get_value() : Coercion::coerce_string( $status );
		if ( ! CycleStatus::is_valid( $value ) ) {
			return new InvalidInputException(
				ValidationErrorCode::INVALID_VALUE,
				'status',
				sprintf( 'CycleStatus: "%s" is not a valid status slug.', $value )
			);
		}
		if ( ! CycleStatus::is_registered( $value ) ) {
			return new InvalidInputException(
				ValidationErrorCode::NOT_REGISTERED,
				'status',
				sprintf( 'Cycle: status "%s" is not registered.', $value ),
				array( 'value' => $value )
			);
		}

		return null;
	}

	/**
	 * Resolve a status input into a typed {@see CycleStatus}. A `CycleStatus` passes
	 * through; null defaults to `pending`; a string is wrapped (slug format checked).
	 * {@see self::create()} checks the raw status first; registration is not checked here,
	 * so storage hydration keeps an unregistered stored value.
	 *
	 * @param mixed $status Raw status value (a CycleStatus, null, or a status string).
	 * @return CycleStatus
	 * @throws InvalidInputException If a status string is not a well-formed status slug.
	 */
	private static function coerce_status( $status ): CycleStatus {
		if ( $status instanceof CycleStatus ) {
			return $status;
		}

		if ( null === $status ) {
			return new CycleStatus( CycleStatus::PENDING );
		}

		return new CycleStatus( Coercion::coerce_string( $status ) );
	}

	/**
	 * The error for a present count that is not positive, or null (a non-counting cycle
	 * has no count).
	 *
	 * @param int|null $count Count to check.
	 */
	private static function get_count_error( ?int $count ): ?InvalidInputException {
		if ( null !== $count && $count < 1 ) {
			return new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'count',
				sprintf( 'Cycle: count must be 1 or greater when set, got %d.', $count ),
				array( 'min' => 1 )
			);
		}

		return null;
	}
}

<?php
/**
 * Contract - the stable identity of a subscription and the live source of truth
 * for its current state. Enforces lifecycle transitions through {@see ContractStatus}.
 *
 * Being the live source of truth (mutable), it holds the live schedule
 * (`next_payment_gmt`), the latest snapshot references (`plan_snapshot_id` /
 * `items_snapshot_id`), and the live config values (the `*_total` totals and the
 * `*_gmt` stamps). These are live values, not caches of cycles: sync flows one way
 * down - a live change repoints the contract's snapshot, and a billing cycle freezes
 * whatever the contract points at now - never cycle -> contract.
 *
 * It holds no cycle graph in memory (cycles are fetched on demand), and a chain is
 * just the pair `(contract_id, kind)` with its counters derived from the cycle rows.
 * `origin_order_id` is nullable (a manual contract has none; for a checkout contract
 * it equals cycle 1's `order_id`). Timestamps are GMT strings; money totals are
 * decimal-safe strings on the storage scale; the payment instrument is exposed as an
 * {@see InstrumentRef}.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Contract entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) contract or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Contract {

	public const SCHEDULE_SOURCE_PRIMITIVE = 'primitive';
	public const SCHEDULE_SOURCE_GATEWAY   = 'gateway';

	public const ADDRESS_BILLING  = 'billing';
	public const ADDRESS_SHIPPING = 'shipping';

	/**
	 * Contract id, or null before it is persisted.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Lifecycle status. See {@see ContractStatus}.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Owning customer id.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * ISO-4217 currency code, locked at creation.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Foreign key to the selling plan.
	 *
	 * @var int
	 */
	private $selling_plan_id;

	/**
	 * Origin order id, or null for a manual contract. Equals cycle 1's `order_id`
	 * for a checkout contract.
	 *
	 * @var int|null
	 */
	private $origin_order_id;

	/**
	 * Owning extension slug, or null.
	 *
	 * @var string|null
	 */
	private $extension_slug;

	/**
	 * Gateway code, or null.
	 *
	 * @var string|null
	 */
	private $payment_method;

	/**
	 * Human-readable gateway title, or null.
	 *
	 * @var string|null
	 */
	private $payment_method_title;

	/**
	 * Payment token id, or null.
	 *
	 * @var int|null
	 */
	private $payment_token_id;

	/**
	 * When the contract goes (or went) active. GMT string.
	 *
	 * @var string
	 */
	private $start_gmt;

	/**
	 * Live schedule: when the next renewal fires, or null. GMT string. The due scan
	 * keys on this; a billing cycle freezes its period from it.
	 *
	 * @var string|null
	 */
	private $next_payment_gmt;

	/**
	 * Latest/live plan snapshot row id, or null.
	 *
	 * @var int|null
	 */
	private $plan_snapshot_id;

	/**
	 * Latest/live items snapshot row id, or null until one is recorded.
	 *
	 * @var int|null
	 */
	private $items_snapshot_id;

	/**
	 * Optionally-hydrated frozen plan terms for `plan_snapshot_id` - the per-contract
	 * billing cadence read off the snapshot, not the live plan. Populated by the
	 * repository on the read paths that need it (the customer-portal reads); null on the
	 * lean reads that do not. Not a stored column, so it is absent from `to_storage()`.
	 *
	 * @var PlanSnapshot|null
	 */
	private $plan_snapshot;

	/**
	 * Live billing total (the recurring amount), a decimal-safe string.
	 *
	 * @var string
	 */
	private $billing_total;

	/**
	 * Live discount total, a decimal-safe string.
	 *
	 * @var string
	 */
	private $discount_total;

	/**
	 * Live shipping total, a decimal-safe string.
	 *
	 * @var string
	 */
	private $shipping_total;

	/**
	 * Live tax total, a decimal-safe string.
	 *
	 * @var string
	 */
	private $tax_total;

	/**
	 * When the contract last billed successfully, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $last_payment_gmt;

	/**
	 * When the contract last attempted a charge, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $last_attempt_gmt;

	/**
	 * When the contract's trial ends (or ended), or null. GMT string.
	 *
	 * @var string|null
	 */
	private $trial_end_gmt;

	/**
	 * When the contract ends (or ended), or null. GMT string.
	 *
	 * @var string|null
	 */
	private $end_gmt;

	/**
	 * Who runs renewals: 'primitive' (this engine) or 'gateway'.
	 *
	 * @var string
	 */
	private $schedule_source;

	/**
	 * Line items, each a plain associative array matching the items table shape.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $items;

	/**
	 * Addresses keyed by type ('billing' | 'shipping').
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $addresses;

	/**
	 * Contract meta as key => value.
	 *
	 * @var array<string, string>
	 */
	private $meta;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}. Coerces each attribute
	 * to its property type; unknown keys are ignored, missing keys take the default.
	 *
	 * @param array<string, mixed> $data Raw attributes keyed by property name.
	 */
	private function __construct( array $data ) {
		$this->id                   = ScalarCoercion::coerce_nullable_int( $data['id'] ?? null );
		$this->status               = ScalarCoercion::coerce_string( $data['status'] ?? null, ContractStatus::ACTIVE );
		$this->customer_id          = ScalarCoercion::coerce_int( $data['customer_id'] ?? null );
		$this->currency             = ScalarCoercion::coerce_string( $data['currency'] ?? null );
		$this->selling_plan_id      = ScalarCoercion::coerce_int( $data['selling_plan_id'] ?? null );
		$this->origin_order_id      = ScalarCoercion::coerce_nullable_int( $data['origin_order_id'] ?? null );
		$this->extension_slug       = ScalarCoercion::coerce_nullable_string( $data['extension_slug'] ?? null );
		$this->payment_method       = ScalarCoercion::coerce_nullable_string( $data['payment_method'] ?? null );
		$this->payment_method_title = ScalarCoercion::coerce_nullable_string( $data['payment_method_title'] ?? null );
		$this->payment_token_id     = ScalarCoercion::coerce_nullable_int( $data['payment_token_id'] ?? null );
		$this->start_gmt            = ScalarCoercion::coerce_string( $data['start_gmt'] ?? null );
		$this->next_payment_gmt     = ScalarCoercion::coerce_nullable_string( $data['next_payment_gmt'] ?? null );
		$this->plan_snapshot_id     = ScalarCoercion::coerce_nullable_int( $data['plan_snapshot_id'] ?? null );
		$this->items_snapshot_id    = ScalarCoercion::coerce_nullable_int( $data['items_snapshot_id'] ?? null );
		$this->billing_total        = MoneyScale::normalize_money( $data['billing_total'] ?? '0' );
		$this->discount_total       = MoneyScale::normalize_money( $data['discount_total'] ?? '0' );
		$this->shipping_total       = MoneyScale::normalize_money( $data['shipping_total'] ?? '0' );
		$this->tax_total            = MoneyScale::normalize_money( $data['tax_total'] ?? '0' );
		$this->last_payment_gmt     = ScalarCoercion::coerce_nullable_string( $data['last_payment_gmt'] ?? null );
		$this->last_attempt_gmt     = ScalarCoercion::coerce_nullable_string( $data['last_attempt_gmt'] ?? null );
		$this->trial_end_gmt        = ScalarCoercion::coerce_nullable_string( $data['trial_end_gmt'] ?? null );
		$this->end_gmt              = ScalarCoercion::coerce_nullable_string( $data['end_gmt'] ?? null );
		$this->schedule_source      = ScalarCoercion::coerce_string( $data['schedule_source'] ?? null, self::SCHEDULE_SOURCE_PRIMITIVE );
		$this->items                = self::coerce_item_rows( $data['items'] ?? null );
		$this->addresses            = self::coerce_address_map( $data['addresses'] ?? null );
		$this->meta                 = self::coerce_meta_map( $data['meta'] ?? null );
		$this->plan_snapshot        = ( $data['plan_snapshot'] ?? null ) instanceof PlanSnapshot ? $data['plan_snapshot'] : null;
	}

	/**
	 * Build a new, unsaved contract.
	 *
	 * @param array<string, mixed> $args Contract attributes.
	 * @throws DomainException If the contract attributes are not valid.
	 */
	public static function create( array $args ): self {
		// A new contract is always unsaved; never adopt a caller-supplied id.
		unset( $args['id'] );

		$contract = new self( $args );

		if ( ! ContractStatus::is_valid( $contract->status ) ) {
			throw new DomainException( sprintf( 'Contract: invalid status "%s".', $contract->status ) );
		}

		if ( ! in_array( $contract->schedule_source, array( self::SCHEDULE_SOURCE_PRIMITIVE, self::SCHEDULE_SOURCE_GATEWAY ), true ) ) {
			throw new DomainException( sprintf( 'Contract: invalid schedule source "%s".', $contract->schedule_source ) );
		}

		return $contract;
	}

	/**
	 * Hydrate from stored rows.
	 *
	 * The frozen plan terms ride second, ahead of the child rows: a contract without
	 * its plan is pretty pointless, so the snapshot is hydrated on the same footing as
	 * items / addresses / meta rather than through a separate mutation step.
	 *
	 * @param array<string, mixed>                $row           Contract row.
	 * @param PlanSnapshot|null                   $plan_snapshot Frozen plan terms for the row's `plan_snapshot_id`, or null.
	 * @param array<int, array<string, mixed>>    $items         Item rows.
	 * @param array<string, array<string, mixed>> $addresses     Address rows keyed by type.
	 * @param array<string, string>               $meta          Meta as key => value.
	 */
	public static function from_storage( array $row, ?PlanSnapshot $plan_snapshot = null, array $items = array(), array $addresses = array(), array $meta = array() ): self {
		$contract = new self(
			array_merge(
				$row,
				array(
					'items'     => $items,
					'addresses' => $addresses,
					'meta'      => $meta,
				)
			)
		);

		if ( null !== $plan_snapshot ) {
			$contract->set_plan_snapshot( $plan_snapshot );
		}

		return $contract;
	}

	/**
	 * Contract id, or null before save.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Assign the id after a successful insert.
	 *
	 * @param int $id Contract id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Lifecycle status.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Transition the contract to a new status.
	 *
	 * @param string $status Target status.
	 * @throws DomainException If the transition is not allowed by ContractStatus.
	 */
	public function set_status( string $status ): void {
		if ( $status === $this->status ) {
			return;
		}

		ContractStatus::assert_transition_allowed( $this->status, $status );

		$this->status = $status;
	}

	/**
	 * Owning customer id.
	 */
	public function get_customer_id(): int {
		return $this->customer_id;
	}

	/**
	 * ISO-4217 currency code.
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Foreign key to the selling plan.
	 */
	public function get_selling_plan_id(): int {
		return $this->selling_plan_id;
	}

	/**
	 * Foreign key to the origin order, or null for a manual/admin contract.
	 */
	public function get_origin_order_id(): ?int {
		return $this->origin_order_id;
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->extension_slug;
	}

	/**
	 * The payment instrument as an immutable reference.
	 */
	public function get_payment_instrument(): InstrumentRef {
		return new InstrumentRef( $this->payment_token_id, $this->payment_method, $this->payment_method_title );
	}

	/**
	 * Set the payment instrument from an immutable reference.
	 *
	 * @param InstrumentRef $instrument Payment instrument reference.
	 */
	public function set_payment_instrument( InstrumentRef $instrument ): void {
		$this->payment_token_id     = $instrument->get_token_id();
		$this->payment_method       = $instrument->get_gateway();
		$this->payment_method_title = $instrument->get_title();
	}

	/**
	 * Next renewal attempt, or null.
	 */
	public function get_next_payment_gmt(): ?string {
		return $this->next_payment_gmt;
	}

	/**
	 * Set the live schedule (when the next renewal fires).
	 *
	 * @param string|null $next_payment_gmt GMT string or null.
	 */
	public function set_next_payment_gmt( ?string $next_payment_gmt ): void {
		$this->next_payment_gmt = $next_payment_gmt;
	}

	/**
	 * Latest/live plan snapshot row id, or null.
	 */
	public function get_plan_snapshot_id(): ?int {
		return $this->plan_snapshot_id;
	}

	/**
	 * Set the latest/live plan snapshot row id.
	 *
	 * @param int|null $plan_snapshot_id Snapshot row id, or null.
	 */
	public function set_plan_snapshot_id( ?int $plan_snapshot_id ): void {
		$this->plan_snapshot_id = $plan_snapshot_id;
	}

	/**
	 * Latest/live items snapshot row id, or null.
	 */
	public function get_items_snapshot_id(): ?int {
		return $this->items_snapshot_id;
	}

	/**
	 * Set the latest/live items snapshot row id.
	 *
	 * @param int|null $items_snapshot_id Snapshot row id, or null.
	 */
	public function set_items_snapshot_id( ?int $items_snapshot_id ): void {
		$this->items_snapshot_id = $items_snapshot_id;
	}

	/**
	 * The frozen plan terms for `plan_snapshot_id`, when hydrated - the per-contract
	 * billing cadence read off the snapshot rather than the live plan. Null when the
	 * read path did not hydrate it, or the contract carries no plan snapshot.
	 */
	public function get_plan_snapshot(): ?PlanSnapshot {
		return $this->plan_snapshot;
	}

	/**
	 * Attach the frozen plan terms for `plan_snapshot_id` (repository hydration).
	 *
	 * @param PlanSnapshot $plan_snapshot The decoded plan snapshot.
	 */
	public function set_plan_snapshot( PlanSnapshot $plan_snapshot ): void {
		$this->plan_snapshot = $plan_snapshot;
	}

	/**
	 * Live billing total (decimal-safe string).
	 */
	public function get_billing_total(): string {
		return $this->billing_total;
	}

	/**
	 * Set the live billing total, normalized to the storage scale.
	 *
	 * @param string $billing_total Money value (decimal string or number).
	 */
	public function set_billing_total( string $billing_total ): void {
		$this->billing_total = MoneyScale::normalize_money( $billing_total );
	}

	/**
	 * Live discount total (decimal-safe string).
	 */
	public function get_discount_total(): string {
		return $this->discount_total;
	}

	/**
	 * Set the live discount total, normalized to the storage scale.
	 *
	 * @param string $discount_total Money value (decimal string or number).
	 */
	public function set_discount_total( string $discount_total ): void {
		$this->discount_total = MoneyScale::normalize_money( $discount_total );
	}

	/**
	 * Live shipping total (decimal-safe string).
	 */
	public function get_shipping_total(): string {
		return $this->shipping_total;
	}

	/**
	 * Set the live shipping total, normalized to the storage scale.
	 *
	 * @param string $shipping_total Money value (decimal string or number).
	 */
	public function set_shipping_total( string $shipping_total ): void {
		$this->shipping_total = MoneyScale::normalize_money( $shipping_total );
	}

	/**
	 * Live tax total (decimal-safe string).
	 */
	public function get_tax_total(): string {
		return $this->tax_total;
	}

	/**
	 * Set the live tax total, normalized to the storage scale.
	 *
	 * @param string $tax_total Money value (decimal string or number).
	 */
	public function set_tax_total( string $tax_total ): void {
		$this->tax_total = MoneyScale::normalize_money( $tax_total );
	}

	/**
	 * When the contract last billed successfully, or null. GMT string.
	 */
	public function get_last_payment_gmt(): ?string {
		return $this->last_payment_gmt;
	}

	/**
	 * Set when the contract last billed successfully.
	 *
	 * @param string|null $last_payment_gmt GMT string or null.
	 */
	public function set_last_payment_gmt( ?string $last_payment_gmt ): void {
		$this->last_payment_gmt = $last_payment_gmt;
	}

	/**
	 * When the contract last attempted a charge, or null. GMT string.
	 */
	public function get_last_attempt_gmt(): ?string {
		return $this->last_attempt_gmt;
	}

	/**
	 * Set when the contract last attempted a charge.
	 *
	 * @param string|null $last_attempt_gmt GMT string or null.
	 */
	public function set_last_attempt_gmt( ?string $last_attempt_gmt ): void {
		$this->last_attempt_gmt = $last_attempt_gmt;
	}

	/**
	 * When the contract's trial ends (or ended), or null. GMT string.
	 */
	public function get_trial_end_gmt(): ?string {
		return $this->trial_end_gmt;
	}

	/**
	 * Set when the contract's trial ends.
	 *
	 * @param string|null $trial_end_gmt GMT string or null.
	 */
	public function set_trial_end_gmt( ?string $trial_end_gmt ): void {
		$this->trial_end_gmt = $trial_end_gmt;
	}

	/**
	 * When the contract ends (or ended), or null. GMT string.
	 */
	public function get_end_gmt(): ?string {
		return $this->end_gmt;
	}

	/**
	 * Set when the contract ends.
	 *
	 * @param string|null $end_gmt GMT string or null.
	 */
	public function set_end_gmt( ?string $end_gmt ): void {
		$this->end_gmt = $end_gmt;
	}

	/**
	 * Start timestamp (GMT string).
	 */
	public function get_start_gmt(): string {
		return $this->start_gmt;
	}

	/**
	 * Who runs renewals: 'primitive' or 'gateway'.
	 */
	public function get_schedule_source(): string {
		return $this->schedule_source;
	}

	/**
	 * Line items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Addresses keyed by type.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_addresses(): array {
		return $this->addresses;
	}

	/**
	 * Contract meta as key => value.
	 *
	 * @return array<string, string>
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * Serialize the contract row (excluding generated id/timestamps).
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'status'               => $this->status,
			'customer_id'          => $this->customer_id,
			'currency'             => $this->currency,
			'selling_plan_id'      => $this->selling_plan_id,
			'origin_order_id'      => $this->origin_order_id,
			'extension_slug'       => $this->extension_slug,
			'payment_method'       => $this->payment_method,
			'payment_method_title' => $this->payment_method_title,
			'payment_token_id'     => $this->payment_token_id,
			'start_gmt'            => $this->start_gmt,
			'next_payment_gmt'     => $this->next_payment_gmt,
			'plan_snapshot_id'     => $this->plan_snapshot_id,
			'items_snapshot_id'    => $this->items_snapshot_id,
			'billing_total'        => $this->billing_total,
			'discount_total'       => $this->discount_total,
			'shipping_total'       => $this->shipping_total,
			'tax_total'            => $this->tax_total,
			'last_payment_gmt'     => $this->last_payment_gmt,
			'last_attempt_gmt'     => $this->last_attempt_gmt,
			'trial_end_gmt'        => $this->trial_end_gmt,
			'end_gmt'              => $this->end_gmt,
			'schedule_source'      => $this->schedule_source,
		);
	}

	/**
	 * Shape a caller-supplied value into the line-item row list. A non-array yields
	 * no items; non-array elements are skipped.
	 *
	 * @param mixed $value Caller-supplied items.
	 * @return array<int, array<string, mixed>>
	 */
	private static function coerce_item_rows( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$rows = array();
		foreach ( $value as $row ) {
			if ( is_array( $row ) ) {
				$rows[] = self::coerce_string_keyed( $row );
			}
		}

		return $rows;
	}

	/**
	 * Shape a caller-supplied value into the addresses map keyed by type. A non-array
	 * yields an empty map; non-array elements are skipped.
	 *
	 * @param mixed $value Caller-supplied addresses.
	 * @return array<string, array<string, mixed>>
	 */
	private static function coerce_address_map( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$map = array();
		foreach ( $value as $type => $address ) {
			if ( is_array( $address ) ) {
				$map[ (string) $type ] = self::coerce_string_keyed( $address );
			}
		}

		return $map;
	}

	/**
	 * Shape a caller-supplied value into the meta map (string => string). A non-array
	 * yields an empty map.
	 *
	 * @param mixed $value Caller-supplied meta.
	 * @return array<string, string>
	 */
	private static function coerce_meta_map( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$map = array();
		foreach ( $value as $key => $meta_value ) {
			$map[ (string) $key ] = ScalarCoercion::coerce_string( $meta_value );
		}

		return $map;
	}

	/**
	 * Re-key an array as a string-keyed map, recovering the `array<string, mixed>`
	 * row shape from an otherwise `int|string`-keyed array.
	 *
	 * @param array<int|string, mixed> $value Array to re-key.
	 * @return array<string, mixed>
	 */
	private static function coerce_string_keyed( array $value ): array {
		$result = array();
		foreach ( $value as $key => $entry ) {
			$result[ (string) $key ] = $entry;
		}

		return $result;
	}
}

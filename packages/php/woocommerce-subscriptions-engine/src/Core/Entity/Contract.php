<?php
/**
 * Contract - the stable, customer-facing identity of a subscription and the live
 * source of truth for its current state. Manages core data for the subscription
 * and enforces lifecycle transitions through {@see ContractStatus}.
 *
 * The contract is the live source of truth (the current reality, mutable): it
 * holds its stable identity, the live schedule (`next_payment_gmt`, which drives
 * the due scan), the latest/live snapshot references (`plan_snapshot_id` /
 * `items_snapshot_id`), and the live config values - the four totals
 * (`billing_total` / `discount_total` / `shipping_total` / `tax_total`) and the
 * four stamps (`last_payment_gmt` / `last_attempt_gmt` / `trial_end_gmt` /
 * `end_gmt`). These are LIVE VALUES, not caches of cycles. Sync is single-direction
 * downward (contract -> snapshot, contract -> cycle), never cycle -> contract: a
 * live change writes a new snapshot row and the contract repoints, and a billing
 * cycle freezes whatever the contract points at now.
 *
 * The contract does not hold a cycle graph in memory; cycles are fetched on demand
 * through the repository. A chain is just the pair `(contract_id, kind)`; its head
 * and counters are derived from the cycle rows, so there is no generic per-contract
 * cycle count. `origin_order_id` is nullable (a manual/admin contract has no origin
 * order); for a checkout contract it equals cycle 1's `order_id`.
 *
 * Timestamps are GMT strings (`Y-m-d H:i:s`). Money totals are decimal-safe
 * strings normalized to the storage scale. The payment instrument is exposed as an
 * {@see InstrumentRef} rather than a live payment token.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;

defined( 'ABSPATH' ) || exit;

/**
 * Contract entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) contract or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Contract {

	use ScalarCoercion;
	use MoneyScale;

	const SCHEDULE_SOURCE_PRIMITIVE = 'primitive';
	const SCHEDULE_SOURCE_GATEWAY   = 'gateway';

	const ADDRESS_BILLING  = 'billing';
	const ADDRESS_SHIPPING = 'shipping';

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
	 * Foreign key to the order that triggered this contract, or null for a
	 * manual/admin contract with no origin order. For a checkout contract it equals
	 * the first cycle's `order_id`.
	 *
	 * @var int|null
	 */
	private $origin_order_id;

	/**
	 * Owning extension slug, or null until owner semantics are assigned.
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
	 * The live schedule: when the next renewal fires, or null. GMT string. This is
	 * the live source of truth the due scan keys on; advancing it is what moves the
	 * contract forward, and a billing cycle freezes its period from it.
	 *
	 * @var string|null
	 */
	private $next_payment_gmt;

	/**
	 * Latest/live plan snapshot row id, or null until one is recorded. A billing
	 * cycle freezes whatever the contract points at now.
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
	 * Use {@see self::create()} or {@see self::from_storage()}.
	 *
	 * Values are coerced to these types at the {@see self::create()} /
	 * {@see self::from_storage()} boundary, so the constructor takes already-typed
	 * arguments.
	 *
	 * @param int|null                            $id                   Contract id, or null before save.
	 * @param string                              $status               Lifecycle status.
	 * @param int                                 $customer_id          Owning customer id.
	 * @param string                              $currency             ISO-4217 currency code.
	 * @param int                                 $selling_plan_id      Foreign key to the selling plan.
	 * @param int|null                            $origin_order_id      Foreign key to the origin order, or null.
	 * @param string|null                         $extension_slug       Owning extension slug, or null.
	 * @param string|null                         $payment_method       Gateway code, or null.
	 * @param string|null                         $payment_method_title Gateway title, or null.
	 * @param int|null                            $payment_token_id     Payment token id, or null.
	 * @param string                              $start_gmt            Start timestamp (GMT string).
	 * @param string|null                         $next_payment_gmt     Live schedule (GMT string), or null.
	 * @param int|null                            $plan_snapshot_id     Latest plan snapshot id, or null.
	 * @param int|null                            $items_snapshot_id    Latest items snapshot id, or null.
	 * @param string                              $billing_total        Live billing total (decimal-safe string).
	 * @param string                              $discount_total       Live discount total (decimal-safe string).
	 * @param string                              $shipping_total       Live shipping total (decimal-safe string).
	 * @param string                              $tax_total            Live tax total (decimal-safe string).
	 * @param string|null                         $last_payment_gmt     Last successful bill (GMT string), or null.
	 * @param string|null                         $last_attempt_gmt     Last charge attempt (GMT string), or null.
	 * @param string|null                         $trial_end_gmt        Trial end (GMT string), or null.
	 * @param string|null                         $end_gmt              Contract end (GMT string), or null.
	 * @param string                              $schedule_source      Who runs renewals.
	 * @param array<int, array<string, mixed>>    $items                Line items.
	 * @param array<string, array<string, mixed>> $addresses            Addresses keyed by type.
	 * @param array<string, string>               $meta                 Contract meta as key => value.
	 */
	private function __construct(
		?int $id,
		string $status,
		int $customer_id,
		string $currency,
		int $selling_plan_id,
		?int $origin_order_id,
		?string $extension_slug,
		?string $payment_method,
		?string $payment_method_title,
		?int $payment_token_id,
		string $start_gmt,
		?string $next_payment_gmt,
		?int $plan_snapshot_id,
		?int $items_snapshot_id,
		string $billing_total,
		string $discount_total,
		string $shipping_total,
		string $tax_total,
		?string $last_payment_gmt,
		?string $last_attempt_gmt,
		?string $trial_end_gmt,
		?string $end_gmt,
		string $schedule_source,
		array $items,
		array $addresses,
		array $meta
	) {
		$this->id                   = $id;
		$this->status               = $status;
		$this->customer_id          = $customer_id;
		$this->currency             = $currency;
		$this->selling_plan_id      = $selling_plan_id;
		$this->origin_order_id      = $origin_order_id;
		$this->extension_slug       = $extension_slug;
		$this->payment_method       = $payment_method;
		$this->payment_method_title = $payment_method_title;
		$this->payment_token_id     = $payment_token_id;
		$this->start_gmt            = $start_gmt;
		$this->next_payment_gmt     = $next_payment_gmt;
		$this->plan_snapshot_id     = $plan_snapshot_id;
		$this->items_snapshot_id    = $items_snapshot_id;
		$this->billing_total        = $billing_total;
		$this->discount_total       = $discount_total;
		$this->shipping_total       = $shipping_total;
		$this->tax_total            = $tax_total;
		$this->last_payment_gmt     = $last_payment_gmt;
		$this->last_attempt_gmt     = $last_attempt_gmt;
		$this->trial_end_gmt        = $trial_end_gmt;
		$this->end_gmt              = $end_gmt;
		$this->schedule_source      = $schedule_source;
		$this->items                = $items;
		$this->addresses            = $addresses;
		$this->meta                 = $meta;
	}

	/**
	 * Build a new, unsaved contract.
	 *
	 * @param array<string, mixed> $args Contract attributes.
	 * @throws DomainException If the contract attributes are not valid.
	 */
	public static function create( array $args ): self {
		$status = self::coerce_string( $args['status'] ?? null, ContractStatus::ACTIVE );
		if ( ! ContractStatus::is_valid( $status ) ) {
			throw new DomainException(
				sprintf( 'Contract: invalid status "%s".', $status )
			);
		}

		$schedule_source = self::coerce_string( $args['schedule_source'] ?? null, self::SCHEDULE_SOURCE_PRIMITIVE );
		if ( ! in_array( $schedule_source, array( self::SCHEDULE_SOURCE_PRIMITIVE, self::SCHEDULE_SOURCE_GATEWAY ), true ) ) {
			throw new DomainException(
				sprintf( 'Contract: invalid schedule source "%s".', $schedule_source )
			);
		}

		return new self(
			null,
			$status,
			self::coerce_int( $args['customer_id'] ?? null ),
			self::coerce_string( $args['currency'] ?? null ),
			self::coerce_int( $args['selling_plan_id'] ?? null ),
			self::coerce_nullable_int( $args['origin_order_id'] ?? null ),
			is_string( $args['extension_slug'] ?? null ) ? $args['extension_slug'] : null,
			is_string( $args['payment_method'] ?? null ) ? $args['payment_method'] : null,
			is_string( $args['payment_method_title'] ?? null ) ? $args['payment_method_title'] : null,
			isset( $args['payment_token_id'] ) ? self::coerce_int( $args['payment_token_id'] ) : null,
			self::coerce_string( $args['start_gmt'] ?? null ),
			self::coerce_nullable_string( $args['next_payment_gmt'] ?? null ),
			self::coerce_nullable_int( $args['plan_snapshot_id'] ?? null ),
			self::coerce_nullable_int( $args['items_snapshot_id'] ?? null ),
			self::normalize_money( $args['billing_total'] ?? '0' ),
			self::normalize_money( $args['discount_total'] ?? '0' ),
			self::normalize_money( $args['shipping_total'] ?? '0' ),
			self::normalize_money( $args['tax_total'] ?? '0' ),
			self::coerce_nullable_string( $args['last_payment_gmt'] ?? null ),
			self::coerce_nullable_string( $args['last_attempt_gmt'] ?? null ),
			self::coerce_nullable_string( $args['trial_end_gmt'] ?? null ),
			self::coerce_nullable_string( $args['end_gmt'] ?? null ),
			$schedule_source,
			self::coerce_item_rows( $args['items'] ?? null ),
			self::coerce_address_map( $args['addresses'] ?? null ),
			self::coerce_meta_map( $args['meta'] ?? null )
		);
	}

	/**
	 * Hydrate from stored rows.
	 *
	 * @param array<string, mixed>                $row       Contract row.
	 * @param array<int, array<string, mixed>>    $items     Item rows.
	 * @param array<string, array<string, mixed>> $addresses Address rows keyed by type.
	 * @param array<string, string>               $meta      Meta as key => value.
	 */
	public static function from_storage( array $row, array $items = array(), array $addresses = array(), array $meta = array() ): self {
		return new self(
			isset( $row['id'] ) ? self::coerce_int( $row['id'] ) : null,
			self::coerce_string( $row['status'] ?? null ),
			self::coerce_int( $row['customer_id'] ?? null ),
			self::coerce_string( $row['currency'] ?? null ),
			self::coerce_int( $row['selling_plan_id'] ?? null ),
			self::coerce_nullable_int( $row['origin_order_id'] ?? null ),
			isset( $row['extension_slug'] ) ? self::coerce_nullable_string( $row['extension_slug'] ) : null,
			isset( $row['payment_method'] ) ? self::coerce_nullable_string( $row['payment_method'] ) : null,
			isset( $row['payment_method_title'] ) ? self::coerce_nullable_string( $row['payment_method_title'] ) : null,
			isset( $row['payment_token_id'] ) ? self::coerce_int( $row['payment_token_id'] ) : null,
			self::coerce_string( $row['start_gmt'] ?? null ),
			self::coerce_nullable_string( $row['next_payment_gmt'] ?? null ),
			self::coerce_nullable_int( $row['plan_snapshot_id'] ?? null ),
			self::coerce_nullable_int( $row['items_snapshot_id'] ?? null ),
			self::normalize_money( $row['billing_total'] ?? '0' ),
			self::normalize_money( $row['discount_total'] ?? '0' ),
			self::normalize_money( $row['shipping_total'] ?? '0' ),
			self::normalize_money( $row['tax_total'] ?? '0' ),
			self::coerce_nullable_string( $row['last_payment_gmt'] ?? null ),
			self::coerce_nullable_string( $row['last_attempt_gmt'] ?? null ),
			self::coerce_nullable_string( $row['trial_end_gmt'] ?? null ),
			self::coerce_nullable_string( $row['end_gmt'] ?? null ),
			self::coerce_string( $row['schedule_source'] ?? null, self::SCHEDULE_SOURCE_PRIMITIVE ),
			$items,
			$addresses,
			$meta
		);
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
		$this->billing_total = self::normalize_money( $billing_total );
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
		$this->discount_total = self::normalize_money( $discount_total );
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
		$this->shipping_total = self::normalize_money( $shipping_total );
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
		$this->tax_total = self::normalize_money( $tax_total );
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
	 * Shape a caller-supplied value into the line-item row list.
	 *
	 * A non-array value yields no items; each array element is re-keyed as a
	 * string-keyed row to recover the item-row shape from an arbitrary input
	 * array (whose key type is otherwise unknown), and non-array elements are
	 * skipped.
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
	 * Shape a caller-supplied value into the addresses map keyed by type.
	 *
	 * A non-array value yields an empty map; each array element is re-keyed as a
	 * string-keyed address, under a string type key. Non-array elements are
	 * skipped.
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
	 * Shape a caller-supplied value into the meta map (string => string).
	 *
	 * A non-array value yields an empty map; each entry's key and value are
	 * coerced to strings.
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
			$map[ (string) $key ] = self::coerce_string( $meta_value );
		}

		return $map;
	}

	/**
	 * Re-key an array as a string-keyed map.
	 *
	 * Recovers the `array<string, mixed>` type the row shapes model from an
	 * arbitrary array whose key type is otherwise `int|string`. String keys pass
	 * through unchanged.
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

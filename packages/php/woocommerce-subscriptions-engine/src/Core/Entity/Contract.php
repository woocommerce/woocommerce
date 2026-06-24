<?php
/**
 * Contract - the stable, customer-facing identity of a subscription. Manages
 * core data for the subscription and enforces lifecycle transitions through
 * {@see ContractStatus}.
 *
 * Money totals are kept as decimal-safe strings; timestamps are GMT strings
 * (`Y-m-d H:i:s`). The payment instrument is exposed as an {@see InstrumentRef}
 * rather than a live payment token.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * Contract entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) contract or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Contract {

	use ScalarCoercion;

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
	 * Foreign key to the order that triggered this contract.
	 *
	 * @var int
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
	 * Recurring total per cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $billing_total;

	/**
	 * Recurring discount per cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $discount_total;

	/**
	 * Recurring shipping per cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $shipping_total;

	/**
	 * Recurring tax per cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $tax_total;

	/**
	 * When the contract goes (or went) active. GMT string.
	 *
	 * @var string
	 */
	private $start_gmt;

	/**
	 * Next renewal attempt, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $next_payment_gmt;

	/**
	 * Last successful renewal payment, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $last_payment_gmt;

	/**
	 * Last attempted renewal cycle regardless of outcome, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $last_attempt_gmt;

	/**
	 * End of trial window, or null. GMT string.
	 *
	 * @var string|null
	 */
	private $trial_end_gmt;

	/**
	 * Hard end (cancelled / expired / max_cycles reached), or null. GMT string.
	 *
	 * @var string|null
	 */
	private $end_gmt;

	/**
	 * Count of successfully-paid renewal cycles.
	 *
	 * @var int
	 */
	private $cycle_count;

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
	 * @param array<string, mixed> $fields Internal field map.
	 */
	private function __construct( array $fields ) {
		$this->id                   = self::coerce_nullable_int( $fields['id'] ?? null );
		$this->status               = self::coerce_string( $fields['status'] ?? null );
		$this->customer_id          = self::coerce_int( $fields['customer_id'] ?? null );
		$this->currency             = self::coerce_string( $fields['currency'] ?? null );
		$this->selling_plan_id      = self::coerce_int( $fields['selling_plan_id'] ?? null );
		$this->origin_order_id      = self::coerce_int( $fields['origin_order_id'] ?? null );
		$this->extension_slug       = self::coerce_nullable_string( $fields['extension_slug'] ?? null );
		$this->payment_method       = self::coerce_nullable_string( $fields['payment_method'] ?? null );
		$this->payment_method_title = self::coerce_nullable_string( $fields['payment_method_title'] ?? null );
		$this->payment_token_id     = self::coerce_nullable_int( $fields['payment_token_id'] ?? null );
		$this->billing_total        = self::coerce_string( $fields['billing_total'] ?? null, '0' );
		$this->discount_total       = self::coerce_string( $fields['discount_total'] ?? null, '0' );
		$this->shipping_total       = self::coerce_string( $fields['shipping_total'] ?? null, '0' );
		$this->tax_total            = self::coerce_string( $fields['tax_total'] ?? null, '0' );
		$this->start_gmt            = self::coerce_string( $fields['start_gmt'] ?? null );
		$this->next_payment_gmt     = self::coerce_nullable_string( $fields['next_payment_gmt'] ?? null );
		$this->last_payment_gmt     = self::coerce_nullable_string( $fields['last_payment_gmt'] ?? null );
		$this->last_attempt_gmt     = self::coerce_nullable_string( $fields['last_attempt_gmt'] ?? null );
		$this->trial_end_gmt        = self::coerce_nullable_string( $fields['trial_end_gmt'] ?? null );
		$this->end_gmt              = self::coerce_nullable_string( $fields['end_gmt'] ?? null );
		$this->cycle_count          = self::coerce_int( $fields['cycle_count'] ?? null );
		$this->schedule_source      = self::coerce_string( $fields['schedule_source'] ?? null );
		$this->items                = self::coerce_items( $fields['items'] ?? null );
		$this->addresses            = self::coerce_addresses( $fields['addresses'] ?? null );
		$this->meta                 = self::coerce_meta( $fields['meta'] ?? null );
	}

	/**
	 * Filter raw items to retain only array-valued entries.
	 *
	 * Individual item field structure is not normalized here; each kept entry is
	 * passed through as-is.
	 *
	 * @param mixed $value Raw items value.
	 * @return array<int, array<string, mixed>>
	 */
	private static function coerce_items( $value ): array {
		$items = array();
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					$items[] = $item;
				}
			}
		}
		return $items;
	}

	/**
	 * Coerce raw address rows to a type => fields map.
	 *
	 * A flat passthrough keyed by address type: only the types actually present
	 * (and array-valued) are kept, so a contract with no addresses round-trips to
	 * an empty map rather than two empty billing/shipping rows.
	 *
	 * @param mixed $value Raw addresses value.
	 * @return array<string, array<string, mixed>>
	 */
	private static function coerce_addresses( $value ): array {
		$addresses = array();
		if ( is_array( $value ) ) {
			foreach ( $value as $type => $address ) {
				if ( is_array( $address ) ) {
					$addresses[ (string) $type ] = $address;
				}
			}
		}
		return $addresses;
	}

	/**
	 * Coerce raw meta to a string => string map.
	 *
	 * @param mixed $value Raw meta value.
	 * @return array<string, string>
	 */
	private static function coerce_meta( $value ): array {
		$meta = array();
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $val ) {
				$meta[ (string) $key ] = is_scalar( $val ) ? (string) $val : '';
			}
		}
		return $meta;
	}

	/**
	 * Build a new, unsaved contract.
	 *
	 * Required: `selling_plan_id` and `origin_order_id` (positive integers),
	 * `customer_id` (a non-negative integer; 0 is a guest), and `currency`,
	 * `start_gmt` (non-empty strings). These are validated up front so an omitted
	 * field fails loud here rather than coercing to a silent `0`/`''` and producing
	 * an observably invalid contract. All other fields are optional and default in
	 * the constructor.
	 *
	 * @param array<string, mixed> $args Contract attributes.
	 * @throws DomainException If a required attribute is missing or invalid.
	 */
	public static function create( array $args ): self {
		// customer_id 0 is a valid guest contract, so it is non-negative rather
		// than strictly positive; an absent key is still rejected (null is not numeric).
		$customer_id = $args['customer_id'] ?? null;
		if ( ! is_numeric( $customer_id ) || (int) $customer_id < 0 ) {
			throw new DomainException(
				'Contract: customer_id is required and must be a non-negative integer.'
			);
		}

		foreach ( array( 'selling_plan_id', 'origin_order_id' ) as $required_id ) {
			$value = $args[ $required_id ] ?? null;
			if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
				throw new DomainException(
					sprintf( 'Contract: %s is required and must be a positive integer.', $required_id )
				);
			}
		}

		foreach ( array( 'currency', 'start_gmt' ) as $required_string ) {
			$value = $args[ $required_string ] ?? null;
			if ( ! is_scalar( $value ) || '' === (string) $value ) {
				throw new DomainException(
					sprintf( 'Contract: %s is required and must be a non-empty string.', $required_string )
				);
			}
		}

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

		// The constructor is the single coercion boundary; pass raw values through.
		return new self(
			array(
				'id'                   => null,
				'status'               => $status,
				'customer_id'          => $args['customer_id'] ?? null,
				'currency'             => $args['currency'] ?? null,
				'selling_plan_id'      => $args['selling_plan_id'] ?? null,
				'origin_order_id'      => $args['origin_order_id'] ?? null,
				'extension_slug'       => $args['extension_slug'] ?? null,
				'payment_method'       => $args['payment_method'] ?? null,
				'payment_method_title' => $args['payment_method_title'] ?? null,
				'payment_token_id'     => $args['payment_token_id'] ?? null,
				'billing_total'        => $args['billing_total'] ?? null,
				'discount_total'       => $args['discount_total'] ?? null,
				'shipping_total'       => $args['shipping_total'] ?? null,
				'tax_total'            => $args['tax_total'] ?? null,
				'start_gmt'            => $args['start_gmt'] ?? null,
				'next_payment_gmt'     => $args['next_payment_gmt'] ?? null,
				'last_payment_gmt'     => null,
				'last_attempt_gmt'     => null,
				'trial_end_gmt'        => $args['trial_end_gmt'] ?? null,
				'end_gmt'              => null,
				'cycle_count'          => 0,
				'schedule_source'      => $schedule_source,
				'items'                => $args['items'] ?? null,
				'addresses'            => $args['addresses'] ?? null,
				'meta'                 => $args['meta'] ?? null,
			)
		);
	}

	/**
	 * Hydrate from stored rows.
	 *
	 * @param array<string, mixed>                $row       Contract row.
	 * @param array<int, array<string, mixed>>    $items     Item rows.
	 * @param array<string, array<string, mixed>> $addresses Address rows keyed by type.
	 * @param array<string, string>               $meta      Meta as key => value.
	 * @throws DomainException If the stored cycle_count or schedule_source is invalid.
	 */
	public static function from_storage( array $row, array $items = array(), array $addresses = array(), array $meta = array() ): self {
		// Hydration is a trust boundary: a WordPress database can be mutated outside
		// this engine's flows, and these fields drive money and scheduling math. A
		// corrupted cycle_count or schedule_source is rejected loudly here rather
		// than silently mis-charging a renewal or mis-routing the schedule.
		$cycle_count = self::coerce_nullable_int( $row['cycle_count'] ?? null );
		if ( null === $cycle_count ) {
			throw new DomainException(
				sprintf( 'Contract: stored cycle_count must be an integer, got %s.', gettype( $row['cycle_count'] ?? null ) )
			);
		}
		if ( $cycle_count < 0 ) {
			throw new DomainException(
				sprintf( 'Contract: stored cycle_count must be 0 or greater, got %d.', $cycle_count )
			);
		}

		$schedule_source = self::coerce_string( $row['schedule_source'] ?? null, self::SCHEDULE_SOURCE_PRIMITIVE );
		if ( ! in_array( $schedule_source, array( self::SCHEDULE_SOURCE_PRIMITIVE, self::SCHEDULE_SOURCE_GATEWAY ), true ) ) {
			throw new DomainException(
				sprintf( 'Contract: stored schedule source must be primitive or gateway, got "%s".', $schedule_source )
			);
		}

		// The constructor is the single coercion boundary; pass raw row values through.
		return new self(
			array(
				'id'                   => $row['id'] ?? null,
				'status'               => $row['status'] ?? null,
				'customer_id'          => $row['customer_id'] ?? null,
				'currency'             => $row['currency'] ?? null,
				'selling_plan_id'      => $row['selling_plan_id'] ?? null,
				'origin_order_id'      => $row['origin_order_id'] ?? null,
				'extension_slug'       => $row['extension_slug'] ?? null,
				'payment_method'       => $row['payment_method'] ?? null,
				'payment_method_title' => $row['payment_method_title'] ?? null,
				'payment_token_id'     => $row['payment_token_id'] ?? null,
				'billing_total'        => $row['billing_total'] ?? null,
				'discount_total'       => $row['discount_total'] ?? null,
				'shipping_total'       => $row['shipping_total'] ?? null,
				'tax_total'            => $row['tax_total'] ?? null,
				'start_gmt'            => $row['start_gmt'] ?? null,
				'next_payment_gmt'     => $row['next_payment_gmt'] ?? null,
				'last_payment_gmt'     => $row['last_payment_gmt'] ?? null,
				'last_attempt_gmt'     => $row['last_attempt_gmt'] ?? null,
				'trial_end_gmt'        => $row['trial_end_gmt'] ?? null,
				'end_gmt'              => $row['end_gmt'] ?? null,
				'cycle_count'          => $cycle_count,
				'schedule_source'      => $schedule_source,
				'items'                => $items,
				'addresses'            => $addresses,
				'meta'                 => $meta,
			)
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
	 * Foreign key to the origin order.
	 */
	public function get_origin_order_id(): int {
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
	 * Recurring total per cycle (decimal-safe string).
	 */
	public function get_billing_total(): string {
		return $this->billing_total;
	}

	/**
	 * Recurring discount per cycle (decimal-safe string).
	 */
	public function get_discount_total(): string {
		return $this->discount_total;
	}

	/**
	 * Recurring shipping per cycle (decimal-safe string).
	 */
	public function get_shipping_total(): string {
		return $this->shipping_total;
	}

	/**
	 * Recurring tax per cycle (decimal-safe string).
	 */
	public function get_tax_total(): string {
		return $this->tax_total;
	}

	/**
	 * Next renewal attempt, or null.
	 */
	public function get_next_payment_gmt(): ?string {
		return $this->next_payment_gmt;
	}

	/**
	 * Set the next renewal attempt timestamp.
	 *
	 * @param string|null $next_payment_gmt GMT string or null.
	 */
	public function set_next_payment_gmt( ?string $next_payment_gmt ): void {
		$this->next_payment_gmt = $next_payment_gmt;
	}

	/**
	 * Last successful renewal payment, or null. GMT string.
	 */
	public function get_last_payment_gmt(): ?string {
		return $this->last_payment_gmt;
	}

	/**
	 * Set the last successful renewal payment timestamp.
	 *
	 * @param string|null $last_payment_gmt GMT string or null.
	 */
	public function set_last_payment_gmt( ?string $last_payment_gmt ): void {
		$this->last_payment_gmt = $last_payment_gmt;
	}

	/**
	 * Start timestamp (GMT string).
	 */
	public function get_start_gmt(): string {
		return $this->start_gmt;
	}

	/**
	 * Count of successfully-paid renewal cycles.
	 */
	public function get_cycle_count(): int {
		return $this->cycle_count;
	}

	/**
	 * Set the count of successfully-paid renewal cycles.
	 *
	 * The renewal money-path advances this under a per-cycle idempotency guard,
	 * so the read-modify-write happens once per cycle and a plain setter is
	 * safe. An atomic, server-side increment becomes necessary once renewal
	 * accounting is driven by concurrent gateway webhooks (the cycles/attempts
	 * reshape); until then this is the simpler shape.
	 *
	 * @param int $cycle_count New cycle count.
	 * @throws DomainException If `$cycle_count` is negative.
	 */
	public function set_cycle_count( int $cycle_count ): void {
		if ( $cycle_count < 0 ) {
			throw new DomainException(
				sprintf( 'Contract: cycle_count must be 0 or greater, got %d.', $cycle_count )
			);
		}

		$this->cycle_count = $cycle_count;
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
			'billing_total'        => $this->billing_total,
			'discount_total'       => $this->discount_total,
			'shipping_total'       => $this->shipping_total,
			'tax_total'            => $this->tax_total,
			'start_gmt'            => $this->start_gmt,
			'next_payment_gmt'     => $this->next_payment_gmt,
			'last_payment_gmt'     => $this->last_payment_gmt,
			'last_attempt_gmt'     => $this->last_attempt_gmt,
			'trial_end_gmt'        => $this->trial_end_gmt,
			'end_gmt'              => $this->end_gmt,
			'cycle_count'          => $this->cycle_count,
			'schedule_source'      => $this->schedule_source,
		);
	}
}

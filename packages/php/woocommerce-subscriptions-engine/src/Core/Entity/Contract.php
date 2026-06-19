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

defined( 'ABSPATH' ) || exit;

/**
 * Contract entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) contract or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Contract {

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
	 * @var array{ billing: array<string, mixed>, shipping: array<string, mixed> }
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
		$this->id                   = $fields['id'];
		$this->status               = $fields['status'];
		$this->customer_id          = $fields['customer_id'];
		$this->currency             = $fields['currency'];
		$this->selling_plan_id      = $fields['selling_plan_id'];
		$this->origin_order_id      = $fields['origin_order_id'];
		$this->extension_slug       = $fields['extension_slug'];
		$this->payment_method       = $fields['payment_method'];
		$this->payment_method_title = $fields['payment_method_title'];
		$this->payment_token_id     = $fields['payment_token_id'];
		$this->billing_total        = $fields['billing_total'];
		$this->discount_total       = $fields['discount_total'];
		$this->shipping_total       = $fields['shipping_total'];
		$this->tax_total            = $fields['tax_total'];
		$this->start_gmt            = $fields['start_gmt'];
		$this->next_payment_gmt     = $fields['next_payment_gmt'];
		$this->last_payment_gmt     = $fields['last_payment_gmt'];
		$this->last_attempt_gmt     = $fields['last_attempt_gmt'];
		$this->trial_end_gmt        = $fields['trial_end_gmt'];
		$this->end_gmt              = $fields['end_gmt'];
		$this->cycle_count          = $fields['cycle_count'];
		$this->schedule_source      = $fields['schedule_source'];
		$this->items                = $fields['items'];
		$this->addresses            = $fields['addresses'];
		$this->meta                 = $fields['meta'];
	}

	/**
	 * Build a new, unsaved contract.
	 *
	 * @param array{
	 *     customer_id: int,
	 *     currency: string,
	 *     selling_plan_id: int,
	 *     origin_order_id: int,
	 *     start_gmt: string,
	 *     status?: string,
	 *     extension_slug?: string,
	 *     payment_method?: string,
	 *     payment_method_title?: string,
	 *     payment_token_id?: int,
	 *     billing_total: string,
	 *     discount_total?: string,
	 *     shipping_total?: string,
	 *     tax_total?: string,
	 *     next_payment_gmt?: string,
	 *     trial_end_gmt?: string,
	 *     schedule_source: string,
	 *     items: array<int, array<string, mixed>>,
	 *     addresses: array{ billing: array<string, mixed>, shipping: array<string, mixed> },
	 *     meta: array<string, string>,
	 * } $args Contract attributes.
	 * @throws DomainException If the contract attributes are not valid.
	 */
	public static function create( array $args ): self {
		$status = (string) ( $args['status'] ?? ContractStatus::ACTIVE );
		if ( ! ContractStatus::is_valid( $status ) ) {
			throw new DomainException(
				sprintf( 'Contract: invalid status "%s".', $status )
			);
		}

		$schedule_source = (string) ( $args['schedule_source'] ?? self::SCHEDULE_SOURCE_PRIMITIVE );
		if ( ! in_array( $schedule_source, array( self::SCHEDULE_SOURCE_PRIMITIVE, self::SCHEDULE_SOURCE_GATEWAY ), true ) ) {
			throw new DomainException(
				sprintf( 'Contract: invalid schedule source "%s".', $schedule_source )
			);
		}

		return new self(
			array(
				'id'                   => null,
				'status'               => $status,
				'customer_id'          => (int) $args['customer_id'],
				'currency'             => (string) $args['currency'],
				'selling_plan_id'      => (int) $args['selling_plan_id'],
				'origin_order_id'      => (int) $args['origin_order_id'],
				'extension_slug'       => is_string( $args['extension_slug'] ?? null ) ? $args['extension_slug'] : null,
				'payment_method'       => is_string( $args['payment_method'] ?? null ) ? $args['payment_method'] : null,
				'payment_method_title' => is_string( $args['payment_method_title'] ?? null ) ? $args['payment_method_title'] : null,
				'payment_token_id'     => isset( $args['payment_token_id'] ) ? (int) $args['payment_token_id'] : null,
				'billing_total'        => (string) ( $args['billing_total'] ?? '0' ),
				'discount_total'       => (string) ( $args['discount_total'] ?? '0' ),
				'shipping_total'       => (string) ( $args['shipping_total'] ?? '0' ),
				'tax_total'            => (string) ( $args['tax_total'] ?? '0' ),
				'start_gmt'            => (string) $args['start_gmt'],
				'next_payment_gmt'     => $args['next_payment_gmt'] ?? null,
				'last_payment_gmt'     => null,
				'last_attempt_gmt'     => null,
				'trial_end_gmt'        => is_string( $args['trial_end_gmt'] ?? null ) ? $args['trial_end_gmt'] : null,
				'end_gmt'              => null,
				'cycle_count'          => 0,
				'schedule_source'      => $schedule_source,
				'items'                => is_array( $args['items'] ?? null ) ? $args['items'] : array(),
				'addresses'            => is_array( $args['addresses'] ?? null ) ? $args['addresses'] : array(),
				'meta'                 => is_array( $args['meta'] ?? null ) ? $args['meta'] : array(),
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
	 */
	public static function from_storage( array $row, array $items = array(), array $addresses = array(), array $meta = array() ): self {
		return new self(
			array(
				'id'                   => isset( $row['id'] ) ? (int) $row['id'] : null,
				'status'               => (string) $row['status'],
				'customer_id'          => (int) $row['customer_id'],
				'currency'             => (string) $row['currency'],
				'selling_plan_id'      => (int) $row['selling_plan_id'],
				'origin_order_id'      => (int) $row['origin_order_id'],
				'extension_slug'       => isset( $row['extension_slug'] ) ? (string) $row['extension_slug'] : null,
				'payment_method'       => isset( $row['payment_method'] ) ? (string) $row['payment_method'] : null,
				'payment_method_title' => isset( $row['payment_method_title'] ) ? (string) $row['payment_method_title'] : null,
				'payment_token_id'     => isset( $row['payment_token_id'] ) ? (int) $row['payment_token_id'] : null,
				'billing_total'        => (string) ( $row['billing_total'] ?? '0' ),
				'discount_total'       => (string) ( $row['discount_total'] ?? '0' ),
				'shipping_total'       => (string) ( $row['shipping_total'] ?? '0' ),
				'tax_total'            => (string) ( $row['tax_total'] ?? '0' ),
				'start_gmt'            => (string) $row['start_gmt'],
				'next_payment_gmt'     => $row['next_payment_gmt'] ?? null,
				'last_payment_gmt'     => $row['last_payment_gmt'] ?? null,
				'last_attempt_gmt'     => $row['last_attempt_gmt'] ?? null,
				'trial_end_gmt'        => $row['trial_end_gmt'] ?? null,
				'end_gmt'              => $row['end_gmt'] ?? null,
				'cycle_count'          => (int) ( $row['cycle_count'] ?? 0 ),
				'schedule_source'      => (string) ( $row['schedule_source'] ?? self::SCHEDULE_SOURCE_PRIMITIVE ),
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

		if ( ! ContractStatus::can_transition( $this->status, $status ) ) {
			throw new DomainException(
				sprintf( 'Contract: illegal status transition from "%s" to "%s".', $this->status, $status )
			);
		}

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

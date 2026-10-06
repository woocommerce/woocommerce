<?php
/**
 * ContractView - a read-only view of a contract at the `Api\` boundary.
 *
 * Consumers read contracts through this view instead of the Core entity. Getters may
 * be added, never removed. Children (`items`, `addresses`) take the shape the
 * contracts write facade accepts; they are null when the read did not load them (list
 * reads) and an array when it did.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\View
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\View;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable contract view.
 */
final class ContractView {

	/**
	 * Contract row values keyed by field name.
	 *
	 * @var array{id: int, status: string, owner: ?string, customer_id: ?int, currency: ?string, selling_plan_id: ?int, origin_order_id: ?int, payment_method: ?string, payment_method_title: ?string, payment_token_id: ?int, start_gmt: ?string, next_payment_gmt: ?string, last_payment_gmt: ?string, last_attempt_gmt: ?string, trial_end_gmt: ?string, end_gmt: ?string, billing_total: string, discount_total: string, shipping_total: string, tax_total: string, schedule_source: string}
	 */
	private $fields;

	/**
	 * Line items, or null when not loaded.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private $items;

	/**
	 * Addresses keyed by type, or null when not loaded.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private $addresses;

	/**
	 * Plan snapshot payload, or null when absent or not hydrated.
	 *
	 * @var array<string, mixed>|null
	 */
	private $plan_snapshot;

	/**
	 * Use {@see self::from_contract()}.
	 */
	private function __construct() {
	}

	/**
	 * Build a view of a stored contract.
	 *
	 * @internal Built by the engine `Api\` facades only.
	 *
	 * @param Contract $contract      Stored contract.
	 * @param bool     $with_children Whether the read loaded items and addresses.
	 */
	public static function from_contract( Contract $contract, bool $with_children ): self {
		$instrument = $contract->get_payment_instrument();
		$snapshot   = $contract->get_plan_snapshot();

		$view         = new self();
		$view->fields = array(
			'id'                   => (int) $contract->get_id(),
			'status'               => $contract->get_status(),
			'owner'                => $contract->get_extension_slug(),
			'customer_id'          => $contract->get_customer_id(),
			'currency'             => $contract->get_currency(),
			'selling_plan_id'      => $contract->get_selling_plan_id(),
			'origin_order_id'      => $contract->get_origin_order_id(),
			'payment_method'       => $instrument->get_gateway(),
			'payment_method_title' => $instrument->get_title(),
			'payment_token_id'     => $instrument->get_token_id(),
			'start_gmt'            => $contract->get_start_gmt(),
			'next_payment_gmt'     => $contract->get_next_payment_gmt(),
			'last_payment_gmt'     => $contract->get_last_payment_gmt(),
			'last_attempt_gmt'     => $contract->get_last_attempt_gmt(),
			'trial_end_gmt'        => $contract->get_trial_end_gmt(),
			'end_gmt'              => $contract->get_end_gmt(),
			'billing_total'        => $contract->get_billing_total(),
			'discount_total'       => $contract->get_discount_total(),
			'shipping_total'       => $contract->get_shipping_total(),
			'tax_total'            => $contract->get_tax_total(),
			'schedule_source'      => $contract->get_schedule_source(),
		);

		$view->items         = $with_children ? array_map( array( self::class, 'item' ), $contract->get_items() ) : null;
		$view->addresses     = $with_children ? array_map( array( self::class, 'address' ), $contract->get_addresses() ) : null;
		$view->plan_snapshot = null !== $snapshot ? $snapshot->to_array() : null;

		return $view;
	}

	/**
	 * Project a stored item row onto the item write fields; `taxes` decoded to an array.
	 *
	 * @param array<string, mixed> $row Stored item row.
	 * @return array<string, mixed>
	 */
	private static function item( array $row ): array {
		$item = array();
		foreach ( Contract::ITEM_FIELDS as $field ) {
			$item[ $field ] = $row[ $field ] ?? null;
		}

		foreach ( array( 'product_id', 'variation_id' ) as $field ) {
			$item[ $field ] = is_numeric( $item[ $field ] ) ? (int) $item[ $field ] : null;
		}

		$taxes         = is_string( $item['taxes'] ) ? json_decode( $item['taxes'], true ) : $item['taxes'];
		$item['taxes'] = is_array( $taxes ) ? $taxes : null;

		return $item;
	}

	/**
	 * Project a stored address row onto the address write fields.
	 *
	 * @param array<string, mixed> $row Stored address row.
	 * @return array<string, mixed>
	 */
	private static function address( array $row ): array {
		$address = array();
		foreach ( Contract::ADDRESS_FIELDS as $field ) {
			$address[ $field ] = $row[ $field ] ?? null;
		}

		return $address;
	}

	/**
	 * Contract id.
	 */
	public function get_id(): int {
		return $this->fields['id'];
	}

	/**
	 * Contract status slug.
	 */
	public function get_status(): string {
		return $this->fields['status'];
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_owner(): ?string {
		return $this->fields['owner'];
	}

	/**
	 * Customer id, or null.
	 */
	public function get_customer_id(): ?int {
		return $this->fields['customer_id'];
	}

	/**
	 * ISO-4217 currency code, or null.
	 */
	public function get_currency(): ?string {
		return $this->fields['currency'];
	}

	/**
	 * Selling plan id, or null.
	 */
	public function get_selling_plan_id(): ?int {
		return $this->fields['selling_plan_id'];
	}

	/**
	 * Origin order id, or null.
	 */
	public function get_origin_order_id(): ?int {
		return $this->fields['origin_order_id'];
	}

	/**
	 * Payment gateway id, or null.
	 */
	public function get_payment_method(): ?string {
		return $this->fields['payment_method'];
	}

	/**
	 * Payment method title, or null.
	 */
	public function get_payment_method_title(): ?string {
		return $this->fields['payment_method_title'];
	}

	/**
	 * Payment token id, or null.
	 */
	public function get_payment_token_id(): ?int {
		return $this->fields['payment_token_id'];
	}

	/**
	 * Start (GMT `Y-m-d H:i:s`), or null.
	 */
	public function get_start_gmt(): ?string {
		return $this->fields['start_gmt'];
	}

	/**
	 * Next-due moment (GMT), or null.
	 */
	public function get_next_payment_gmt(): ?string {
		return $this->fields['next_payment_gmt'];
	}

	/**
	 * Last successful payment (GMT), or null.
	 */
	public function get_last_payment_gmt(): ?string {
		return $this->fields['last_payment_gmt'];
	}

	/**
	 * Last charge attempt (GMT), or null.
	 */
	public function get_last_attempt_gmt(): ?string {
		return $this->fields['last_attempt_gmt'];
	}

	/**
	 * Trial end (GMT), or null.
	 */
	public function get_trial_end_gmt(): ?string {
		return $this->fields['trial_end_gmt'];
	}

	/**
	 * End (GMT), or null.
	 */
	public function get_end_gmt(): ?string {
		return $this->fields['end_gmt'];
	}

	/**
	 * Billing total (decimal string).
	 */
	public function get_billing_total(): string {
		return $this->fields['billing_total'];
	}

	/**
	 * Discount total (decimal string).
	 */
	public function get_discount_total(): string {
		return $this->fields['discount_total'];
	}

	/**
	 * Shipping total (decimal string).
	 */
	public function get_shipping_total(): string {
		return $this->fields['shipping_total'];
	}

	/**
	 * Tax total (decimal string).
	 */
	public function get_tax_total(): string {
		return $this->fields['tax_total'];
	}

	/**
	 * Who runs renewals: `primitive` or `gateway`.
	 */
	public function get_schedule_source(): string {
		return $this->fields['schedule_source'];
	}

	/**
	 * Line items, or null when the read did not load them.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get_items(): ?array {
		return $this->items;
	}

	/**
	 * Addresses keyed by type (`billing` / `shipping`), or null when the read did not load them.
	 *
	 * @return array<string, array<string, mixed>>|null
	 */
	public function get_addresses(): ?array {
		return $this->addresses;
	}

	/**
	 * The plan snapshot payload as stored, or null when the contract has none.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_plan_snapshot(): ?array {
		return $this->plan_snapshot;
	}
}

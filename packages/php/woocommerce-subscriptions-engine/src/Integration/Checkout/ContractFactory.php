<?php
/**
 * ContractFactory - builds and persists a Contract from a completed order.
 *
 * This is the entry point a consumer's checkout handler calls once an order
 * that contains a subscription product is paid: hand it the order plus the
 * selling {@see Plan} the customer chose, and it
 *
 *   1. builds a Core {@see Contract} from the order's customer / currency /
 *      payment / totals / line items / addresses,
 *   2. seeds the first renewal date from the plan's {@see BillingPolicy}
 *      (honouring a native trial),
 *   3. persists it via {@see ContractRepository::insert()}, and
 *   4. links order <-> contract in both directions (the contract row's
 *      `origin_order_id` column plus the order-side {@see OrderLinkage} meta).
 *
 * Returns the persisted Contract. Scheduling the first renewal is a separate
 * step the caller drives through {@see RenewalEngine::schedule()} - the factory
 * builds the contract and sets `next_payment_gmt`; it does not enqueue the
 * Action Scheduler row, so a caller can create a contract without arming the
 * money-path (e.g. a gateway-scheduled contract).
 *
 * Integration zone: WordPress-native. Reads a live `WC_Order`; the order never
 * crosses into Core - only the snapshot values pulled off it do.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Order -> contract factory.
 */
final class ContractFactory {

	/**
	 * The repository the factory persists through.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Build a factory that persists through the given repository.
	 *
	 * @param ContractRepository|null $contracts Repository to persist through; a
	 *                                           default instance is created when
	 *                                           omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
	}

	/**
	 * Build, persist, and link a contract for `$order` on `$plan`.
	 *
	 * Recurring totals are seeded from the order's totals on the assumption
	 * that the first cycle's price equals the recurring price; a caller that
	 * already knows the recurring totals (sign-up fees, first-cycle discounts)
	 * passes them via `$overrides`, which are merged over the order-derived
	 * defaults before the contract is built.
	 *
	 * The first `next_payment_gmt` comes from
	 * {@see BillingPolicy::compute_first_renewal_from()} anchored on the order's
	 * paid time (`date_paid`, falling back to "now"), so a native trial delays
	 * the first bill correctly. A caller can override it via
	 * `$overrides['next_payment_gmt']`.
	 *
	 * @param WC_Order             $order     The paid checkout order.
	 * @param Plan                 $plan      The selling plan the customer chose. Must be persisted (have an id).
	 * @param array<string, mixed> $overrides Optional explicit values for any Contract::create() field.
	 * @return Contract The persisted contract, with its id assigned.
	 * @throws \RuntimeException If the plan or order has no id, or the insert fails.
	 */
	public function create_from_order( WC_Order $order, Plan $plan, array $overrides = array() ): Contract {
		$plan_id = $plan->get_id();
		if ( null === $plan_id ) {
			throw new \RuntimeException( 'ContractFactory::create_from_order(): the selling plan must be persisted (have an id) before a contract can reference it.' );
		}

		// An unsaved order reports id 0; persisting origin_order_id => 0 would link
		// the contract to a non-existent order. Require a saved order up front.
		if ( ! $order->get_id() ) {
			throw new \RuntimeException( 'ContractFactory::create_from_order(): the order must be persisted (have an id) before a contract can link to it.' );
		}

		$now       = gmdate( 'Y-m-d H:i:s' );
		$paid_date = $order->get_date_paid();
		$anchor    = null !== $paid_date
			? new DateTimeImmutable( '@' . $paid_date->getTimestamp() )
			: new DateTimeImmutable( $now, new DateTimeZone( 'UTC' ) );

		$next_payment = $plan->get_billing_policy()
			->compute_first_renewal_from( $anchor )
			->format( 'Y-m-d H:i:s' );

		$defaults = array(
			'customer_id'          => $order->get_customer_id(),
			'currency'             => $order->get_currency(),
			'selling_plan_id'      => $plan_id,
			'origin_order_id'      => $order->get_id(),
			'extension_slug'       => $plan->get_extension_slug(),
			'payment_method'       => '' !== $order->get_payment_method() ? $order->get_payment_method() : null,
			'payment_method_title' => '' !== $order->get_payment_method_title() ? $order->get_payment_method_title() : null,
			'payment_token_id'     => $this->extract_payment_token_id( $order ),
			'billing_total'        => (string) $order->get_total(),
			'discount_total'       => (string) $order->get_total_discount(),
			'shipping_total'       => (string) $order->get_shipping_total(),
			'tax_total'            => (string) $order->get_total_tax(),
			'start_gmt'            => $now,
			'next_payment_gmt'     => $next_payment,
			'items'                => $this->map_items( $order ),
			'addresses'            => $this->map_addresses( $order ),
			'meta'                 => array(),
		);

		$contract = Contract::create( array_merge( $defaults, $overrides ) );

		$id = $this->contracts->insert( $contract );

		$this->tag_origin_order( $order, $id );

		return $contract;
	}

	/**
	 * Tag `$order` with the parent-relation meta for `$contract_id`.
	 *
	 * Best-effort: the contract row is already persisted and carries the
	 * `origin_order_id` FK, so a failure here (a save listener throwing, a DB
	 * hiccup) is logged and swallowed rather than failing contract creation -
	 * the reverse lookup can be healed from the FK later. Mirrors the order
	 * tagging the renewal engine applies to renewal orders.
	 *
	 * @param WC_Order $order       Order to tag.
	 * @param int      $contract_id Contract id to write into the order meta.
	 */
	private function tag_origin_order( WC_Order $order, int $contract_id ): void {
		try {
			$order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract_id );
			$order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_PARENT );
			$order->save();
		} catch ( Throwable $e ) {
			wc_get_logger()->warning(
				sprintf(
					'ContractFactory: failed to tag origin order %d for contract %d: %s. The contract is persisted; the order-side link can be rebuilt from the contract row.',
					$order->get_id(),
					$contract_id,
					$e->getMessage()
				),
				array(
					'source'      => 'woocommerce-subscriptions-engine',
					'contract_id' => $contract_id,
					'order_id'    => $order->get_id(),
				)
			);
		}
	}

	/**
	 * Map the order's line items to the contract item-row shape.
	 *
	 * Only `line_item` rows are carried onto the contract today (fees /
	 * shipping / tax are reconstructed from the contract totals at renewal
	 * time). The renewal-order builder clones the origin order's items, so the
	 * contract items are a snapshot for inspection rather than the renewal
	 * source of truth.
	 *
	 * @param WC_Order $order The order to read items from.
	 * @return array<int, array<string, mixed>>
	 */
	private function map_items( WC_Order $order ): array {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$items[] = array(
				'item_name'    => $item->get_name(),
				'item_type'    => 'line_item',
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'quantity'     => (string) $item->get_quantity(),
				'subtotal'     => (string) $item->get_subtotal(),
				'total'        => (string) $item->get_total(),
				'taxes'        => $item->get_taxes(),
			);
		}

		return $items;
	}

	/**
	 * Map the order's billing and shipping addresses to the contract shape.
	 *
	 * @param WC_Order $order The order to read addresses from.
	 * @return array<string, array<string, mixed>>
	 */
	private function map_addresses( WC_Order $order ): array {
		return array(
			Contract::ADDRESS_BILLING  => $order->get_address( 'billing' ),
			Contract::ADDRESS_SHIPPING => $order->get_address( 'shipping' ),
		);
	}

	/**
	 * Best-effort extraction of the payment-token id from `$order`.
	 *
	 * Reads WooCommerce's per-order payment tokens, populated when a gateway
	 * calls `$order->add_payment_token()` at checkout. WooCommerce's typical
	 * pattern is one token per order with the most recently attached entry
	 * being the one charged, so we read the last entry. Returns null when no
	 * token is resolvable (manual gateways, or gateways that store their token
	 * reference elsewhere) - the contract is then created without a token and a
	 * later payment-method-change flow can attach one.
	 *
	 * @param WC_Order $order Order to read the token from.
	 * @return int|null Token id, or null when none is resolvable.
	 */
	private function extract_payment_token_id( WC_Order $order ): ?int {
		$tokens = $order->get_payment_tokens();
		if ( ! empty( $tokens ) ) {
			$id = (int) end( $tokens );
			if ( $id > 0 ) {
				return $id;
			}
		}

		return null;
	}
}

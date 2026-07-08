<?php
/**
 * Builds and persists a {@see Contract} (plus its origin {@see Cycle}) from a paid
 * checkout order, and links order <-> contract in both directions. Renewals need no
 * arming beyond this: the contract's `next_payment_gmt` places it on the due index the
 * batch dispatcher scans.
 *
 * Reads a live `WC_Order`; the order never crosses into Core - only the snapshot
 * values pulled off it do.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
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
	 * The live totals (`billing_total` = cycle 1's `expected_total`, plus discount /
	 * shipping / tax) are seeded from the order on the assumption the first recurring
	 * bill equals the order's recurring price; the first renewal date is computed from
	 * the plan's billing policy anchored on the paid time (so a native trial delays it).
	 * Any of these, and any other `Contract::create()` field, can be replaced via `$overrides`.
	 *
	 * @param WC_Order             $order     The paid checkout order.
	 * @param Plan                 $plan      The selling plan the customer chose. Must be persisted (have an id).
	 * @param array<string, mixed> $overrides Optional explicit values: any Contract::create() field, plus
	 *                                        `billing_total` (cycle 1's expected_total) and
	 *                                        `next_payment_gmt` (the first renewal date / cycle 1's period end).
	 * @return Contract The persisted contract, with its id assigned.
	 * @throws \RuntimeException If the plan or order has no id, or a write fails.
	 */
	public function create_from_order( WC_Order $order, Plan $plan, array $overrides = array() ): Contract {
		$plan_id = $plan->get_id();
		if ( null === $plan_id ) {
			throw new \RuntimeException( 'ContractFactory::create_from_order(): the selling plan must be persisted (have an id) before a contract can reference it.' );
		}

		// An unsaved order reports id 0, which would link the contract to a
		// non-existent order. Require a saved order up front.
		if ( ! $order->get_id() ) {
			throw new \RuntimeException( 'ContractFactory::create_from_order(): the order must be persisted (have an id) before a contract can link to it.' );
		}

		$paid_date = $order->get_date_paid();
		$anchor    = null !== $paid_date
			? new DateTimeImmutable( '@' . $paid_date->getTimestamp() )
			: new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		// Start from the paid time (not processing time) so the contract start, cycle 1's
		// period start, and the renewal-measurement anchor all agree.
		$period_start = $anchor->format( 'Y-m-d H:i:s' );

		// First renewal date: cycle 1's period end and the contract's next-bill cache.
		$next_payment = isset( $overrides['next_payment_gmt'] )
			? ScalarCoercion::coerce_string( $overrides['next_payment_gmt'] )
			: $plan->get_billing_policy()->compute_first_renewal_from( $anchor )->format( 'Y-m-d H:i:s' );

		$expected_total = isset( $overrides['billing_total'] ) ? ScalarCoercion::coerce_string( $overrides['billing_total'] ) : (string) $order->get_total();
		$currency       = $order->get_currency();

		$contract_defaults = array(
			'customer_id'          => $order->get_customer_id(),
			'currency'             => $currency,
			'selling_plan_id'      => $plan_id,
			'origin_order_id'      => $order->get_id(),
			'extension_slug'       => $plan->get_extension_slug(),
			'payment_method'       => '' !== $order->get_payment_method() ? $order->get_payment_method() : null,
			'payment_method_title' => '' !== $order->get_payment_method_title() ? $order->get_payment_method_title() : null,
			'payment_token_id'     => $this->extract_payment_token_id( $order ),
			'start_gmt'            => $period_start,
			'next_payment_gmt'     => $next_payment,
			// Live recurring totals the contract bills going forward, seeded from the order.
			'billing_total'        => $expected_total,
			'discount_total'       => (string) $order->get_total_discount(),
			'shipping_total'       => (string) $order->get_shipping_total(),
			'tax_total'            => (string) $order->get_total_tax(),
			'items'                => $this->map_items( $order ),
			'addresses'            => $this->map_addresses( $order ),
			'meta'                 => array(),
		);

		$contract = Contract::create( array_merge( $contract_defaults, $overrides ) );

		$origin_cycle = $this->build_origin_cycle( $order, $plan, $period_start, $next_payment, $expected_total, $currency );

		$contract_id = $this->contracts->insert_with_origin_cycle( $contract, $origin_cycle );

		$this->tag_origin_order( $order, $contract_id );

		return $contract;
	}

	/**
	 * Build the billing chain's cycle 1 - the immutable signup record.
	 *
	 * Created directly `billed` (the origin order is already paid), with `count` 1 and
	 * `contract_id` a placeholder (0) the repository stamps once the contract row has an id.
	 *
	 * @param WC_Order $order          The paid checkout order (the items / order-id source).
	 * @param Plan     $plan           The selling plan (the plan-snapshot / owner source).
	 * @param string   $starts_at      Cycle 1's period start (the signup time, GMT string).
	 * @param string   $ends_at        Cycle 1's period end (the first renewal date, GMT string).
	 * @param string   $expected_total The amount cycle 1 billed (decimal-safe string).
	 * @param string   $currency       ISO-4217 currency code.
	 * @return Cycle The unsaved signup cycle, created `billed`.
	 */
	private function build_origin_cycle( WC_Order $order, Plan $plan, string $starts_at, string $ends_at, string $expected_total, string $currency ): Cycle {
		return Cycle::create(
			array(
				'contract_id'    => 0,
				'sequence_no'    => 1,
				'count'          => 1,
				'status'         => CycleStatus::billed(),
				'order_id'       => $order->get_id(),
				'extension_slug' => $plan->get_extension_slug(),
				'starts_at_gmt'  => $starts_at,
				'ends_at_gmt'    => $ends_at,
				'expected_total' => $expected_total,
				'currency'       => $currency,
				'plan_snapshot'  => $this->build_plan_snapshot( $plan ),
				'items_snapshot' => $this->build_items_snapshot( $order ),
			)
		);
	}

	/**
	 * Build the typed plan snapshot for the origin cycle.
	 *
	 * @param Plan $plan The plan whose terms to snapshot.
	 */
	private function build_plan_snapshot( Plan $plan ): PlanSnapshot {
		return PlanSnapshot::from_array(
			array(
				'selling_plan_id' => $plan->get_id(),
				'name'            => $plan->get_name(),
				'category'        => $plan->get_category(),
				'billing_policy'  => $plan->get_billing_policy()->to_array(),
			)
		);
	}

	/**
	 * Build the typed items snapshot for the origin cycle from the order.
	 *
	 * @param WC_Order $order The order whose line items to snapshot.
	 */
	private function build_items_snapshot( WC_Order $order ): ItemsSnapshot {
		return ItemsSnapshot::from_items( $this->map_items( $order ) );
	}

	/**
	 * Tag `$order` with the parent-relation meta for `$contract_id`.
	 *
	 * Best-effort: the contract already carries the `origin_order_id` FK, so a failure
	 * here is logged and swallowed (the order-side link can be rebuilt from the FK later).
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
	 * Only `line_item` rows are carried (fees / shipping / tax are reconstructed from
	 * the contract totals at renewal). These are a snapshot for inspection, not the
	 * renewal source of truth - the renewal-order builder clones the origin order's items.
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
	 * Reads WooCommerce's per-order payment tokens (populated when a gateway calls
	 * `$order->add_payment_token()`); the last entry is the one charged. Returns null
	 * when none is resolvable (manual gateways, or token stored elsewhere) - the contract
	 * is then created without a token and a later payment-method change can attach one.
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

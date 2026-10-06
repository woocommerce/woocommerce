<?php
/**
 * Base test case for engine integration tests.
 *
 * Schema is installed once in the bootstrap; WP_UnitTestCase wraps each test in
 * a transaction and rolls it back, so test rows do not leak between tests.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;

/**
 * Engine integration test case.
 */
abstract class EngineIntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Gateway ids wired with an approving scheduled-payment handler, to unhook on teardown.
	 *
	 * @var array<int, string>
	 */
	private $approved_gateways = array();

	public function tear_down(): void {
		foreach ( $this->approved_gateways as $gateway ) {
			remove_all_actions( 'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway );
		}
		$this->approved_gateways = array();

		parent::tear_down();
	}

	/**
	 * Declare `recurring` for `$gateway` and wire an inline approving handler: it marks the
	 * renewal order paid synchronously (the dummy-gateway shape), so the money-path reads a
	 * paid order immediately after the charge is attempted. Unhooked automatically on teardown.
	 *
	 * @param string $gateway Gateway id to approve charges for.
	 */
	protected function approve_charges_for( string $gateway ): void {
		GatewayCapabilities::declare( $gateway, array( GatewayCapabilities::RECURRING ) );

		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway,
			static function ( $amount, $renewal_order ): void {
				unset( $amount );
				if ( $renewal_order instanceof WC_Order && $renewal_order->needs_payment() ) {
					$renewal_order->payment_complete();
				}
			},
			10,
			2
		);

		$this->approved_gateways[] = $gateway;
	}

	/**
	 * Declare `recurring` for `$gateway` and wire an inline declining handler: it moves the
	 * renewal order to `failed` (a hard decline the gateway reports synchronously), so the
	 * money-path settles the cycle `failed` - as opposed to a gateway that leaves the order
	 * un-paid-and-not-failed, which is treated as an async charge still awaiting confirmation.
	 * Unhooked automatically on teardown.
	 *
	 * @param string $gateway Gateway id to fail charges for.
	 */
	protected function fail_charges_for( string $gateway ): void {
		GatewayCapabilities::declare( $gateway, array( GatewayCapabilities::RECURRING ) );

		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway,
			static function ( $amount, $renewal_order ): void {
				unset( $amount );
				if ( $renewal_order instanceof WC_Order ) {
					$renewal_order->update_status( 'failed', 'Gateway declined the recurring charge.' );
				}
			},
			10,
			2
		);

		$this->approved_gateways[] = $gateway;
	}

	/**
	 * Sign up a contract for a paid order on `$plan` through the contracts facade, the way an
	 * extension maps its checkout: create a draft from explicit order fields and snapshots,
	 * record cycle 1 (billed, linked to the order), then activate. An order without a
	 * customer gets a new one.
	 *
	 * @param WC_Order             $order     Saved, paid order.
	 * @param Plan                 $plan      Saved selling plan.
	 * @param array<string, mixed> $overrides `Contracts::create()` fields to replace; `status` is the final status.
	 * @return int The contract id.
	 */
	protected function sign_up_from_order( WC_Order $order, Plan $plan, array $overrides = array() ): int {
		if ( $order->get_customer_id() <= 0 ) {
			$customer_id = self::factory()->user->create();
			$this->assertIsInt( $customer_id );
			$order->set_customer_id( $customer_id );
			$order->save();
		}

		$paid  = $order->get_date_paid();
		$start = null !== $paid
			? new DateTimeImmutable( '@' . $paid->getTimestamp() )
			: new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
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
		}

		$tokens   = $order->get_payment_tokens();
		$token_id = array() !== $tokens ? (int) end( $tokens ) : 0;

		$args = array_merge(
			array(
				'owner'                => (string) $plan->get_extension_slug(),
				'customer_id'          => $order->get_customer_id(),
				'currency'             => $order->get_currency(),
				'selling_plan_id'      => $plan->get_id(),
				'origin_order_id'      => $order->get_id(),
				'payment_method'       => '' !== $order->get_payment_method() ? $order->get_payment_method() : null,
				'payment_method_title' => '' !== $order->get_payment_method_title() ? $order->get_payment_method_title() : null,
				'payment_token_id'     => $token_id > 0 ? $token_id : null,
				'start_gmt'            => $start,
				'next_payment_gmt'     => $plan->get_billing_policy()->compute_first_renewal_from( $start ),
				'billing_total'        => (string) $order->get_total(),
				'discount_total'       => (string) $order->get_total_discount(),
				'shipping_total'       => (string) $order->get_shipping_total(),
				'tax_total'            => (string) $order->get_total_tax(),
				'items'                => $items,
				'addresses'            => array(
					'billing'  => $order->get_address( 'billing' ),
					'shipping' => $order->get_address( 'shipping' ),
				),
				'plan_snapshot'        => array(
					'selling_plan_id' => $plan->get_id(),
					'name'            => $plan->get_name(),
					'category'        => $plan->get_category(),
					'billing_policy'  => $plan->get_billing_policy()->to_array(),
					'pricing_policy'  => $plan->get_pricing_policy(),
				),
				'items_snapshot'       => $items,
			),
			$overrides
		);

		$status         = $args['status'] ?? ContractStatus::ACTIVE;
		$args['status'] = ContractStatus::DRAFT;

		$id   = Contracts::create( $args );
		$view = Subscriptions::get( $id );
		$this->assertNotNull( $view );

		Contracts::add_cycle(
			$id,
			array(
				'status'         => CycleStatus::BILLED,
				'order_id'       => $order->get_id(),
				'starts_at_gmt'  => (string) $view->get_start_gmt(),
				'ends_at_gmt'    => (string) $view->get_next_payment_gmt(),
				'expected_total' => $view->get_billing_total(),
			)
		);
		Contracts::update( $id, array( 'status' => $status ) );

		return $id;
	}
}

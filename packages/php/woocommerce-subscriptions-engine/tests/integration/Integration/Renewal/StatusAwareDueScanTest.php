<?php
/**
 * Integration tests for the status-aware due scan: a non-active contract is never
 * charged when its renewal fires. An on-hold contract is skipped (no charge while
 * held) and a pending-cancellation contract creates no renewal order at its date.
 *
 * Note: actually terminating a pending-cancellation contract at the date (moving it
 * terminal) is a follow-up slice; the current dispatcher only refuses to charge it.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Renewal;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalIntent;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine
 */
class StatusAwareDueScanTest extends EngineIntegrationTestCase {

	private const GATEWAY = 'engine_test_gateway';

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var RenewalEngine
	 */
	private $engine;

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$this->contracts = new ContractRepository();
		$this->engine    = new RenewalEngine( $this->contracts );
	}

	public function tear_down(): void {
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	private function make_origin_order(): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY );
		$order->set_total( '19.99' );
		$order->save();

		return $order;
	}

	/**
	 * Seed a contract at a status with a due (past) next-payment date.
	 *
	 * @param string $status Contract status.
	 */
	private function seed_due( string $status ): int {
		$order    = $this->make_origin_order();
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'origin_order_id'  => $order->get_id(),
				'payment_method'   => self::GATEWAY,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2026-02-01 00:00:00',
				'end_gmt'          => ContractStatus::PENDING_CANCELLATION === $status ? '2026-02-01 00:00:00' : null,
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	/**
	 * Process a renewal for the contract, mirroring how the batch dispatcher hands the
	 * engine a resolved intent for the due contract. The non-active status guard in
	 * {@see RenewalEngine::process()} short-circuits ahead of any cycle selection, so the
	 * exact cycle count handed in is immaterial here.
	 *
	 * @param int $contract_id Contract id.
	 */
	private function process_renewal( int $contract_id ): ?WC_Order {
		return $this->engine->process(
			new RenewalIntent( $contract_id, 1 ),
			new \DateTimeImmutable( '2026-02-01 00:00:00', new \DateTimeZone( 'UTC' ) )
		);
	}

	/**
	 * Count renewal orders tagged for a contract.
	 *
	 * @param int $contract_id Contract id.
	 */
	private function renewal_order_count( int $contract_id ): int {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => 'any',
				'type'       => 'shop_order',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$count = 0;
		foreach ( is_array( $orders ) ? $orders : array() as $order ) {
			if ( $order instanceof WC_Order && OrderLinkage::RELATION_RENEWAL === $order->get_meta( OrderLinkage::META_RELATION_TYPE ) ) {
				++$count;
			}
		}

		return $count;
	}

	public function test_on_hold_contract_at_its_date_creates_no_renewal_order(): void {
		$id = $this->seed_due( ContractStatus::ON_HOLD );

		$result = $this->process_renewal( $id );

		$this->assertNull( $result );
		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		// Still on hold; the next-payment date is left for reactivate to re-arm.
		$this->assertSame( ContractStatus::ON_HOLD, $this->reload( $id )->get_status() );
	}

	public function test_pending_cancellation_at_its_date_creates_no_renewal_order(): void {
		$id = $this->seed_due( ContractStatus::PENDING_CANCELLATION );

		$result = $this->process_renewal( $id );

		$this->assertNull( $result, 'No renewal order is created for a non-active contract.' );
		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		// The contract is not charged. Terminating it at the date is a follow-up slice,
		// so it remains pending-cancellation for now.
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $this->reload( $id )->get_status() );
	}

	/**
	 * Reload a contract, asserting it still exists (narrows the nullable read).
	 *
	 * @param int $id Contract id.
	 */
	private function reload( int $id ): Contract {
		$contract = $this->contracts->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}
}

<?php
/**
 * Integration tests for the owner-scoped due scan, end to end through the batch
 * dispatcher: a contract is due when its next-due moment has passed and its owner is a
 * registered consumer. Lifecycle flows stop renewals by disarming the next-due moment; the
 * interim renewal-flow status predicate leaves a contract in any other status (including an
 * extension-registered one) unselected and untouched; a contract whose owner is null or
 * unregistered waits untouched.
 *
 * Note: actually terminating a pending-cancellation contract at its end date (moving it
 * terminal) is a follow-up slice; today it simply has no next-due moment.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository::find_due
 */
class OwnerScopedDueScanTest extends EngineIntegrationTestCase {

	/**
	 * A gateway that approves the scheduled charge inline.
	 */
	private const GATEWAY = 'engine_owner_scan_gateway';

	/**
	 * The registered consumer, and the owner the default test plan carries.
	 */
	private const OWNER = 'engine-tests';

	/**
	 * The date signed-up contracts first come due (one month after the paid date).
	 */
	private const FIRST_DUE = '2026-02-15 00:00:00';

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
		ConsumerRegistry::reset();
		ConsumerRegistry::register( self::OWNER );
		$this->approve_charges_for( self::GATEWAY );

		$this->contracts = new ContractRepository();
	}

	public function tear_down(): void {
		ConsumerRegistry::reset();
		StatusRegistry::reset();
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	public function test_a_held_contract_is_not_renewed_at_or_after_its_former_date(): void {
		$id = $this->sign_up( self::OWNER );
		( new Hold( $this->contracts ) )->hold( $this->reload( $id ) );

		$this->run_batch_at( self::FIRST_DUE );
		$this->run_batch_at( '2026-06-01 00:00:00' );

		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		$held = $this->reload( $id );
		$this->assertSame( ContractStatus::ON_HOLD, $held->get_status() );
		$this->assertNull( $held->get_next_payment_gmt() );
	}

	public function test_a_pending_cancellation_contract_is_not_renewed_at_its_end_date(): void {
		$id = $this->sign_up( self::OWNER );
		( new Cancellation( $this->contracts ) )->cancel_at_period_end( $this->reload( $id ) );

		$this->run_batch_at( self::FIRST_DUE );

		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		$this->assertSame( self::FIRST_DUE, $stored->get_end_gmt() );
	}

	public function test_a_due_contract_with_an_extension_registered_status_is_not_selected_and_left_untouched(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		$id = $this->sign_up( self::OWNER );
		$this->force_column( $id, 'status', 'paused-by-merchant' );

		$at = new DateTimeImmutable( '2026-02-20 00:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertNotContains( $id, $this->due_ids( $at ), 'The scan does not select a non-active contract.' );

		$this->run_batch_at( '2026-02-20 00:00:00' );

		// The engine never clears the due moment of a status it did not set: the extension
		// that set it decides what happens next.
		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		$stored = $this->reload( $id );
		$this->assertSame( 'paused-by-merchant', $stored->get_status() );
		$this->assertSame( self::FIRST_DUE, $stored->get_next_payment_gmt() );
	}

	public function test_a_due_contract_with_an_unregistered_owner_waits_until_the_owner_registers(): void {
		$id = $this->sign_up( 'other-ext' );

		$this->run_batch_at( '2026-02-20 00:00:00' );

		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		$this->assertSame( self::FIRST_DUE, $this->reload( $id )->get_next_payment_gmt(), 'The waiting contract is untouched.' );

		ConsumerRegistry::register( 'other-ext' );
		$this->run_batch_at( '2026-02-20 00:00:00' );

		$this->assertSame( 1, $this->renewal_order_count( $id ) );
	}

	public function test_a_due_contract_with_no_owner_is_untouched(): void {
		$id = $this->sign_up( self::OWNER );
		$this->force_column( $id, 'extension_slug', null );

		$this->run_batch_at( '2026-02-20 00:00:00' );

		$this->assertSame( 0, $this->renewal_order_count( $id ) );
		$this->assertSame( self::FIRST_DUE, $this->reload( $id )->get_next_payment_gmt() );
	}

	/**
	 * Sign up a monthly contract owned by `$owner` via the checkout factory (cycle 1 billed,
	 * next payment due {@see self::FIRST_DUE}). Returns the contract id.
	 *
	 * @param string $owner The plan's (and so the contract's) extension slug.
	 */
	private function sign_up( string $owner ): int {
		$plan = Plan::create(
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, null, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => $owner,
			)
		);
		( new PlanRepository() )->insert( $plan );

		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY );
		$order->set_total( '19.99' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		$contract = ( new ContractFactory() )->create_from_order( $order, $plan );
		$id       = (int) $contract->get_id();
		$this->assertSame( self::FIRST_DUE, $this->reload( $id )->get_next_payment_gmt() );

		return $id;
	}

	/**
	 * Run one dispatcher tick at the given UTC moment.
	 *
	 * @param string $now GMT moment.
	 */
	private function run_batch_at( string $now ): void {
		( new RenewalDispatcher() )->run_batch( new DateTimeImmutable( $now, new DateTimeZone( 'UTC' ) ), 50 );
	}

	/**
	 * Contract ids the due scan selects at `$at`.
	 *
	 * @param DateTimeImmutable $at Scan moment.
	 * @return array<int, int>
	 */
	private function due_ids( DateTimeImmutable $at ): array {
		$ids = array();
		foreach ( $this->contracts->find_due( $at, 50, ConsumerRegistry::all() ) as $candidate ) {
			$ids[] = $candidate->get_contract_id();
		}

		return $ids;
	}

	/**
	 * Overwrite one contract column directly (a shape no flow produces).
	 *
	 * @param int         $id     Contract id.
	 * @param string      $column Column name.
	 * @param string|null $value  Value to store.
	 */
	private function force_column( int $id, string $column, ?string $value ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( $column => $value ), array( 'id' => $id ) );
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

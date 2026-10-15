<?php
/**
 * Integration tests for the lean ContractRepository and its targeted cycle access.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PaymentInstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SnapshotStore;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 */
class ContractRepositoryTest extends EngineIntegrationTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	/**
	 * The owner the due-scan fixtures carry, registered as a consumer in setUp().
	 */
	private const OWNER = 'engine-tests';

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
	}

	public function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	private function make_contract(): Contract {
		return Contract::create(
			array(
				'status'               => ContractStatus::ACTIVE,
				'customer_id'          => 42,
				'currency'             => 'USD',
				'selling_plan_id'      => 7,
				'origin_order_id'      => 1001,
				'extension_slug'       => 'lite',
				'payment_method'       => 'woocommerce_payments',
				'payment_method_title' => 'Credit card',
				'payment_token_id'     => 55,
				'start_gmt'            => '2026-06-15 00:00:00',
				'next_payment_gmt'     => '2026-07-15 00:00:00',
				'billing_total'        => '19.99',
				'discount_total'       => '1.00',
				'shipping_total'       => '5.00',
				'tax_total'            => '2.50',
				'last_payment_gmt'     => '2026-06-15 00:00:00',
				'last_attempt_gmt'     => '2026-06-15 00:00:00',
				'trial_end_gmt'        => null,
				'end_gmt'              => null,
				'items'                => array(
					array(
						'item_name'  => 'Coffee bag',
						'item_type'  => 'line_item',
						'product_id' => 200,
						'quantity'   => '1',
						'subtotal'   => '19.99',
						'total'      => '19.99',
					),
				),
				'addresses'            => array(
					Contract::ADDRESS_BILLING  => array(
						'first_name' => 'Ada',
						'last_name'  => 'Lovelace',
						'country'    => 'US',
						'email'      => 'ada@example.test',
					),
					Contract::ADDRESS_SHIPPING => array(
						'first_name' => 'Ada',
						'last_name'  => 'Lovelace',
						'country'    => 'US',
					),
				),
			)
		);
	}

	/**
	 * Build a billing cycle for the given contract at a sequence/count.
	 *
	 * @param int      $contract_id       Contract id.
	 * @param int      $sequence_no       Position in the chain.
	 * @param int|null $count             Chargeable count, or null for non-counting.
	 * @param string   $starts_at         Period start GMT string.
	 * @param string   $ends_at           Period end GMT string.
	 * @param int|null $plan_snapshot_id  Stored plan snapshot id, or null.
	 * @param int|null $items_snapshot_id Stored items snapshot id, or null.
	 * @param int|null $order_id          Linked order id, or null.
	 */
	private function make_cycle( int $contract_id, int $sequence_no, ?int $count, string $starts_at, string $ends_at, ?int $plan_snapshot_id = null, ?int $items_snapshot_id = null, ?int $order_id = null ): Cycle {
		return Cycle::create(
			array(
				'contract_id'       => $contract_id,
				'sequence_no'       => $sequence_no,
				'count'             => $count,
				'starts_at_gmt'     => $starts_at,
				'ends_at_gmt'       => $ends_at,
				'expected_total'    => '19.99',
				'currency'          => 'USD',
				'extension_slug'    => 'lite',
				'order_id'          => $order_id,
				'plan_snapshot_id'  => $plan_snapshot_id,
				'items_snapshot_id' => $items_snapshot_id,
			)
		);
	}

	/**
	 * Build a first billing-style cycle on a chain of the given kind.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $kind        Chain kind.
	 */
	private function make_cycle_of_kind( int $contract_id, string $kind ): Cycle {
		return Cycle::create(
			array(
				'contract_id'   => $contract_id,
				'kind'          => $kind,
				'sequence_no'   => 1,
				'starts_at_gmt' => '2026-07-15 00:00:00',
				'ends_at_gmt'   => '2026-08-15 00:00:00',
				'currency'      => 'USD',
			)
		);
	}

	/**
	 * Store the sample plan and items snapshot rows for a contract.
	 *
	 * @param int $contract_id Contract id.
	 * @return array{0: int, 1: int} The plan and items snapshot ids.
	 */
	private function seed_cycle_snapshots( int $contract_id ): array {
		$store = new SnapshotStore();
		$plan  = $this->sample_plan_snapshot();
		$items = $this->sample_items_snapshot();

		return array(
			$store->insert( $contract_id, SnapshotStore::TYPE_PLAN, $plan->get_selling_plan_id(), $plan->to_payload(), $plan->get_schema_version() ),
			$store->insert( $contract_id, SnapshotStore::TYPE_ITEMS, null, $items->to_payload(), $items->get_schema_version() ),
		);
	}

	private function sample_plan_snapshot(): PlanSnapshot {
		return PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 7,
				'cadence'         => 'monthly',
			)
		);
	}

	private function sample_items_snapshot(): ItemsSnapshot {
		return ItemsSnapshot::from_items(
			array(
				array(
					'product_id' => 200,
					'quantity'   => 1,
				),
			)
		);
	}

	/**
	 * @testdox A contract round-trips its live state, children, and config, no cycle graph.
	 */
	public function test_contract_round_trips_its_live_state(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->assertGreaterThan( 0, $id );

		$fetched = $this->sut->find( $id );

		$this->assertInstanceOf( Contract::class, $fetched );
		$this->assertSame( $id, $fetched->get_id() );
		$this->assertSame( 42, $fetched->get_customer_id() );
		$this->assertSame( 'USD', $fetched->get_currency() );
		$this->assertSame( 'lite', $fetched->get_extension_slug() );
		$this->assertSame( ContractStatus::ACTIVE, $fetched->get_status() );
		$this->assertSame( 1001, $fetched->get_origin_order_id() );
		$this->assertSame( '2026-07-15 00:00:00', $fetched->get_next_payment_gmt() );

		// The live config round-trips (totals normalized to the storage scale).
		$this->assertSame( '19.99000000', $fetched->get_billing_total() );
		$this->assertSame( '1.00000000', $fetched->get_discount_total() );
		$this->assertSame( '5.00000000', $fetched->get_shipping_total() );
		$this->assertSame( '2.50000000', $fetched->get_tax_total() );
		$this->assertSame( '2026-06-15 00:00:00', $fetched->get_last_payment_gmt() );
		$this->assertSame( '2026-06-15 00:00:00', $fetched->get_last_attempt_gmt() );
		$this->assertNull( $fetched->get_trial_end_gmt() );
		$this->assertNull( $fetched->get_end_gmt() );

		$instrument = $fetched->get_payment_instrument();
		$this->assertSame( 55, $instrument->get_token_id() );
		$this->assertSame( 'woocommerce_payments', $instrument->get_gateway() );

		$items = $fetched->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'Coffee bag', $items[0]['item_name'] );

		$addresses = $fetched->get_addresses();
		$this->assertArrayHasKey( Contract::ADDRESS_BILLING, $addresses );
		$this->assertArrayHasKey( Contract::ADDRESS_SHIPPING, $addresses );
		$this->assertSame( 'Ada', $addresses[ Contract::ADDRESS_BILLING ]['first_name'] );
	}

	/**
	 * @testdox find_summary reads the contract row only, without children.
	 */
	public function test_find_summary_reads_the_contract_row_only(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$summary = $this->sut->find_summary( $id );

		$this->assertInstanceOf( Contract::class, $summary );
		$this->assertSame( $id, $summary->get_id() );
		$this->assertSame( '2026-07-15 00:00:00', $summary->get_next_payment_gmt() );
		$this->assertSame( array(), $summary->get_items() );
	}

	/**
	 * @testdox find_by_origin_order returns the matching contracts oldest first, with plan terms hydrated.
	 */
	public function test_find_by_origin_order_returns_matches_oldest_first(): void {
		$first  = $this->sut->insert( $this->make_contract() );
		$second = $this->sut->insert( $this->make_contract() );
		$other  = $this->make_contract();
		$other->set_origin_order_id( 2002 );
		$this->sut->insert( $other );

		$first_contract = $this->sut->find( $first );
		$this->assertInstanceOf( Contract::class, $first_contract );
		$this->seed_plan_snapshot(
			$first_contract,
			array(
				'selling_plan_id' => 7,
				'name'            => 'Monthly',
			)
		);

		$found = $this->sut->find_by_origin_order( 1001 );

		$this->assertSame(
			array( $first, $second ),
			array_map(
				static function ( Contract $c ): ?int {
					return $c->get_id();
				},
				$found
			)
		);
		$snapshot = $found[0]->get_plan_snapshot();
		$this->assertInstanceOf( PlanSnapshot::class, $snapshot );
		$this->assertSame( 'Monthly', $snapshot->to_array()['name'] );
		$this->assertSame( array(), $this->sut->find_by_origin_order( 9999 ) );
	}

	/**
	 * Store a plan snapshot row and point the contract at it.
	 *
	 * @param Contract             $contract Stored contract.
	 * @param array<string, mixed> $payload  Plan snapshot payload.
	 */
	private function seed_plan_snapshot( Contract $contract, array $payload ): void {
		$plan = PlanSnapshot::from_array( $payload );
		$contract->set_plan_snapshot_id(
			( new SnapshotStore() )->insert( (int) $contract->get_id(), SnapshotStore::TYPE_PLAN, $plan->get_selling_plan_id(), $plan->to_payload(), $plan->get_schema_version() )
		);
		$this->sut->update_fields( $contract, array( 'plan_snapshot_id' ) );
	}

	/**
	 * @testdox Meta written through the meta methods survives contract writes.
	 */
	public function test_meta_survives_a_whole_entity_update(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->add_meta( $id, 'source_channel', 'pdp' );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'items', 'addresses' ) ) );
		$this->assertSame( 'pdp', $this->sut->get_meta( $id, 'source_channel', true ) );

		$contract->set_status( ContractStatus::ACTIVE );
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status' ) ) );
		$this->assertSame( array( 'pdp' ), $this->sut->get_meta( $id, 'source_channel' ) );
	}

	/**
	 * @testdox query returns contracts newest first, lightweight, honouring limit/offset.
	 */
	public function test_query_orders_newest_first_with_paging(): void {
		$first  = $this->sut->insert( $this->make_contract() );
		$second = $this->sut->insert( $this->make_contract() );
		$third  = $this->sut->insert( $this->make_contract() );

		// Newest first (id DESC), and hydrated lightweight (row only, no children).
		$all = $this->sut->query();
		$this->assertSame( array( $third, $second, $first ), array_map( static fn ( Contract $c ) => $c->get_id(), $all ) );
		$this->assertInstanceOf( Contract::class, $all[0] );
		$this->assertSame( array(), $all[0]->get_items() );

		// Limit caps the window from the newest end.
		$limited = $this->sut->query( array( 'limit' => 2 ) );
		$this->assertSame( array( $third, $second ), array_map( static fn ( Contract $c ) => $c->get_id(), $limited ) );

		// Offset skips from the newest end.
		$offset = $this->sut->query(
			array(
				'limit'  => 2,
				'offset' => 1,
			)
		);
		$this->assertSame( array( $second, $first ), array_map( static fn ( Contract $c ) => $c->get_id(), $offset ) );
	}

	/**
	 * Insert a contract at a given status with the given list-relevant columns, returning its id.
	 *
	 * @param string      $status          Contract status (a ContractStatus value).
	 * @param int         $customer_id     Owning customer id.
	 * @param string|null $next_payment    Next-payment GMT string, or null.
	 * @param string      $billing_total   Billing total (decimal string).
	 * @param string      $start           Start GMT string.
	 * @param int|null    $origin_order_id Origin order id, or null.
	 */
	private function insert_list_contract(
		string $status,
		int $customer_id = 42,
		?string $next_payment = '2026-07-15 00:00:00',
		string $billing_total = '19.99',
		string $start = '2026-06-15 00:00:00',
		?int $origin_order_id = 1001
	): int {
		return $this->sut->insert(
			Contract::create(
				array(
					'extension_slug'   => 'engine-tests',
					'customer_id'      => $customer_id,
					'status'           => $status,
					'currency'         => 'USD',
					'selling_plan_id'  => 7,
					'origin_order_id'  => $origin_order_id,
					'start_gmt'        => $start,
					'next_payment_gmt' => $next_payment,
					'billing_total'    => $billing_total,
				)
			)
		);
	}

	/**
	 * The ids returned by a query, in result order.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, int>
	 */
	private function query_ids( array $args ): array {
		return array_map( static fn ( Contract $c ) => (int) $c->get_id(), $this->sut->query( $args ) );
	}

	/**
	 * @testdox query filters by a valid status and ignores an invalid or empty status.
	 */
	public function test_query_filters_by_status(): void {
		$active    = $this->insert_list_contract( ContractStatus::ACTIVE );
		$on_hold   = $this->insert_list_contract( ContractStatus::ON_HOLD );
		$cancelled = $this->insert_list_contract( ContractStatus::CANCELLED );

		$this->assertSame( array( $active ), $this->query_ids( array( 'status' => ContractStatus::ACTIVE ) ) );
		$this->assertSame( array( $on_hold ), $this->query_ids( array( 'status' => ContractStatus::ON_HOLD ) ) );

		// An unknown status is ignored (not injected into SQL): all rows come back, newest first.
		$this->assertSame(
			array( $cancelled, $on_hold, $active ),
			$this->query_ids( array( 'status' => 'not-a-status' ) )
		);

		// An empty status is ignored too.
		$this->assertSame(
			array( $cancelled, $on_hold, $active ),
			$this->query_ids( array( 'status' => '' ) )
		);
	}

	/**
	 * @testdox query sorts by a whitelisted column and direction, defaulting to id DESC.
	 */
	public function test_query_sorts_by_whitelisted_orderby_and_order(): void {
		// Distinct totals and next-payment dates so the ordering is unambiguous.
		$low  = $this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-09-15 00:00:00', '10.00' );
		$high = $this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-07-15 00:00:00', '30.00' );
		$mid  = $this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-08-15 00:00:00', '20.00' );

		// total ASC.
		$this->assertSame(
			array( $low, $mid, $high ),
			$this->query_ids(
				array(
					'orderby' => 'total',
					'order'   => 'ASC',
				)
			)
		);

		// total DESC (order defaults to DESC when omitted).
		$this->assertSame( array( $high, $mid, $low ), $this->query_ids( array( 'orderby' => 'total' ) ) );

		// next_payment maps to next_payment_gmt: ASC is earliest-first.
		$this->assertSame(
			array( $high, $mid, $low ),
			$this->query_ids(
				array(
					'orderby' => 'next_payment',
					'order'   => 'ASC',
				)
			)
		);
	}

	/**
	 * @testdox query falls back to id DESC for an unknown orderby or order (never raw SQL).
	 */
	public function test_query_falls_back_for_invalid_sort(): void {
		$first  = $this->insert_list_contract( ContractStatus::ACTIVE );
		$second = $this->insert_list_contract( ContractStatus::ACTIVE );
		$third  = $this->insert_list_contract( ContractStatus::ACTIVE );

		// An unknown orderby column falls back to id, and an unknown order to DESC - no SQL error.
		$this->assertSame(
			array( $third, $second, $first ),
			$this->query_ids(
				array(
					'orderby' => 'customer_id; DROP TABLE contracts',
					'order'   => 'sideways',
				)
			)
		);
	}

	/**
	 * @testdox query clamps a negative limit or offset instead of emitting invalid SQL.
	 */
	public function test_query_clamps_negative_paging(): void {
		$this->insert_list_contract( ContractStatus::ACTIVE );
		$this->insert_list_contract( ContractStatus::ACTIVE );

		// A negative limit clamps to 0 (LIMIT 0 -> no rows) rather than "LIMIT -n", which is a SQL error.
		$this->assertSame( array(), $this->query_ids( array( 'limit' => -5 ) ) );

		// A negative offset clamps to 0, so the page is unaffected and no SQL error is raised.
		$this->assertCount( 2, $this->query_ids( array( 'offset' => -10 ) ) );
	}

	/**
	 * @testdox query search matches by contract id or origin order id for a numeric term.
	 */
	public function test_query_search_matches_id_and_origin_order_for_a_numeric_term(): void {
		$by_id     = $this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-07-15 00:00:00', '19.99', '2026-06-15 00:00:00', 500 );
		$by_origin = $this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-07-15 00:00:00', '19.99', '2026-06-15 00:00:00', 700 );

		// The term equals the first contract's id: it matches by id.
		$this->assertSame( array( $by_id ), $this->query_ids( array( 'search' => (string) $by_id ) ) );

		// The term equals the second contract's origin order id: it matches by origin_order_id.
		$this->assertSame( array( $by_origin ), $this->query_ids( array( 'search' => '700' ) ) );

		// A numeric term matching nothing returns no rows.
		$this->assertSame( array(), $this->query_ids( array( 'search' => '99999999' ) ) );
	}

	/**
	 * @testdox query search resolves a non-numeric term to matching customers.
	 */
	public function test_query_search_matches_customers_for_a_text_term(): void {
		$alice = self::factory()->user->create(
			array(
				'user_email'   => 'alice@example.test',
				'display_name' => 'Alice Example',
			)
		);
		$bob   = self::factory()->user->create(
			array(
				'user_email'   => 'bob@example.test',
				'display_name' => 'Bob Example',
			)
		);
		$this->assertIsInt( $alice );
		$this->assertIsInt( $bob );

		$alice_contract = $this->insert_list_contract( ContractStatus::ACTIVE, (int) $alice );
		$this->insert_list_contract( ContractStatus::ACTIVE, (int) $bob );

		// The email resolves to Alice's user id, then to her contract.
		$this->assertSame( array( $alice_contract ), $this->query_ids( array( 'search' => 'alice@example.test' ) ) );

		// A text term matching no user returns no rows (empty customer set -> no rows).
		$this->assertSame( array(), $this->query_ids( array( 'search' => 'nobody-by-this-name' ) ) );
	}

	/**
	 * @testdox query search matches a customer by display name and by login, not only email.
	 */
	public function test_query_search_matches_display_name_and_login(): void {
		$customer = self::factory()->user->create(
			array(
				'user_login'   => 'zelda_login',
				'user_email'   => 'zelda@example.test',
				'display_name' => 'Zelda Fitzgerald',
			)
		);
		$this->assertIsInt( $customer );
		$contract = $this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer );

		// The users-table subquery covers display_name and user_login, not just email.
		$this->assertSame( array( $contract ), $this->query_ids( array( 'search' => 'Fitzgerald' ) ) );
		$this->assertSame( array( $contract ), $this->query_ids( array( 'search' => 'zelda_login' ) ) );
	}

	/**
	 * @testdox query/count customer search keeps every match, past the old 50-user lookup cap.
	 */
	public function test_query_search_is_not_capped_at_a_user_limit(): void {
		// More than the old 50-user get_users() cap, all sharing an email substring, each with a contract.
		$total = 55;
		for ( $i = 0; $i < $total; $i++ ) {
			$customer = self::factory()->user->create( array( 'user_email' => "capsearch{$i}@example.test" ) );
			$this->assertIsInt( $customer );
			$this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer );
		}

		// The users-table subquery matches every customer whose email contains the term - no
		// truncation - and count() agrees with the full set the page is a window onto.
		$this->assertSame( $total, $this->sut->count( array( 'search' => 'capsearch' ) ) );
		$this->assertCount(
			$total,
			$this->sut->query(
				array(
					'search' => 'capsearch',
					'limit'  => 100,
				)
			)
		);
	}

	/**
	 * @testdox query composes status, search, and sort together.
	 */
	public function test_query_composes_status_search_and_sort(): void {
		$customer = self::factory()->user->create(
			array(
				'user_email'   => 'composer@example.test',
				'display_name' => 'Composer Example',
			)
		);
		$other    = self::factory()->user->create( array( 'user_email' => 'other@example.test' ) );
		$this->assertIsInt( $customer );
		$this->assertIsInt( $other );

		$active_low  = $this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer, '2026-07-15 00:00:00', '10.00' );
		$active_high = $this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer, '2026-07-15 00:00:00', '20.00' );
		// Same customer, different status - excluded by the status filter.
		$this->insert_list_contract( ContractStatus::CANCELLED, (int) $customer, '2026-07-15 00:00:00', '30.00' );
		// A different customer - excluded by the search.
		$this->insert_list_contract( ContractStatus::ACTIVE, (int) $other, '2026-07-15 00:00:00', '5.00' );

		$this->assertSame(
			array( $active_low, $active_high ),
			$this->query_ids(
				array(
					'status'  => ContractStatus::ACTIVE,
					'search'  => 'composer@example.test',
					'orderby' => 'total',
					'order'   => 'ASC',
				)
			)
		);
	}

	/**
	 * @testdox count_by_status returns every known status, filling absent ones with zero.
	 */
	public function test_count_by_status_returns_every_status_filling_zeros(): void {
		$this->insert_list_contract( ContractStatus::ACTIVE );
		$this->insert_list_contract( ContractStatus::ACTIVE );
		$this->insert_list_contract( ContractStatus::ON_HOLD );

		$counts = $this->sut->count_by_status();

		// Every known status is a key, in ContractStatus::get_all() order, with absent ones 0.
		$this->assertSame( ContractStatus::get_all(), array_keys( $counts ) );
		$this->assertSame( 2, $counts[ ContractStatus::ACTIVE ] );
		$this->assertSame( 1, $counts[ ContractStatus::ON_HOLD ] );
		$this->assertSame( 0, $counts[ ContractStatus::PENDING_CANCELLATION ] );
		$this->assertSame( 0, $counts[ ContractStatus::CANCELLED ] );
		$this->assertSame( 0, $counts[ ContractStatus::EXPIRED ] );
	}

	/**
	 * @testdox count_by_status returns all-zero when there are no contracts.
	 */
	public function test_count_by_status_is_all_zero_when_empty(): void {
		$counts = $this->sut->count_by_status();

		$this->assertSame( ContractStatus::get_all(), array_keys( $counts ) );
		$this->assertSame( array_fill( 0, count( $counts ), 0 ), array_values( $counts ) );
	}

	/**
	 * @testdox count honours the same status + search filter as query, ignoring paging/sort.
	 */
	public function test_count_matches_the_query_filter(): void {
		$customer = self::factory()->user->create( array( 'user_email' => 'counted@example.test' ) );
		$this->assertIsInt( $customer );

		$this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer );
		$this->insert_list_contract( ContractStatus::ACTIVE, (int) $customer );
		$this->insert_list_contract( ContractStatus::ON_HOLD, (int) $customer );
		$this->insert_list_contract( ContractStatus::ACTIVE, 42, '2026-07-15 00:00:00', '19.99', '2026-06-15 00:00:00', 4242 );

		// No args: the grand total.
		$this->assertSame( 4, $this->sut->count() );

		// A status filter counts only that status.
		$this->assertSame( 3, $this->sut->count( array( 'status' => ContractStatus::ACTIVE ) ) );
		$this->assertSame( 1, $this->sut->count( array( 'status' => ContractStatus::ON_HOLD ) ) );

		// A numeric search counts by id / origin order id.
		$this->assertSame( 1, $this->sut->count( array( 'search' => '4242' ) ) );

		// A text search counts the matching customer's rows; status composes with it.
		$this->assertSame( 3, $this->sut->count( array( 'search' => 'counted@example.test' ) ) );
		$this->assertSame(
			2,
			$this->sut->count(
				array(
					'search' => 'counted@example.test',
					'status' => ContractStatus::ACTIVE,
				)
			)
		);

		// Paging and sort args do not change the count.
		$this->assertSame(
			4,
			$this->sut->count(
				array(
					'limit'   => 1,
					'offset'  => 2,
					'orderby' => 'total',
				)
			)
		);
	}

	/**
	 * @testdox count agrees with the number of rows query returns for the same filter.
	 */
	public function test_count_agrees_with_query_result_size(): void {
		$this->insert_list_contract( ContractStatus::ACTIVE );
		$this->insert_list_contract( ContractStatus::ACTIVE );
		$this->insert_list_contract( ContractStatus::CANCELLED );

		$args = array( 'status' => ContractStatus::ACTIVE );
		$this->assertSame( count( $this->sut->query( $args ) ), $this->sut->count( $args ) );
	}

	/**
	 * @testdox count_items_by_contract maps every requested id, zero-filling ids with no items.
	 */
	public function test_count_items_by_contract_maps_every_requested_id(): void {
		$with_items = $this->sut->insert( $this->make_contract() ); // Seeds one line item.
		$no_items   = $this->insert_list_contract( ContractStatus::ACTIVE ); // Bare row, no items.
		$absent     = 999999; // Never inserted.

		$counts = $this->sut->count_items_by_contract( array( $with_items, $no_items, $absent ) );

		$this->assertSame( 1, $counts[ $with_items ], 'A contract with items reports its line-item count.' );
		$this->assertSame( 0, $counts[ $no_items ], 'A contract with no items is zero-filled, not absent.' );
		$this->assertSame( 0, $counts[ $absent ], 'A requested id with no rows is present at zero.' );
		$this->assertCount( 3, $counts, 'The map carries exactly the requested ids.' );

		// De-duplicates its input and short-circuits an empty request.
		$this->assertSame( array( $with_items => 1 ), $this->sut->count_items_by_contract( array( $with_items, $with_items ) ) );
		$this->assertSame( array(), $this->sut->count_items_by_contract( array() ) );
	}

	/**
	 * @testdox A manual/admin contract with a null origin order round-trips.
	 */
	public function test_contract_round_trips_a_null_origin_order(): void {
		$id = $this->sut->insert(
			Contract::create(
				array(
					'extension_slug'  => 'engine-tests',
					'status'          => ContractStatus::ACTIVE,
					'customer_id'     => 1,
					'currency'        => 'EUR',
					'selling_plan_id' => 2,
					'start_gmt'       => '2026-06-15 00:00:00',
				)
			)
		);

		$fetched = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $fetched );
		$this->assertNull( $fetched->get_origin_order_id() );
	}

	/**
	 * @testdox A stored row with no extension_slug hydrates a null slug.
	 */
	public function test_a_stored_null_extension_slug_hydrates_as_null(): void {
		global $wpdb;

		$id = $this->sut->insert(
			Contract::create(
				array(
					'extension_slug'  => 'engine-tests',
					'status'          => ContractStatus::ACTIVE,
					'customer_id'     => 1,
					'currency'        => 'EUR',
					'selling_plan_id' => 2,
					'origin_order_id' => 3,
					'start_gmt'       => '2026-06-15 00:00:00',
				)
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'extension_slug' => null ), array( 'id' => $id ) );

		$fetched = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $fetched );
		$this->assertNull( $fetched->get_extension_slug() );
	}

	/**
	 * @testdox update_fields persists the named contract columns.
	 */
	public function test_update_fields_persists_the_contract_row(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$contract->set_next_payment_gmt( '2026-08-15 00:00:00' );

		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) ) );

		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::ON_HOLD, $reloaded->get_status() );
		$this->assertSame( '2026-08-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox A row-only update_fields leaves the item rows in place.
	 */
	public function test_a_row_only_update_leaves_children_in_place(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$item_ids_before = $this->item_ids( $id );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$this->sut->update_fields( $contract, array( 'status' ) );

		$this->assertSame( $item_ids_before, $this->item_ids( $id ), 'A row-only write must not rewrite the items.' );
	}

	/**
	 * @testdox update_fields replaces the items when named.
	 */
	public function test_update_fields_replaces_named_children(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_items(
			array(
				array(
					'item_name'  => 'Tea tin',
					'item_type'  => 'line_item',
					'product_id' => 300,
					'quantity'   => '2',
					'subtotal'   => '24.00',
					'total'      => '24.00',
				),
			)
		);

		$this->assertTrue( $this->sut->update_fields( $contract, array( 'items' ) ) );

		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$items = $reloaded->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'Tea tin', $items[0]['item_name'] );
	}

	/**
	 * @testdox update_fields throws when the contract has no id.
	 */
	public function test_update_fields_throws_without_id(): void {
		$this->expectException( \RuntimeException::class );
		$this->sut->update_fields( $this->make_contract(), array( 'status' ) );
	}

	/**
	 * @testdox update_fields refuses the create-only creation key and extension slug.
	 */
	public function test_update_fields_refuses_create_only_fields(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		foreach ( array( 'creation_key', 'extension_slug' ) as $field ) {
			try {
				$this->sut->update_fields( $contract, array( $field ) );
				$this->fail( "Expected {$field} to be refused." );
			} catch ( \InvalidArgumentException $e ) {
				$this->assertStringContainsString( $field, $e->getMessage() );
			}
		}
	}

	/**
	 * The item row ids of a contract, oldest first.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<int, int>
	 */
	private function item_ids( int $contract_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE contract_id = %d ORDER BY id ASC", $contract_id ) );

		return array_map( 'intval', $ids );
	}

	/**
	 * @testdox update_fields on a deleted contract returns false before any child write.
	 */
	public function test_update_fields_returns_false_for_a_deleted_contract_and_writes_no_children(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->assertTrue( $this->sut->delete( $id ) );

		$stale = $this->make_contract();
		$stale->set_id( $id );

		$this->assertFalse( $this->sut->update_fields( $stale, array( 'status', 'items', 'addresses' ) ) );

		foreach ( array( SchemaInstaller::TABLE_CONTRACT_ITEMS, SchemaInstaller::TABLE_CONTRACT_ADDRESSES ) as $table ) {
			$table = SchemaInstaller::get_table_name( $table );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE contract_id = %d", $id ) ) );
		}
	}

	/**
	 * @testdox update_fields writing identical values in the same second still changes the row (write_seq) and returns true.
	 */
	public function test_update_fields_with_identical_values_does_not_throw(): void {
		global $wpdb;

		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		for ( $i = 0; $i < 2; $i++ ) {
			$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) ) );
			$this->assertSame( 1, $wpdb->rows_affected, 'A matched row always counts as changed.' );
		}

		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
	}

	/**
	 * @testdox append_cycle inserts a cycle reachable as the chain's current cycle.
	 */
	public function test_append_cycle_and_find_chain_head(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', ...$this->seed_cycle_snapshots( $id ) );
		$this->sut->append_cycle( $cycle );

		$this->assertNotNull( $cycle->get_id() );

		$current = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $current );
		$this->assertSame( $cycle->get_id(), $current->get_id() );
		$this->assertSame( 1, $current->get_sequence_no() );
		$this->assertSame( 1, $current->get_count() );
		$this->assertSame( CycleStatus::PENDING, $current->get_status()->get_value() );
		$this->assertSame( '2026-07-15 00:00:00', $current->get_starts_at_gmt() );
		$this->assertSame( '19.99000000', $current->get_expected_total() );
		$this->assertSame( 'lite', $current->get_extension_slug() );

		// Stored snapshot ids are decoded back into typed value objects on read.
		$this->assertInstanceOf( PlanSnapshot::class, $current->get_plan_snapshot() );
		$this->assertSame(
			array(
				'selling_plan_id' => 7,
				'cadence'         => 'monthly',
			),
			$current->get_plan_snapshot()->to_array()
		);
		$this->assertInstanceOf( ItemsSnapshot::class, $current->get_items_snapshot() );
	}

	/**
	 * @testdox expected_total round-trips at full DECIMAL(26,8) precision, not just two decimals.
	 */
	public function test_expected_total_round_trips_full_decimal_precision(): void {
		$id = $this->sut->insert( $this->make_contract() );

		// Eight fractional digits: a DECIMAL(26,2) column would truncate this on the
		// way in, so an exact reload proves the storage scale is (26,8).
		$cycle = Cycle::create(
			array(
				'contract_id'    => $id,
				'sequence_no'    => 1,
				'count'          => 1,
				'starts_at_gmt'  => '2026-07-15 00:00:00',
				'ends_at_gmt'    => '2026-08-15 00:00:00',
				'expected_total' => '9.12345678',
				'currency'       => 'USD',
				'extension_slug' => 'lite',
			)
		);
		$this->sut->append_cycle( $cycle );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertSame( '9.12345678', $reloaded->get_expected_total() );
	}

	/**
	 * @testdox find_chain_head returns the highest-sequence cycle in the chain.
	 */
	public function test_find_chain_head_returns_the_head(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$first = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' );
		$this->sut->append_cycle( $first );

		$second = $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' );
		$this->sut->append_cycle( $second );

		$current = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $current );
		$this->assertSame( 2, $current->get_sequence_no() );
	}

	/**
	 * @testdox find_chain_head returns null for a chain with no cycles.
	 */
	public function test_find_chain_head_is_null_when_empty(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$this->assertNull( $this->sut->find_chain_head( $id ) );
	}

	/**
	 * @testdox find_cycle_history returns a window of cycles newest first.
	 */
	public function test_find_cycle_history_pages_newest_first(): void {
		$id = $this->sut->insert( $this->make_contract() );

		for ( $n = 1; $n <= 3; $n++ ) {
			$this->sut->append_cycle( $this->make_cycle( $id, $n, $n, sprintf( '2026-%02d-15 00:00:00', 6 + $n ), sprintf( '2026-%02d-15 00:00:00', 7 + $n ) ) );
		}

		$page = $this->sut->find_cycle_history( $id, Cycle::KIND_BILLING, 2, 0 );
		$this->assertCount( 2, $page );
		$this->assertSame( 3, $page[0]->get_sequence_no() );
		$this->assertSame( 2, $page[1]->get_sequence_no() );

		$next = $this->sut->find_cycle_history( $id, Cycle::KIND_BILLING, 2, 2 );
		$this->assertCount( 1, $next );
		$this->assertSame( 1, $next[0]->get_sequence_no() );
	}

	/**
	 * @testdox find_cycles_by_order_id returns every cycle linked to an order, newest first.
	 */
	public function test_find_cycles_by_order_id(): void {
		$first_id  = $this->sut->insert( $this->make_contract() );
		$second_id = $this->sut->insert( $this->make_contract() );

		// One aggregate order serves a cycle on each of two contracts (not 1:1).
		$this->sut->append_cycle( $this->make_cycle( $first_id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', null, null, 9090 ) );
		$this->sut->append_cycle( $this->make_cycle( $second_id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', null, null, 9090 ) );
		// A different order on the first contract must not match.
		$this->sut->append_cycle( $this->make_cycle( $first_id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00', null, null, 7070 ) );

		$linked = $this->sut->find_cycles_by_order_id( 9090 );
		$this->assertCount( 2, $linked );

		$contract_ids = array_map(
			static function ( Cycle $cycle ) {
				return $cycle->get_contract_id();
			},
			$linked
		);
		$this->assertSame( array( $second_id, $first_id ), $contract_ids, 'Cycles come newest first.' );
	}

	/**
	 * Append a pending cycle 1, returning it (with its id).
	 *
	 * @param int $contract_id Contract id.
	 */
	private function append_pending_cycle( int $contract_id ): Cycle {
		$cycle = Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => 1,
				'count'          => 1,
				'status'         => new CycleStatus( CycleStatus::PENDING ),
				'starts_at_gmt'  => '2026-07-15 00:00:00',
				'ends_at_gmt'    => '2026-08-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
			)
		);
		$this->sut->append_cycle( $cycle );

		return $cycle;
	}

	/**
	 * @testdox find_due returns due contracts whatever their status, oldest first.
	 */
	public function test_find_due_is_status_blind_oldest_first(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$on_hold     = $this->insert_contract_due_at( '2026-06-01 00:00:00', ContractStatus::ON_HOLD );
		$due_old     = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$pending_cxl = $this->insert_contract_due_at( '2026-06-20 00:00:00', ContractStatus::PENDING_CANCELLATION );
		$extension   = $this->insert_contract_due_at( '2026-06-25 00:00:00', 'paused-by-merchant' );
		$draft       = $this->insert_contract_due_at( '2026-07-01 00:00:00', ContractStatus::DRAFT );
		$due_recent  = $this->insert_contract_due_at( '2026-07-14 00:00:00', ContractStatus::ACTIVE );
		$not_yet     = $this->insert_contract_due_at( '2026-08-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame(
			array( $on_hold, $due_old, $pending_cxl, $extension, $draft, $due_recent ),
			$this->due_ids( $now, 50 ),
			'Every due contract is selected whatever its status; whether it acts is the owner\'s decision.'
		);
		$this->assertNotContains( $not_yet, $this->due_ids( $now, 50 ) );
	}

	/**
	 * @testdox find_due selects a due contract whose stored status is not registered.
	 */
	public function test_find_due_selects_a_contract_with_an_unregistered_stored_status(): void {
		global $wpdb;

		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$id  = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		$this->assertSame( array( $id ), $this->due_ids( $now, 50 ) );
	}

	/**
	 * @testdox find_due skips a due contract with no owner.
	 */
	public function test_find_due_skips_a_contract_with_no_owner(): void {
		global $wpdb;

		$now      = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$owned    = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$no_owner = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'extension_slug' => null ), array( 'id' => $no_owner ) );

		$ids = $this->due_ids( $now, 50 );

		$this->assertContains( $owned, $ids );
		$this->assertNotContains( $no_owner, $ids );
	}

	/**
	 * @testdox find_due skips a contract whose owner is not listed, and selects it untouched once it is.
	 */
	public function test_find_due_skips_an_unlisted_owner_until_it_is_listed(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$id  = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_ENGINE, CycleStatus::BILLED, null, 'other-ext' );

		$this->assertNotContains( $id, $this->due_ids( $now, 50 ) );

		$this->assertContains( $id, $this->due_ids( $now, 50, array( self::OWNER, 'other-ext' ) ) );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$this->assertSame( '2026-06-15 00:00:00', $contract->get_next_payment_gmt(), 'The waiting contract keeps its due moment.' );
	}

	/**
	 * @testdox find_due returns nothing when no owner is listed.
	 */
	public function test_find_due_returns_nothing_without_owners(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame( array(), $this->sut->find_due( $now, 50, array() ) );
	}

	/**
	 * @testdox find_due treats the cutoff as inclusive and excludes a null next_payment.
	 */
	public function test_find_due_is_inclusive_and_skips_null_schedule(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$exactly_due = $this->insert_contract_due_at( '2026-07-15 00:00:00', ContractStatus::ACTIVE );
		$no_schedule = $this->insert_contract_due_at( null, ContractStatus::ACTIVE );

		$ids = $this->due_ids( $now, 50 );

		$this->assertContains( $exactly_due, $ids, 'A contract due exactly at the cutoff is included.' );
		$this->assertNotContains( $no_schedule, $ids, 'A contract with no next_payment_gmt is never due.' );
	}

	/**
	 * @testdox find_due caps the batch at the limit from the oldest-due end.
	 */
	public function test_find_due_honours_limit(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$first  = $this->insert_contract_due_at( '2026-05-15 00:00:00', ContractStatus::ACTIVE );
		$second = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$this->insert_contract_due_at( '2026-07-01 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame( array( $first, $second ), $this->due_ids( $now, 2 ) );
	}

	/**
	 * @testdox find_due selects only contracts whose schedule the engine keeps.
	 */
	public function test_find_due_selects_only_engine_scheduled_contracts(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$engine  = $this->insert_contract_due_at( '2026-06-01 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_ENGINE );
		$gateway = $this->insert_contract_due_at( '2026-05-01 00:00:00', ContractStatus::ACTIVE, 'my_gateway' );
		$legacy  = $this->insert_contract_due_at( '2026-05-15 00:00:00', ContractStatus::ACTIVE, 'primitive' );

		$ids = $this->due_ids( $now, 50 );

		$this->assertSame( array( $engine ), $ids );
		$this->assertNotContains( $gateway, $ids, 'A contract whose schedule a gateway keeps is never dispatched.' );
		$this->assertNotContains( $legacy, $ids, 'Only the engine source is dispatched.' );
	}

	/**
	 * @testdox find_due returns the id, owner and stored due moment of each row.
	 */
	public function test_find_due_returns_the_row_shape(): void {
		$now = new \DateTimeImmutable( '2026-07-15 02:00:00', new \DateTimeZone( 'Europe/Berlin' ) );

		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame(
			array(
				array(
					'id'               => $id,
					'extension_slug'   => self::OWNER,
					'next_payment_gmt' => '2026-06-15 00:00:00',
				),
			),
			$this->sut->find_due( $now, 50, array( self::OWNER ) )
		);
	}

	/**
	 * @testdox find_due returns no rows for a non-positive limit.
	 */
	public function test_find_due_returns_no_rows_for_a_non_positive_limit(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame( array(), $this->sut->find_due( $now, 0, array( self::OWNER ) ) );
		$this->assertSame( array(), $this->sut->find_due( $now, -1, array( self::OWNER ) ) );
	}

	/**
	 * @testdox A live lock hides the contract from find_due until it expires on the database clock; release reorders it by release moment.
	 */
	public function test_a_live_lock_hides_the_contract_until_it_expires(): void {
		$now   = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$id    = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$later = $this->insert_contract_due_at( '2026-06-20 00:00:00', ContractStatus::ACTIVE );

		$this->pin_db_clock( '2026-07-15 00:00:00' );
		$this->lock( $id, 'due' );

		$this->pin_db_clock( '2026-07-15 00:09:59' );
		$this->assertSame( array( $later ), $this->due_ids( $now, 50 ), 'A live lock hides the contract.' );
		$this->assertSame( array( $later ), $this->due_ids( $now->modify( '+1 day' ), 50 ), 'A later scan cutoff does not expire the lock; the database clock does.' );

		$this->pin_db_clock( '2026-07-15 00:10:00' );
		$this->assertSame( array( $later, $id ), $this->due_ids( $now, 50 ), 'An expired lock no longer hides it; never-locked contracts lead.' );
	}

	/**
	 * @testdox update_fields writes the contract row in exactly one UPDATE statement.
	 */
	public function test_a_contract_write_is_one_update_statement(): void {
		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$contract->set_next_payment_gmt( null );

		$statements = $this->contract_updates_during(
			function () use ( $contract ): void {
				$this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) );
			}
		);

		$this->assertCount( 1, $statements, 'update_fields() writes status and due moment in one UPDATE.' );
		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( ContractStatus::ON_HOLD, $stored->get_status() );
		$this->assertNull( $stored->get_next_payment_gmt() );
	}

	/**
	 * @testdox When the contract write is lost, neither the status nor the due moment changes, and the lock stays.
	 */
	public function test_a_lost_contract_write_changes_neither_status_nor_due_moment(): void {
		$id   = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$lock = $this->sut->acquire_lock( $id, 'test' );
		$this->assertNotNull( $lock );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$contract->set_next_payment_gmt( null );

		$drop = function ( string $query ): string {
			return $this->is_contract_update( $query ) ? '' : $query;
		};

		add_filter( 'query', $drop );
		try {
			$this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ), $lock );
			$this->fail( 'Expected the lost write to throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Failed to update contract.', $e->getMessage() );
		} finally {
			remove_filter( 'query', $drop );
		}

		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		$this->assertSame( '2026-06-15 00:00:00', $stored->get_next_payment_gmt() );
		$this->assertNotNull( $this->sut->lock_holder( $id )['operation'] );
	}

	/**
	 * @testdox Writing next_payment_gmt touches no lock column.
	 */
	public function test_due_moment_writes_leave_the_lock_alone(): void {
		$id   = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$lock = $this->sut->acquire_lock( $id, 'due' );
		$this->assertNotNull( $lock );
		$before = $this->sut->lock_holder( $id );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_next_payment_gmt( '2026-07-15 00:00:00' );
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'next_payment_gmt' ), $lock ) );
		$contract->set_next_payment_gmt( null );
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'next_payment_gmt' ), $lock ) );

		$this->assertSame( $before, $this->sut->lock_holder( $id ) );
	}

	/**
	 * The UPDATE statements against the contracts table that `$run` issues, in order. Counts
	 * without dropping, so the count does not depend on how a write reacts to a lost statement.
	 *
	 * @param callable $run Code under test.
	 * @return array<int, string>
	 */
	private function contract_updates_during( callable $run ): array {
		$statements = array();
		$count      = function ( string $query ) use ( &$statements ): string {
			if ( $this->is_contract_update( $query ) ) {
				$statements[] = $query;
			}
			return $query;
		};

		add_filter( 'query', $count );
		try {
			$run();
		} finally {
			remove_filter( 'query', $count );
		}

		return $statements;
	}

	/**
	 * Whether a query is an UPDATE of the contracts table, with or without backtick quoting.
	 *
	 * @param string $query SQL statement.
	 */
	private function is_contract_update( string $query ): bool {
		$table = preg_quote( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), '/' );

		return 1 === preg_match( "/^\\s*UPDATE\\s+`?{$table}`?\\s/i", $query );
	}

	/**
	 * @testdox An over-long payment method or title is refused by insert and update_fields alike, and nothing is stored.
	 * @dataProvider provide_over_long_payment_instruments
	 *
	 * @param string $method Payment method id.
	 * @param string $title  Payment method title.
	 */
	public function test_an_over_long_payment_instrument_is_refused_on_insert_and_update( string $method, string $title ): void {
		$over_long = new PaymentInstrumentRef( 55, $method, $title );

		$fresh = $this->make_contract();
		$fresh->set_payment_instrument( $over_long );
		try {
			$this->sut->insert( $fresh );
			$this->fail( 'Expected insert() to refuse the over-long value.' );
		} catch ( \RuntimeException $e ) {
			$this->assertNull( $fresh->get_id(), 'Nothing is inserted.' );
		}

		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_payment_instrument( $over_long );
		$contract->set_status( ContractStatus::ON_HOLD );

		try {
			$this->sut->update_fields( $contract, array( 'status', 'payment_method', 'payment_method_title' ) );
			$this->fail( 'Expected update_fields() to refuse the over-long value.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'longer than the column allows', $e->getMessage() );
		}

		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( 'woocommerce_payments', $stored->get_payment_instrument()->get_gateway(), 'update_fields() wrote nothing.' );
		$this->assertSame( 'Credit card', $stored->get_payment_instrument()->get_title() );
		$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );

		$fits = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $fits );
		$fits->set_payment_instrument( new PaymentInstrumentRef( 55, str_repeat( 'm', 100 ), str_repeat( 'é', 200 ) ) );
		$this->sut->update_fields( $fits, array( 'payment_method', 'payment_method_title' ) );
		$written = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $written );
		$this->assertSame( str_repeat( 'é', 200 ), $written->get_payment_instrument()->get_title(), 'A value at the column length (in characters) is written.' );
	}

	/**
	 * @testdox An over-long cycle kind is refused by append_cycle and nothing is stored; one at the column length is written.
	 */
	public function test_an_over_long_cycle_kind_is_refused_on_append(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$over_long = $this->make_cycle_of_kind( $id, str_repeat( 'k', 21 ) );
		try {
			$this->sut->append_cycle( $over_long );
			$this->fail( 'Expected append_cycle() to refuse the over-long kind.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'longer than the column allows', $e->getMessage() );
		}
		$this->assertNull( $over_long->get_id(), 'Nothing is inserted.' );
		$this->assertSame( array(), $this->sut->find_cycle_history( $id, str_repeat( 'k', 21 ) ) );

		$fits = $this->make_cycle_of_kind( $id, str_repeat( 'k', 20 ) );
		$this->sut->append_cycle( $fits );
		$this->assertNotNull( $fits->get_id(), 'A kind at the column length is written.' );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provide_over_long_payment_instruments(): array {
		return array(
			'payment method over 100'       => array( str_repeat( 'm', 101 ), 'Credit card' ),
			'payment method title over 200' => array( 'woocommerce_payments', str_repeat( 't', 201 ) ),
		);
	}

	/**
	 * @testdox transition_cycle is a compare-and-set: one caller wins, a caller that read the old status loses.
	 */
	public function test_transition_cycle_is_an_atomic_compare_and_set(): void {
		$cycle_id = $this->pending_cycle_id();

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array( 'order_id' => 4242 ) ) );

		$settled = $this->find_cycle( $cycle_id );
		$this->assertSame( CycleStatus::BILLED, $settled->get_status()->get_value() );
		$this->assertSame( 4242, $settled->get_order_id() );

		$this->assertFalse( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array( 'order_id' => 9999 ) ) );

		$after = $this->find_cycle( $cycle_id );
		$this->assertSame( CycleStatus::BILLED, $after->get_status()->get_value(), 'The losing transition writes nothing.' );
		$this->assertSame( 4242, $after->get_order_id() );
	}

	/**
	 * @testdox A same-status transition_cycle that changes nothing still returns true; on a cycle in another status it returns false.
	 */
	public function test_a_same_status_transition_that_changes_nothing_returns_true(): void {
		$cycle_id = $this->pending_cycle_id();

		// Start early in a second so both calls stamp the same date_updated_gmt and the
		// second one matches the row without changing it.
		while ( fmod( microtime( true ), 1.0 ) > 0.5 ) {
			usleep( 50000 );
		}
		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::PENDING, array( 'order_id' => 4242 ) ) );
		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::PENDING, array( 'order_id' => 4242 ) ), 'Writing the stored values again still succeeds.' );

		$this->assertFalse( $this->sut->transition_cycle( $cycle_id, CycleStatus::BILLED, CycleStatus::BILLED, array( 'order_id' => 4242 ) ), 'A cycle in another status is not matched.' );
		$this->assertSame( CycleStatus::PENDING, $this->find_cycle( $cycle_id )->get_status()->get_value() );
	}

	/**
	 * @testdox transition_cycle keeps the stored order when order_id is omitted and clears it on null.
	 */
	public function test_transition_cycle_order_id_is_written_only_when_present(): void {
		$cycle_id = $this->pending_cycle_id();
		$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::PENDING, array( 'order_id' => 4242 ) );

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array( 'reason' => 'payment-failed' ) ) );
		$failed = $this->find_cycle( $cycle_id );
		$this->assertSame( 4242, $failed->get_order_id(), 'An omitted order_id keeps the stored one.' );
		$this->assertSame( 'payment-failed', $failed->get_reason() );

		$this->assertTrue(
			$this->sut->transition_cycle(
				$cycle_id,
				CycleStatus::FAILED,
				CycleStatus::CANCELLED,
				array(
					'order_id' => null,
					'reason'   => null,
				)
			)
		);
		$cancelled = $this->find_cycle( $cycle_id );
		$this->assertNull( $cancelled->get_order_id(), 'A null order_id clears it.' );
		$this->assertNull( $cancelled->get_reason(), 'A null reason clears it.' );
	}

	/**
	 * @testdox transition_cycle rejects an unregistered target status and writes nothing.
	 */
	public function test_transition_cycle_rejects_an_unregistered_target(): void {
		$cycle_id = $this->pending_cycle_id();

		try {
			$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, 'never-registered', array( 'order_id' => 4242 ) );
			$this->fail( 'Expected a DomainException for an unregistered target status.' );
		} catch ( \DomainException $e ) {
			$this->assertSame( CycleStatus::PENDING, $this->find_cycle( $cycle_id )->get_status()->get_value() );
		}
	}

	/**
	 * @testdox transition_cycle accepts an extension-registered target status.
	 */
	public function test_transition_cycle_accepts_an_extension_registered_target(): void {
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );
		$cycle_id = $this->pending_cycle_id();

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, 'disputed', array() ) );

		$this->assertSame( 'disputed', $this->find_cycle( $cycle_id )->get_status()->get_value() );
	}

	/**
	 * @testdox A billed cycle hydrates its plan and items snapshots.
	 */
	public function test_a_billed_cycle_hydrates_its_snapshots(): void {
		$id           = $this->sut->insert( $this->make_contract() );
		$snapshot_ids = $this->seed_cycle_snapshots( $id );
		$cycle        = Cycle::create(
			array(
				'contract_id'       => $id,
				'sequence_no'       => 1,
				'count'             => 1,
				'status'            => new CycleStatus( CycleStatus::BILLED ),
				'starts_at_gmt'     => '2026-07-15 00:00:00',
				'ends_at_gmt'       => '2026-08-15 00:00:00',
				'expected_total'    => '19.99',
				'currency'          => 'USD',
				'plan_snapshot_id'  => $snapshot_ids[0],
				'items_snapshot_id' => $snapshot_ids[1],
			)
		);
		$this->sut->append_cycle( $cycle );

		$head = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertInstanceOf( PlanSnapshot::class, $head->get_plan_snapshot() );
		$this->assertInstanceOf( ItemsSnapshot::class, $head->get_items_snapshot() );
	}

	/**
	 * A pending cycle 1 on a new contract.
	 */
	private function pending_cycle_id(): int {
		$id = $this->sut->insert( $this->make_contract() );

		return (int) $this->append_pending_cycle( $id )->get_id();
	}

	/**
	 * Read a billing cycle by id through its chain history.
	 *
	 * @param int $cycle_id Cycle id.
	 */
	private function find_cycle( int $cycle_id ): Cycle {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$contract_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT contract_id FROM {$table} WHERE id = %d", $cycle_id ) );

		foreach ( $this->sut->find_cycle_history( $contract_id ) as $cycle ) {
			if ( $cycle->get_id() === $cycle_id ) {
				return $cycle;
			}
		}

		$this->fail( sprintf( 'Cycle %d not found.', $cycle_id ) );
	}

	/**
	 * @testdox An unknown stored contract status hydrates and survives an unrelated update.
	 */
	public function test_an_unknown_stored_contract_status_round_trips(): void {
		global $wpdb;

		$id    = $this->sut->insert( $this->make_contract() );
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $table, array( 'status' => 'legacy-paused' ), array( 'id' => $id ) );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$this->assertSame( 'legacy-paused', $contract->get_status() );

		$contract->set_next_payment_gmt( '2026-09-15 00:00:00' );
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stored = $wpdb->get_row( $wpdb->prepare( "SELECT status, next_payment_gmt FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		$this->assertSame( 'legacy-paused', $stored['status'] );
		$this->assertSame( '2026-09-15 00:00:00', $stored['next_payment_gmt'] );
	}

	/**
	 * @testdox An unknown stored cycle status hydrates through every cycle read.
	 */
	public function test_an_unknown_stored_cycle_status_hydrates_through_every_cycle_read(): void {
		global $wpdb;

		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', ...$this->seed_cycle_snapshots( $id ) );
		$this->sut->append_cycle( $cycle );

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $table, array( 'status' => 'legacy-x' ), array( 'id' => $cycle->get_id() ) );

		$head = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 'legacy-x', $head->get_status()->get_value() );

		$history = $this->sut->find_cycle_history( $id );
		$this->assertCount( 1, $history );
		$this->assertSame( 'legacy-x', $history[0]->get_status()->get_value() );
	}

	/**
	 * @testdox count_by_status keys include extension-registered contract statuses.
	 */
	public function test_count_by_status_includes_registered_extension_statuses(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'paused-by-merchant' );
		$this->insert_list_contract( 'paused-by-merchant' );

		$counts = $this->sut->count_by_status();

		$this->assertSame( ContractStatus::get_all(), array_keys( $counts ) );
		$this->assertSame( 1, $counts['paused-by-merchant'] );
	}

	/**
	 * The contract ids of the due scan at `$now`, in scan order.
	 *
	 * @param \DateTimeImmutable      $now    The cutoff moment.
	 * @param int                     $limit  The batch size.
	 * @param array<int, string>|null $owners Owners to scan; defaults to the test owner.
	 * @return array<int, int>
	 */
	private function due_ids( \DateTimeImmutable $now, int $limit, ?array $owners = null ): array {
		return array_map(
			static function ( array $row ): int {
				return $row['id'];
			},
			$this->sut->find_due( $now, $limit, $owners ?? array( self::OWNER ) )
		);
	}

	/**
	 * Insert a contract with the given schedule date and status, plus a head cycle so the
	 * cycle-aware scan can join it. The head is billed and ends when the next payment is due
	 * (advance-ready) unless overridden. Returns the contract id.
	 *
	 * @param string|null $next_payment_gmt The next-payment date, or null for no schedule/head.
	 * @param string      $status           The contract status (a ContractStatus value).
	 * @param string      $schedule_source  Who keeps the schedule (the engine, or a gateway/extension slug).
	 * @param string      $head_status      The head cycle status (a CycleStatus value).
	 * @param string|null $head_ends_at     The head period end; defaults to `$next_payment_gmt`.
	 * @param string      $owner            The owning extension slug; defaults to the registered test owner.
	 */
	private function insert_contract_due_at(
		?string $next_payment_gmt,
		string $status,
		string $schedule_source = Contract::SCHEDULE_SOURCE_ENGINE,
		string $head_status = CycleStatus::BILLED,
		?string $head_ends_at = null,
		string $owner = self::OWNER
	): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => 7,
				'origin_order_id'  => 1001,
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => $next_payment_gmt,
				'status'           => $status,
				'schedule_source'  => $schedule_source,
				'extension_slug'   => $owner,
			)
		);
		$id       = $this->sut->insert( $contract );

		if ( null !== $next_payment_gmt ) {
			$this->sut->append_cycle(
				Cycle::create(
					array(
						'contract_id'    => $id,
						'sequence_no'    => 1,
						'count'          => 1,
						'status'         => new CycleStatus( $head_status ),
						'starts_at_gmt'  => '2026-01-15 00:00:00',
						'ends_at_gmt'    => $head_ends_at ?? $next_payment_gmt,
						'expected_total' => '19.99',
						'currency'       => 'USD',
					)
				)
			);
		}

		return $id;
	}

	/**
	 * @testdox A duplicate (contract_id, kind, sequence_no) is rejected by the UNIQUE index.
	 */
	public function test_duplicate_sequence_no_is_rejected(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );

		$cycles_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$now          = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$cycles_table,
			array(
				'contract_id'      => $id,
				'kind'             => Cycle::KIND_BILLING,
				'sequence_no'      => 1,
				'count'            => 99,
				'status'           => CycleStatus::PENDING,
				'starts_at_gmt'    => '2026-09-15 00:00:00',
				'ends_at_gmt'      => '2026-10-15 00:00:00',
				'expected_total'   => '19.99',
				'currency'         => 'USD',
				'date_created_gmt' => $now,
				'date_updated_gmt' => $now,
			)
		);

		$this->assertFalse( $inserted, 'A duplicate (contract_id, kind, sequence_no) must be rejected by the UNIQUE index.' );
	}

	/**
	 * @testdox A duplicate (contract_id, kind, count) is rejected by the UNIQUE index.
	 */
	public function test_duplicate_count_is_rejected(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );

		$cycles_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$now          = gmdate( 'Y-m-d H:i:s' );

		// Same count (1) at a different sequence_no must violate UNIQUE(contract_id, kind, count).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$cycles_table,
			array(
				'contract_id'      => $id,
				'kind'             => Cycle::KIND_BILLING,
				'sequence_no'      => 2,
				'count'            => 1,
				'status'           => CycleStatus::PENDING,
				'starts_at_gmt'    => '2026-09-15 00:00:00',
				'ends_at_gmt'      => '2026-10-15 00:00:00',
				'expected_total'   => '19.99',
				'currency'         => 'USD',
				'date_created_gmt' => $now,
				'date_updated_gmt' => $now,
			)
		);

		$this->assertFalse( $inserted, 'A duplicate (contract_id, kind, count) must be rejected by the UNIQUE index.' );
	}

	/**
	 * @testdox append_cycle surfaces a UNIQUE collision as DuplicateCycleException.
	 *
	 * The append race signal must be distinguishable from any other write failure,
	 * so the money-path can treat the collision as benign without masking real errors.
	 */
	public function test_append_cycle_throws_duplicate_cycle_exception_on_collision(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );

		$this->expectException( DuplicateCycleException::class );
		$this->sut->append_cycle( $this->make_cycle( $id, 2, 1, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ) );
	}

	/**
	 * Build a billing cycle whose position the append assigns.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $overrides   Extra or replacement keys.
	 */
	private function make_unpositioned_cycle( int $contract_id, array $overrides = array() ): Cycle {
		return Cycle::create(
			array_merge(
				array(
					'contract_id'   => $contract_id,
					'starts_at_gmt' => '2026-07-15 00:00:00',
					'ends_at_gmt'   => '2026-08-15 00:00:00',
					'currency'      => 'USD',
				),
				$overrides
			)
		);
	}

	/**
	 * @testdox append_cycle assigns sequence 1 to the first unpositioned cycle and leaves the count alone.
	 */
	public function test_append_cycle_assigns_the_first_sequence_no(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->make_unpositioned_cycle( $id );

		$this->sut->append_cycle( $cycle );

		$this->assertSame( 1, $cycle->get_sequence_no() );
		$this->assertNull( $cycle->get_count() );
		$head = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( $cycle->get_id(), $head->get_id() );
	}

	/**
	 * @testdox append_cycle assigns the head's sequence + 1 within the cycle's own chain.
	 */
	public function test_append_cycle_assigns_the_next_sequence_no_in_its_chain(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );
		$this->sut->append_cycle( $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ) );

		$next  = $this->make_unpositioned_cycle( $id, array( 'count' => 3 ) );
		$other = $this->make_unpositioned_cycle( $id, array( 'kind' => 'shipping' ) );
		$this->sut->append_cycle( $next );
		$this->sut->append_cycle( $other );

		$this->assertSame( 3, $next->get_sequence_no() );
		$this->assertSame( 3, $next->get_count() );
		$this->assertSame( 1, $other->get_sequence_no() );
	}

	/**
	 * @testdox Appends without a sequence_no, locked or not, take the next position in one insert.
	 */
	public function test_append_without_a_sequence_no_takes_the_next_position(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );

		$first = $this->make_unpositioned_cycle( $id );
		$this->sut->append_cycle( $first );

		$lock   = $this->lock( $id );
		$second = $this->make_unpositioned_cycle( $id );
		$this->sut->append_cycle( $second, $lock );
		$lock->release();

		$third   = $this->make_unpositioned_cycle( $id );
		$queries = $wpdb->num_queries;
		$this->sut->append_cycle( $third );

		$this->assertSame( array( 1, 2, 3 ), array( $first->get_sequence_no(), $second->get_sequence_no(), $third->get_sequence_no() ) );
		$this->assertSame( 2, $wpdb->num_queries - $queries, 'The insert, then the read of its assigned position.' );
		$head = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( $third->get_id(), $head->get_id() );
	}

	/**
	 * @testdox A failed chain head or contract read throws instead of reading as empty or missing.
	 */
	public function test_failed_deciding_reads_throw(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_unpositioned_cycle( $id ) );

		$this->fail_next_query( '/ORDER BY sequence_no DESC LIMIT 1/i' );
		try {
			$this->sut->find_chain_head( $id );
			$this->fail( 'Expected the failed head read to throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'chain head', $e->getMessage() );
		}

		$this->fail_next_query( '/^\s*SELECT\s+\*,\s*\(\s*lock_token/i' );
		$this->expectException( \RuntimeException::class );
		$this->sut->find( $id );
	}

	/**
	 * @testdox Multiple non-counting cycles (count = null) coexist in one chain.
	 */
	public function test_multiple_null_count_cycles_coexist(): void {
		$id = $this->sut->insert( $this->make_contract() );

		// MySQL treats NULLs as distinct, so two count = null cycles do not collide
		// under UNIQUE(contract_id, kind, count).
		$this->sut->append_cycle( $this->make_cycle( $id, 1, null, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );
		$this->sut->append_cycle( $this->make_cycle( $id, 2, null, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ) );

		$history = $this->sut->find_cycle_history( $id );
		$this->assertCount( 2, $history );
		$this->assertNull( $history[0]->get_count() );
		$this->assertNull( $history[1]->get_count() );
	}

	/**
	 * @testdox delete removes the contract, its children, cycles, and snapshots.
	 */
	public function test_delete_removes_contract_children_cycles_and_snapshots(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', ...$this->seed_cycle_snapshots( $id ) ) );

		$this->assertTrue( $this->sut->delete( $id ) );
		$this->assertNull( $this->sut->find( $id ) );

		foreach ( array(
			SchemaInstaller::TABLE_CONTRACT_ITEMS,
			SchemaInstaller::TABLE_CYCLES,
			SchemaInstaller::TABLE_SNAPSHOTS,
		) as $child ) {
			$table = SchemaInstaller::get_table_name( $child );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$remaining = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE contract_id = %d", $id ) );
			$this->assertSame( '0', $remaining, "Rows must be removed from {$table} when the contract is deleted." );
		}
	}

	/**
	 * Acquire a lock, asserting it was granted.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label.
	 */
	private function lock( int $contract_id, string $operation = 'test' ): ContractLock {
		$lock = $this->sut->acquire_lock( $contract_id, $operation );
		$this->assertInstanceOf( ContractLock::class, $lock );

		return $lock;
	}

	/**
	 * Run `$run` with the next query matching `$pattern` replaced by invalid SQL.
	 *
	 * @param string   $pattern Regex the statement to break must match.
	 * @param callable $run     Code under test.
	 * @param int      $skip    Matching statements to let through first.
	 */
	private function with_failing_query( string $pattern, callable $run, int $skip = 0 ): void {
		global $wpdb;

		$broken = false;
		$break  = static function ( string $query ) use ( $pattern, &$broken, &$skip ): string {
			if ( ! $broken && 1 === preg_match( $pattern, $query ) && 0 === $skip-- ) {
				$broken = true;
				return 'SELECT * FROM table_that_does_not_exist_for_tests';
			}
			return $query;
		};

		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );
		try {
			$run();
		} finally {
			$wpdb->suppress_errors( $suppress );
			remove_filter( 'query', $break );
		}
		$this->assertTrue( $broken, 'The targeted statement ran.' );
	}

	/**
	 * A stored contract with its status changed in memory.
	 *
	 * @param int    $id     Contract id.
	 * @param string $status New status.
	 */
	private function with_status( int $id, string $status ): Contract {
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( $status );

		return $contract;
	}

	/**
	 * The stored status of a contract.
	 *
	 * @param int $id Contract id.
	 */
	private function stored_status( int $id ): string {
		$contract = $this->sut->find_summary( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract->get_status();
	}

	/**
	 * @testdox A row write with the live lock lands; without a lock it is refused while the lock lives and lands after.
	 */
	public function test_contract_row_writes_are_fenced(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->lock( $id, 'hold' );

		$this->assertTrue( $this->sut->update_fields( $this->with_status( $id, ContractStatus::ON_HOLD ), array( 'status' ), $lock ) );
		$this->assertSame( ContractStatus::ON_HOLD, $this->stored_status( $id ) );

		try {
			$this->sut->update_fields( $this->with_status( $id, ContractStatus::CANCELLED ), array( 'status' ) );
			$this->fail( 'Expected an unlocked write to be refused while the lock lives.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'hold', $e->get_operation() );
			$this->assertSame( '2035-01-01 00:10:00', $e->get_locked_until() ? $e->get_locked_until()->format( 'Y-m-d H:i:s' ) : null );
		}
		$this->assertSame( ContractStatus::ON_HOLD, $this->stored_status( $id ) );

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->assertTrue( $this->sut->update_fields( $this->with_status( $id, ContractStatus::CANCELLED ), array( 'status' ) ) );
		$this->assertSame( ContractStatus::CANCELLED, $this->stored_status( $id ) );

		try {
			$this->sut->update_fields( $this->with_status( $id, ContractStatus::ACTIVE ), array( 'status' ), $lock );
			$this->fail( 'Expected the expired lock to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertNull( $e->get_operation(), 'No live holder.' );
		}
		$this->assertSame( ContractStatus::CANCELLED, $this->stored_status( $id ) );
	}

	/**
	 * @testdox A lock for another contract is refused before any write.
	 */
	public function test_a_lock_for_another_contract_is_refused(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$other = $this->sut->insert( $this->make_contract() );
		$lock  = $this->lock( $other );

		$this->expectException( \InvalidArgumentException::class );
		$this->sut->update_fields( $this->with_status( $id, ContractStatus::ON_HOLD ), array( 'status' ), $lock );
	}

	/**
	 * @testdox An identical same-second write with the lock still matches and returns true.
	 */
	public function test_an_identical_locked_write_is_a_no_op(): void {
		global $wpdb;

		$id       = $this->sut->insert( $this->make_contract() );
		$lock     = $this->lock( $id );
		$contract = $this->with_status( $id, ContractStatus::ACTIVE );

		for ( $i = 0; $i < 2; $i++ ) {
			$this->assertTrue( $this->sut->update_fields( $contract, array( 'status' ), $lock ) );
			$this->assertSame( 1, $wpdb->rows_affected );
		}
	}

	/**
	 * @testdox A write to a deleted contract returns false with and without a lock.
	 */
	public function test_a_missing_row_returns_false(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->with_status( $id, ContractStatus::ON_HOLD );
		$lock     = $this->lock( $id );
		$this->sut->delete( $id );

		$this->assertFalse( $this->sut->update_fields( $contract, array( 'status' ), $lock ) );
		$this->assertFalse( $this->sut->update_fields( $contract, array( 'status' ) ) );
	}

	/**
	 * @testdox An unlocked items replacement runs under an internal lock: refused while another lock lives, released after.
	 */
	public function test_an_unlocked_children_write_takes_an_internal_lock(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$items = $this->item_ids( $id );
		$lock  = $this->lock( $id, 'hold' );

		$contract = $this->with_status( $id, ContractStatus::ON_HOLD );
		try {
			$this->sut->update_fields( $contract, array( 'status', 'items' ) );
			$this->fail( 'Expected the write to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'hold', $e->get_operation() );
		}
		$this->assertSame( $items, $this->item_ids( $id ), 'Nothing is written.' );
		$this->assertSame( ContractStatus::ACTIVE, $this->stored_status( $id ) );

		$lock->release();
		$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'items' ) ) );
		$this->assertSame( ContractStatus::ON_HOLD, $this->stored_status( $id ) );
		$this->assertNull( $this->sut->lock_holder( $id )['operation'], 'The internal lock is released.' );
	}

	/**
	 * @testdox Locked cycle appends and transitions land; unlocked ones are refused while another lock lives.
	 */
	public function test_cycle_writes_are_fenced(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$lock = $this->lock( $id, 'renewal' );

		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' );
		$this->sut->append_cycle( $cycle, $lock );
		$cycle_id = (int) $cycle->get_id();
		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array(), $lock ) );

		try {
			$this->sut->append_cycle( $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ) );
			$this->fail( 'Expected an unlocked append to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'renewal', $e->get_operation() );
		}
		$this->assertCount( 1, $this->sut->find_cycle_history( $id ), 'Nothing is appended.' );

		try {
			$this->sut->transition_cycle( $cycle_id, CycleStatus::FAILED, CycleStatus::BILLED, array() );
			$this->fail( 'Expected an unlocked transition to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( CycleStatus::FAILED, $this->find_cycle( $cycle_id )->get_status()->get_value() );
		}

		$this->pin_db_clock( '2035-01-01 00:10:00' );
		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::FAILED, CycleStatus::BILLED, array() ) );
		$next = $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' );
		$this->sut->append_cycle( $next );
		$this->assertNotNull( $next->get_id() );
	}

	/**
	 * @testdox An unlocked append to an unknown contract throws and writes no orphan row.
	 */
	public function test_an_append_to_an_unknown_contract_throws(): void {
		global $wpdb;

		try {
			$this->sut->append_cycle( $this->make_cycle( 999999, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );
			$this->fail( 'Expected an append to an unknown contract to throw.' );
		} catch ( \InvalidArgumentException $e ) {
			$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE contract_id = %d", 999999 ) ) );
		}
	}

	/**
	 * @testdox A lock for one contract cannot append to or transition another contract's cycles.
	 */
	public function test_a_lock_cannot_write_another_contracts_cycles(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$other    = $this->sut->insert( $this->make_contract() );
		$cycle_id = (int) $this->append_pending_cycle( $other )->get_id();
		$lock     = $this->lock( $id );

		try {
			$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array(), $lock );
			$this->fail( 'Expected another contract\'s cycle to be refused.' );
		} catch ( \InvalidArgumentException $e ) {
			$this->assertSame( CycleStatus::PENDING, $this->find_cycle( $cycle_id )->get_status()->get_value() );
		}

		$this->expectException( \InvalidArgumentException::class );
		$this->sut->append_cycle( $this->make_cycle( $other, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ), $lock );
	}

	/**
	 * @testdox A lost compare-and-set under the lock returns false; a missing cycle throws.
	 */
	public function test_a_lost_locked_transition_returns_false(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$cycle_id = (int) $this->append_pending_cycle( $id )->get_id();
		$lock     = $this->lock( $id );

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array(), $lock ) );
		$this->assertFalse( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array(), $lock ) );

		$this->expectException( \InvalidArgumentException::class );
		$this->sut->transition_cycle( 999999, CycleStatus::PENDING, CycleStatus::BILLED, array(), $lock );
	}

	/**
	 * @testdox A database failure in a cycle transition throws instead of returning false.
	 */
	public function test_a_failed_transition_throws(): void {
		$cycle_id = $this->pending_cycle_id();

		$this->with_failing_query(
			'/^\s*UPDATE\s+\S*cycles/i',
			function () use ( $cycle_id ): void {
				try {
					$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array() );
					$this->fail( 'Expected a RuntimeException.' );
				} catch ( \RuntimeException $e ) {
					$this->assertStringContainsString( 'Failed to transition cycle', $e->getMessage() );
				}
			}
		);
		$this->assertSame( CycleStatus::PENDING, $this->find_cycle( $cycle_id )->get_status()->get_value() );
	}

	/**
	 * @testdox Replacing items leaves exactly the new rows, in order.
	 */
	public function test_replacing_items_leaves_the_new_rows_in_order(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_items( $this->two_items() );
		$lock = $this->lock( $id );

		$this->sut->update_fields( $contract, array( 'items' ), $lock );

		$this->assertSame( array( 'Tea tin', 'Mug' ), $this->item_names( $id ) );
	}

	/**
	 * @testdox A failed item delete leaves old and new rows side by side, and the next replacement repairs it.
	 */
	public function test_a_failed_item_delete_is_repaired_by_the_next_replacement(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_items( $this->two_items() );
		$lock = $this->lock( $id );

		$this->with_failing_query(
			'/^\s*DELETE\s+FROM\s+\S*contract_items/i',
			function () use ( $contract, $lock ): void {
				try {
					$this->sut->update_fields( $contract, array( 'items' ), $lock );
					$this->fail( 'Expected the failed delete to throw.' );
				} catch ( \RuntimeException $e ) {
					$this->assertStringContainsString( 'Failed to delete item rows', $e->getMessage() );
				}
			}
		);
		$this->assertSame( array( 'Coffee bag', 'Tea tin', 'Mug' ), $this->item_names( $id ), 'Old and new rows side by side, never an empty set.' );

		$this->sut->update_fields( $contract, array( 'items' ), $lock );
		$this->assertSame( array( 'Tea tin', 'Mug' ), $this->item_names( $id ) );
	}

	/**
	 * @testdox A failed item insert keeps the old item set.
	 */
	public function test_a_failed_item_insert_keeps_the_old_items(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_items( $this->two_items() );
		$lock = $this->lock( $id );

		$this->with_failing_query(
			'/^\s*INSERT\s+INTO\s+\S*contract_items/i',
			function () use ( $contract, $lock ): void {
				try {
					$this->sut->update_fields( $contract, array( 'items' ), $lock );
					$this->fail( 'Expected the failed insert to throw.' );
				} catch ( \RuntimeException $e ) {
					$this->assertStringContainsString( 'Failed to insert item row', $e->getMessage() );
				}
			}
		);
		$this->assertSame( array( 'Coffee bag' ), $this->item_names( $id ) );
	}

	/**
	 * @testdox A failed second item insert removes the first new row and leaves exactly the old set.
	 */
	public function test_a_failed_second_item_insert_leaves_exactly_the_old_items(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_items( $this->two_items() );
		$lock = $this->lock( $id );

		$this->with_failing_query(
			'/^\s*INSERT\s+INTO\s+\S*contract_items/i',
			function () use ( $contract, $lock ): void {
				try {
					$this->sut->update_fields( $contract, array( 'items' ), $lock );
					$this->fail( 'Expected the failed insert to throw.' );
				} catch ( \RuntimeException $e ) {
					$this->assertStringContainsString( 'Failed to insert item row', $e->getMessage() );
				}
			},
			1
		);
		$this->assertSame( array( 'Coffee bag' ), $this->item_names( $id ) );

		$this->sut->update_fields( $contract, array( 'items' ), $lock );
		$this->assertSame( array( 'Tea tin', 'Mug' ), $this->item_names( $id ) );
	}

	/**
	 * @testdox A failed address insert on create leaves no contract, and a retry with the same creation key succeeds.
	 */
	public function test_a_failed_create_is_discarded_and_can_be_retried(): void {
		global $wpdb;

		foreach ( array( 'insert', 'insert_locked' ) as $method ) {
			$key   = 'order:' . $method;
			$build = function () use ( $key ): Contract {
				$contract = $this->make_contract();
				$storage  = $contract->to_storage();
				return Contract::create(
					array(
						'extension_slug' => 'lite',
						'creation_key'   => $key,
						'items'          => $contract->get_items(),
						'addresses'      => $contract->get_addresses(),
						'currency'       => $storage['currency'],
					)
				);
			};

			$this->with_failing_query(
				'/^\s*INSERT\s+INTO\s+\S*contract_addresses/i',
				function () use ( $build, $method ): void {
					try {
						'insert' === $method ? $this->sut->insert( $build() ) : $this->sut->insert_locked( $build(), 'checkout' );
						$this->fail( 'Expected the failed address insert to throw.' );
					} catch ( \RuntimeException $e ) {
						$this->assertStringContainsString( 'address row', $e->getMessage() );
					}
				},
				1
			);

			$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
			$items     = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$contracts} WHERE creation_key = %s", $key ) ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->assertSame( '0', $wpdb->get_var( "SELECT COUNT(*) FROM {$items} WHERE contract_id NOT IN ( SELECT id FROM {$contracts} )" ), 'No orphaned items.' );

			$retry = $build();
			'insert' === $method ? $this->sut->insert( $retry ) : $this->sut->insert_locked( $retry, 'checkout' );
			$this->assertIsInt( $retry->get_id() );
			$this->assertSame( array( 'Coffee bag' ), $this->item_names( (int) $retry->get_id() ) );
		}
	}

	/**
	 * @testdox Replacing addresses upserts the given types in place and removes the others.
	 */
	public function test_replacing_addresses_upserts_and_prunes(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$lock = $this->lock( $id );

		$contract->set_addresses(
			array(
				Contract::ADDRESS_BILLING => array(
					'first_name' => 'Grace',
					'last_name'  => 'Hopper',
					'country'    => 'US',
				),
			)
		);
		$this->sut->update_fields( $contract, array( 'addresses' ), $lock );

		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$addresses = $stored->get_addresses();
		$this->assertSame( array( Contract::ADDRESS_BILLING ), array_keys( $addresses ) );
		$this->assertSame( 'Grace', $addresses[ Contract::ADDRESS_BILLING ]['first_name'] );
		$this->assertNull( $addresses[ Contract::ADDRESS_BILLING ]['email'], 'An omitted column is cleared on upsert.' );

		$contract->set_addresses( array() );
		$this->sut->update_fields( $contract, array( 'addresses' ), $lock );
		$cleared = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $cleared );
		$this->assertSame( array(), $cleared->get_addresses() );
	}

	/**
	 * @testdox A creation key is unique per extension; a duplicate inserts nothing and returns null.
	 */
	public function test_creation_key_is_unique_per_extension(): void {
		global $wpdb;

		$this->assertNotNull( $this->sut->insert_locked( $this->keyed_contract( 'lite', 'order:42' ), 'checkout' ) );
		$this->assertNull( $this->sut->insert_locked( $this->keyed_contract( 'lite', 'order:42' ), 'checkout' ) );

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( '1', $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE creation_key = %s AND extension_slug = %s", 'order:42', 'lite' ) ) );

		$this->assertNotNull( $this->sut->insert_locked( $this->keyed_contract( 'other-ext', 'order:42' ), 'checkout' ), 'Another extension may use the same key.' );
		$this->assertNotNull( $this->sut->insert_locked( $this->keyed_contract( 'lite', null ), 'checkout' ) );
		$this->assertNotNull( $this->sut->insert_locked( $this->keyed_contract( 'lite', null ), 'checkout' ), 'Null keys never collide.' );
	}

	/**
	 * @testdox An unlocked insert of a keyed contract whose key is taken throws instead of returning no id.
	 */
	public function test_an_unlocked_insert_with_a_taken_key_throws(): void {
		$this->sut->insert( $this->keyed_contract( 'lite', 'order:43' ) );

		$this->expectException( \RuntimeException::class );
		$this->sut->insert( $this->keyed_contract( 'lite', 'order:43' ) );
	}

	/**
	 * @testdox A locked insert creates the contract locked from birth.
	 */
	public function test_a_locked_insert_is_locked_from_birth(): void {
		$contract = $this->make_contract();
		$lock     = $this->sut->insert_locked( $contract, 'checkout' );
		$id       = (int) $contract->get_id();

		$this->assertNotNull( $lock );
		$this->assertSame( $id, $lock->get_contract_id() );
		$this->assertSame( 'checkout', $this->sut->lock_holder( $id )['operation'] );
		$this->assertCount( 1, $this->item_ids( $id ), 'Items are written under the lock.' );

		try {
			$this->sut->update_fields( $this->with_status( $id, ContractStatus::ON_HOLD ), array( 'status' ) );
			$this->fail( 'Expected an unlocked write to be refused.' );
		} catch ( ContractLockedException $e ) {
			$this->assertSame( 'checkout', $e->get_operation() );
		}

		$lock->release();
		$this->assertTrue( $this->sut->update_fields( $this->with_status( $id, ContractStatus::ON_HOLD ), array( 'status' ) ) );
	}

	/**
	 * @testdox A locked insert with a taken creation key returns null and locks nothing.
	 */
	public function test_a_locked_insert_with_a_duplicate_key_returns_null(): void {
		$first = $this->sut->insert( $this->keyed_contract( 'lite', 'order:7' ) );

		$this->assertNull( $this->sut->insert_locked( $this->keyed_contract( 'lite', 'order:7' ), 'checkout' ) );
		$this->assertNull( $this->sut->lock_holder( $first )['operation'] );
	}

	/**
	 * A minimal contract with a creation key.
	 *
	 * @param string      $extension_slug Owning extension.
	 * @param string|null $creation_key   Creation key, or null.
	 */
	private function keyed_contract( string $extension_slug, ?string $creation_key ): Contract {
		return Contract::create(
			array(
				'extension_slug' => $extension_slug,
				'creation_key'   => $creation_key,
			)
		);
	}

	/**
	 * Two replacement items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function two_items(): array {
		return array(
			array(
				'item_name' => 'Tea tin',
				'item_type' => 'line_item',
				'quantity'  => '1',
				'total'     => '10.00',
			),
			array(
				'item_name' => 'Mug',
				'item_type' => 'line_item',
				'quantity'  => '1',
				'total'     => '5.00',
			),
		);
	}

	/**
	 * The item names of a contract, oldest row first.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<int, string>
	 */
	private function item_names( int $contract_id ): array {
		$contract = $this->sut->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $contract );

		return array_map(
			static function ( array $item ): string {
				return is_string( $item['item_name'] ?? null ) ? $item['item_name'] : '';
			},
			$contract->get_items()
		);
	}
}

<?php
/**
 * Integration tests for the lean ContractRepository and its targeted cycle access.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
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
	 * @testdox Meta written through the meta methods survives whole-contract writes.
	 */
	public function test_meta_survives_a_whole_entity_update(): void {
		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->add_meta( $id, 'source_channel', 'pdp' );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$this->assertTrue( $this->sut->update( $contract ) );
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
	 * @testdox update persists the contract-row cache without touching the cycle rows.
	 */
	public function test_update_persists_the_contract_cache(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$contract->set_next_payment_gmt( '2026-08-15 00:00:00' );

		$this->assertTrue( $this->sut->update( $contract ) );

		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::ON_HOLD, $reloaded->get_status() );
		$this->assertSame( '2026-08-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox update leaves unchanged child rows in place (no churn).
	 */
	public function test_update_does_not_churn_unchanged_children(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );

		$items_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$item_id_before = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$items_table} WHERE contract_id = %d", $id ) );

		// A cache-only update (status) must not delete-and-reinsert the items row.
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$this->sut->update( $contract );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$item_id_after = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$items_table} WHERE contract_id = %d", $id ) );

		$this->assertSame( $item_id_before, $item_id_after, 'An unchanged item set must keep its row id (not be rewritten).' );
	}

	/**
	 * @testdox update rewrites child rows when they change.
	 */
	public function test_update_rewrites_changed_children(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$mutated = Contract::create(
			array(
				'extension_slug'  => 'engine-tests',
				'status'          => ContractStatus::ACTIVE,
				'customer_id'     => 42,
				'currency'        => 'USD',
				'selling_plan_id' => 7,
				'origin_order_id' => 1001,
				'start_gmt'       => '2026-06-15 00:00:00',
				'items'           => array(
					array(
						'item_name'  => 'Tea tin',
						'item_type'  => 'line_item',
						'product_id' => 300,
						'quantity'   => '2',
						'subtotal'   => '24.00',
						'total'      => '24.00',
					),
				),
			)
		);
		$mutated->set_id( $id );

		$this->assertTrue( $this->sut->update( $mutated ) );

		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$items = $reloaded->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'Tea tin', $items[0]['item_name'] );
	}

	/**
	 * @testdox update throws when the contract has no id.
	 */
	public function test_update_throws_without_id(): void {
		$this->expectException( \RuntimeException::class );
		$this->sut->update( $this->make_contract() );
	}

	/**
	 * @testdox update rejects a deleted contract and writes no orphan child rows.
	 */
	public function test_update_rejects_deleted_contract_and_writes_no_orphans(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->assertTrue( $this->sut->delete( $id ) );

		$stale = $this->make_contract();
		$stale->set_id( $id );

		try {
			$this->sut->update( $stale );
			$this->fail( 'Expected RuntimeException when updating a contract whose row no longer exists.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'no longer exists', $e->getMessage() );
		}

		$items_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE contract_id = %d", $id ) );

		$this->assertSame( '0', $remaining );
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
	 * @testdox update_fields writing identical values (no changed rows) does not throw.
	 */
	public function test_update_fields_with_identical_values_does_not_throw(): void {
		global $wpdb;

		$id       = $this->sut->insert( $this->make_contract() );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		// A write in a new second changes date_updated_gmt; repeat until one changes no rows.
		$zero_rows = false;
		for ( $i = 0; $i < 3 && ! $zero_rows; $i++ ) {
			$this->assertTrue( $this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) ) );
			$zero_rows = 0 === $wpdb->rows_affected;
		}

		$this->assertTrue( $zero_rows, 'A write changed no rows.' );
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
	 * @testdox A claimed append stamps the cycle claim on the database clock; a transition without a claim releases it.
	 */
	public function test_cycle_claimed_until_round_trips(): void {
		$id = $this->sut->insert( $this->make_contract() );

		// Appended claimed: the INSERT stamps the claim from the database clock.
		$this->pin_db_clock( '2026-07-15 00:00:00' );
		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' );
		$this->sut->append_cycle( $cycle, 900 );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertSame( '2026-07-15 00:15:00', $reloaded->get_claimed_until_gmt() );

		// Cleared by a transition without a claim (a settled cycle holds no claim).
		$this->assertTrue( $this->sut->transition_cycle( (int) $reloaded->get_id(), CycleStatus::PENDING, CycleStatus::BILLED, array() ) );

		$settled = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $settled );
		$this->assertNull( $settled->get_claimed_until_gmt() );
	}

	/**
	 * @testdox claim_cycle takes an expired claim once; a second caller loses.
	 *
	 * The expiry check and the new claim use the database clock, so the claim is seeded
	 * relative to real time.
	 */
	public function test_claim_cycle_takes_an_expired_claim_once(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$cycle    = $this->append_pending_cycle_with_claim( $id, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$cycle_id = (int) $cycle->get_id();

		$this->assertTrue( $this->sut->claim_cycle( $cycle_id, CycleStatus::PENDING, 900 ), 'The first caller takes the expired claim.' );
		$this->assertFalse( $this->sut->claim_cycle( $cycle_id, CycleStatus::PENDING, 900 ), 'The second caller finds a live claim.' );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertNotNull( $reloaded->get_claimed_until_gmt() );
		$this->assertGreaterThan( time() + 800, strtotime( $reloaded->get_claimed_until_gmt() . ' UTC' ), 'The claim runs a TTL into the future.' );
	}

	/**
	 * @testdox claim_cycle takes a pending cycle that has no claim.
	 */
	public function test_claim_cycle_takes_a_unclaimed_pending_cycle(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->append_pending_cycle_with_claim( $id, null );

		$this->assertTrue( $this->sut->claim_cycle( (int) $cycle->get_id(), CycleStatus::PENDING, 900 ) );
		$this->assertFalse( $this->sut->claim_cycle( (int) $cycle->get_id(), CycleStatus::PENDING, 900 ) );
	}

	/**
	 * @testdox claim_cycle refuses a live claim and a cycle that left the status the caller read.
	 */
	public function test_claim_cycle_refuses_a_live_claim_and_a_settled_cycle(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->append_pending_cycle_with_claim( $id, gmdate( 'Y-m-d H:i:s', time() + 600 ) );

		$this->assertFalse( $this->sut->claim_cycle( (int) $cycle->get_id(), CycleStatus::PENDING, 900 ), 'A live claim is held by someone else.' );

		$this->assertTrue( $this->sut->transition_cycle( (int) $cycle->get_id(), CycleStatus::PENDING, CycleStatus::BILLED, array() ) );
		$this->assertFalse( $this->sut->claim_cycle( (int) $cycle->get_id(), CycleStatus::PENDING, 900 ), 'A cycle no longer in the read status is not claimable.' );
	}

	/**
	 * @testdox claim_cycle claims a cycle in whatever status the caller read, not only pending.
	 */
	public function test_claim_cycle_keys_on_the_callers_status(): void {
		$id       = $this->sut->insert( $this->make_contract() );
		$cycle_id = (int) $this->append_pending_cycle_with_claim( $id, null )->get_id();
		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array() ) );

		$this->assertFalse( $this->sut->claim_cycle( $cycle_id, CycleStatus::PENDING, 900 ), 'A stale read loses.' );
		$this->assertTrue( $this->sut->claim_cycle( $cycle_id, CycleStatus::FAILED, 900 ), 'The current status wins.' );
		$this->assertFalse( $this->sut->claim_cycle( $cycle_id, CycleStatus::FAILED, 900 ), 'Only once while the claim is live.' );
		$this->assertSame( CycleStatus::FAILED, $this->find_cycle( $cycle_id )->get_status()->get_value(), 'Claiming does not change the status.' );
	}

	/**
	 * Append a pending cycle 1 carrying a crash-recovery claim, returning it (with its id).
	 *
	 * @param int         $contract_id   Contract id.
	 * @param string|null $claimed_until The claim expiry GMT string to stamp, or null for none.
	 */
	private function append_pending_cycle_with_claim( int $contract_id, ?string $claimed_until ): Cycle {
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
		$this->stamp_cycle_claim( (int) $cycle->get_id(), $claimed_until );

		return $cycle;
	}

	/**
	 * Seed a cycle's stored claim expiry directly (fixtures need claims in the past).
	 *
	 * @param int         $cycle_id      Cycle id.
	 * @param string|null $claimed_until Claim expiry GMT string, or null for none.
	 */
	private function stamp_cycle_claim( int $cycle_id, ?string $claimed_until ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES ), array( 'claimed_until' => $claimed_until ), array( 'id' => $cycle_id ) );
	}

	/**
	 * @testdox A claimed append is one INSERT: the claim is stamped in the same statement, with no read or follow-up write.
	 */
	public function test_a_claimed_append_is_one_insert(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$cycles  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$queries = array();
		$capture = static function ( string $query ) use ( &$queries ): string {
			$queries[] = $query;
			return $query;
		};

		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' );
		add_filter( 'query', $capture );
		try {
			$this->sut->append_cycle( $cycle, 900 );
		} finally {
			remove_filter( 'query', $capture );
		}

		$writes = array_values(
			array_filter(
				$queries,
				static function ( string $query ) use ( $cycles ): bool {
					return false !== strpos( $query, $cycles ) || false !== stripos( $query, 'UTC_TIMESTAMP' );
				}
			)
		);
		$this->assertCount( 1, $writes, 'Only the INSERT touches the cycles table or the database clock.' );
		$this->assertStringStartsWith( "INSERT INTO `{$cycles}`", $writes[0] );
		$this->assertStringContainsString( 'DATE_ADD( UTC_TIMESTAMP(), INTERVAL 900 SECOND )', $writes[0] );
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
		$id  = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_ENGINE, CycleStatus::BILLED, null, null, 'other-ext' );

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
	 * @testdox A live dispatch claim hides the contract from find_due until the claim expires on the database clock.
	 */
	public function test_a_live_claim_hides_the_contract_until_it_expires(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$id  = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->pin_db_clock( '2026-07-15 00:00:00' );
		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );

		$this->pin_db_clock( '2026-07-15 00:14:59' );
		$this->assertSame( array(), $this->due_ids( $now, 50 ), 'A live claim hides the contract.' );
		$this->assertSame( array(), $this->due_ids( $now->modify( '+1 day' ), 50 ), 'A later scan cutoff does not expire the claim; the database clock does.' );

		$this->pin_db_clock( '2026-07-15 00:15:00' );
		$this->assertSame( array( $id ), $this->due_ids( $now, 50 ), 'An expired claim no longer hides it.' );
	}

	/**
	 * @testdox claim_contract wins once on the database clock; a second claim on the same due moment loses.
	 */
	public function test_claim_contract_wins_once(): void {
		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->pin_db_clock( '2035-01-01 00:00:00' );
		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );
		$this->assertFalse( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ), 'An overlapping dispatcher loses.' );
		$this->assertSame( '2035-01-01 00:15:00', $this->stored_claim( $id ), 'The claim is stamped from the database clock at the write.' );

		$this->pin_db_clock( '2035-01-01 00:15:00' );
		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ), 'An expired claim can be taken again.' );
	}

	/**
	 * @testdox claim_contract loses when the due moment changed since the scan read it.
	 */
	public function test_claim_contract_loses_when_the_due_moment_moved(): void {
		$id = $this->insert_contract_due_at( '2026-06-20 00:00:00', ContractStatus::ACTIVE );

		$this->assertFalse( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );
		$this->assertNull( $this->stored_claim( $id ) );
	}

	/**
	 * @testdox Moving or clearing next_payment_gmt releases the claim; re-saving it or other writes keep it.
	 */
	public function test_due_moment_writes_release_the_claim(): void {
		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );
		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$this->sut->update_fields( $contract, array( 'status' ) );
		$this->assertNotNull( $this->stored_claim( $id ), 'A write that leaves the due moment alone keeps the claim.' );

		Contracts::update( $id, array( 'next_payment_gmt' => '2026-06-15 00:00:00' ) );
		$this->assertNotNull( $this->stored_claim( $id ), 'Re-saving the same due moment keeps the claim (no queue jump).' );

		$this->sut->update( $contract );
		$this->assertNotNull( $this->stored_claim( $id ), 'A full update() with the same due moment keeps the claim.' );

		$reformatted = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reformatted );
		$reformatted->set_next_payment_gmt( '2026-06-15T00:00:00' );
		$this->sut->update_fields( $reformatted, array( 'next_payment_gmt' ) );
		$this->assertNotNull( $this->stored_claim( $id ), 'The same moment in another DATETIME literal form keeps the claim (MySQL compares DATETIME values).' );
		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( '2026-06-15 00:00:00', $stored->get_next_payment_gmt() );

		Contracts::update( $id, array( 'next_payment_gmt' => '2026-06-16 00:00:00' ) );
		$this->assertNull( $this->stored_claim( $id ), 'Moving the due moment releases the claim.' );

		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-16 00:00:00', 900 ) );
		Contracts::update( $id, array( 'next_payment_gmt' => null ) );
		$this->assertNull( $this->stored_claim( $id ), 'Clearing the due moment releases the claim.' );

		Contracts::update( $id, array( 'next_payment_gmt' => '2026-06-15 00:00:00' ) );
		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );
		$moved = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $moved );
		$moved->set_next_payment_gmt( '2026-07-01 00:00:00' );
		$this->sut->update( $moved );
		$this->assertNull( $this->stored_claim( $id ), 'A full update() that moves the due moment releases the claim.' );
		$stored = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $stored );
		$this->assertSame( '2026-07-01 00:00:00', $stored->get_next_payment_gmt() );
	}

	/**
	 * @testdox update_fields and update each write the contract row in exactly one UPDATE statement.
	 */
	public function test_a_contract_write_is_one_update_statement(): void {
		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		foreach ( array( 'update_fields', 'update' ) as $write ) {
			$contract = $this->sut->find( $id );
			$this->assertInstanceOf( Contract::class, $contract );
			$on_hold = ContractStatus::ON_HOLD === $contract->get_status();
			$contract->set_status( $on_hold ? ContractStatus::ACTIVE : ContractStatus::ON_HOLD );
			$contract->set_next_payment_gmt( $on_hold ? '2026-06-15 00:00:00' : null );

			$statements = $this->contract_updates_during(
				function () use ( $write, $contract ): void {
					if ( 'update_fields' === $write ) {
						$this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) );
					} else {
						$this->sut->update( $contract );
					}
				}
			);

			$this->assertCount( 1, $statements, "{$write}() writes status and due moment in one UPDATE." );
			$stored = $this->sut->find( $id );
			$this->assertInstanceOf( Contract::class, $stored );
			$this->assertSame( $contract->get_status(), $stored->get_status() );
			$this->assertSame( $contract->get_next_payment_gmt(), $stored->get_next_payment_gmt() );
		}
	}

	/**
	 * @testdox When the contract write is lost, neither the status nor the due moment nor the claim changes.
	 */
	public function test_a_lost_contract_write_changes_neither_status_nor_due_moment(): void {
		$id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$this->assertTrue( $this->sut->claim_contract( $id, '2026-06-15 00:00:00', 900 ) );

		$contract = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );
		$contract->set_status( ContractStatus::ON_HOLD );
		$contract->set_next_payment_gmt( null );

		$drop = function ( string $query ): string {
			return $this->is_contract_update( $query ) ? '' : $query;
		};

		add_filter( 'query', $drop );
		try {
			$this->sut->update_fields( $contract, array( 'status', 'next_payment_gmt' ) );
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
		$this->assertNotNull( $this->stored_claim( $id ) );
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
	 * @testdox An over-long payment method or title is refused by insert, update_fields and update alike, and nothing is stored.
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

		$id = $this->sut->insert( $this->make_contract() );
		foreach ( array( 'update_fields', 'update' ) as $write ) {
			$contract = $this->sut->find( $id );
			$this->assertInstanceOf( Contract::class, $contract );
			$contract->set_payment_instrument( $over_long );
			$contract->set_status( ContractStatus::ON_HOLD );

			try {
				if ( 'update_fields' === $write ) {
					$this->sut->update_fields( $contract, array( 'status', 'payment_method', 'payment_method_title' ) );
				} else {
					$this->sut->update( $contract );
				}
				$this->fail( "Expected {$write}() to refuse the over-long value." );
			} catch ( \RuntimeException $e ) {
				$this->assertStringContainsString( 'longer than the column allows', $e->getMessage() );
			}

			$stored = $this->sut->find( $id );
			$this->assertInstanceOf( Contract::class, $stored );
			$this->assertSame( 'woocommerce_payments', $stored->get_payment_instrument()->get_gateway(), "{$write}() wrote nothing." );
			$this->assertSame( 'Credit card', $stored->get_payment_instrument()->get_title() );
			$this->assertSame( ContractStatus::ACTIVE, $stored->get_status() );
		}

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
	 * The stored due-dispatch claim of a contract.
	 *
	 * @param int $id Contract id.
	 */
	private function stored_claim( int $id ): ?string {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$claim = $wpdb->get_var( $wpdb->prepare( "SELECT claimed_until FROM {$table} WHERE id = %d", $id ) );

		return null === $claim ? null : (string) $claim;
	}

	/**
	 * @testdox transition_cycle is a compare-and-set: one caller wins, a caller that read the old status loses.
	 */
	public function test_transition_cycle_is_an_atomic_compare_and_set(): void {
		$cycle_id = $this->claimd_pending_cycle_id();

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, array( 'order_id' => 4242 ) ) );

		$settled = $this->find_cycle( $cycle_id );
		$this->assertSame( CycleStatus::BILLED, $settled->get_status()->get_value() );
		$this->assertSame( 4242, $settled->get_order_id() );
		$this->assertNull( $settled->get_claimed_until_gmt(), 'Without claim_seconds the claim is cleared.' );

		$this->assertFalse( $this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array( 'order_id' => 9999 ) ) );

		$after = $this->find_cycle( $cycle_id );
		$this->assertSame( CycleStatus::BILLED, $after->get_status()->get_value(), 'The losing transition writes nothing.' );
		$this->assertSame( 4242, $after->get_order_id() );
	}

	/**
	 * @testdox A same-status transition_cycle that changes nothing still returns true; on a cycle in another status it returns false.
	 */
	public function test_a_same_status_transition_that_changes_nothing_returns_true(): void {
		$cycle_id = $this->claimd_pending_cycle_id();

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
		$cycle_id = $this->claimd_pending_cycle_id();
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
	 * @testdox transition_cycle sets a claim from claim_seconds, e.g. retrying a failed cycle.
	 */
	public function test_transition_cycle_sets_a_claim(): void {
		$cycle_id = $this->claimd_pending_cycle_id();
		$this->sut->transition_cycle( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, array() );

		$this->assertTrue( $this->sut->transition_cycle( $cycle_id, CycleStatus::FAILED, CycleStatus::PENDING, array( 'claim_seconds' => 900 ) ) );

		$retried = $this->find_cycle( $cycle_id );
		$this->assertSame( CycleStatus::PENDING, $retried->get_status()->get_value() );
		$this->assertNotNull( $retried->get_claimed_until_gmt() );
		$this->assertGreaterThan( time() + 800, strtotime( $retried->get_claimed_until_gmt() . ' UTC' ) );
		$this->assertFalse( $this->sut->claim_cycle( $cycle_id, CycleStatus::PENDING, 900 ), 'The retrier holds the claim.' );
	}

	/**
	 * @testdox transition_cycle rejects an unregistered target status and writes nothing.
	 */
	public function test_transition_cycle_rejects_an_unregistered_target(): void {
		$cycle_id = $this->claimd_pending_cycle_id();

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
		$cycle_id = $this->claimd_pending_cycle_id();

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
	 * A pending cycle 1 with an expired claim on a new contract.
	 */
	private function claimd_pending_cycle_id(): int {
		$id = $this->sut->insert( $this->make_contract() );

		return (int) $this->append_pending_cycle_with_claim( $id, gmdate( 'Y-m-d H:i:s', time() - 60 ) )->get_id();
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
		$this->assertTrue( $this->sut->update( $contract ) );

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
	 * @param string|null $claimed_until    The head cycle claim expiry, or null for none.
	 * @param string|null $head_ends_at     The head period end; defaults to `$next_payment_gmt`.
	 * @param string      $owner            The owning extension slug; defaults to the registered test owner.
	 */
	private function insert_contract_due_at(
		?string $next_payment_gmt,
		string $status,
		string $schedule_source = Contract::SCHEDULE_SOURCE_ENGINE,
		string $head_status = CycleStatus::BILLED,
		?string $claimed_until = null,
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
			if ( null !== $claimed_until ) {
				$head = $this->sut->find_chain_head( $id );
				$this->assertInstanceOf( Cycle::class, $head );
				$this->stamp_cycle_claim( (int) $head->get_id(), $claimed_until );
			}
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
	 * The create-as-claim race signal must be distinguishable from any other write failure,
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
}

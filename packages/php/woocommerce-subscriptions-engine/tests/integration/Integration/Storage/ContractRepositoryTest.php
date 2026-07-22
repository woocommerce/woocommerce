<?php
/**
 * Integration tests for the lean ContractRepository and its targeted cycle access.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\RenewalCandidate;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

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

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
	}

	private function make_contract(): Contract {
		return Contract::create(
			array(
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
				'meta'                 => array(
					'source_channel' => 'pdp',
				),
			)
		);
	}

	/**
	 * Build a billing cycle for the given contract at a sequence/count.
	 *
	 * @param int                $contract_id Contract id.
	 * @param int                $sequence_no Position in the chain.
	 * @param int|null           $count       Chargeable count, or null for non-counting.
	 * @param string             $starts_at   Period start GMT string.
	 * @param string             $ends_at     Period end GMT string.
	 * @param PlanSnapshot|null  $plan        Plan snapshot, or null.
	 * @param ItemsSnapshot|null $items       Items snapshot, or null.
	 * @param int|null           $order_id    Linked order id, or null.
	 */
	private function make_cycle( int $contract_id, int $sequence_no, ?int $count, string $starts_at, string $ends_at, ?PlanSnapshot $plan = null, ?ItemsSnapshot $items = null, ?int $order_id = null ): Cycle {
		return Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => $sequence_no,
				'count'          => $count,
				'starts_at_gmt'  => $starts_at,
				'ends_at_gmt'    => $ends_at,
				'expected_total' => '19.99',
				'currency'       => 'USD',
				'extension_slug' => 'lite',
				'order_id'       => $order_id,
				'plan_snapshot'  => $plan,
				'items_snapshot' => $items,
			)
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

		$this->assertSame( 'pdp', $fetched->get_meta()['source_channel'] );
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
		$this->assertSame( array(), $summary->get_meta() );
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

		// Every known status is a key, in ContractStatus::all() order, with absent ones 0.
		$this->assertSame( ContractStatus::all(), array_keys( $counts ) );
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

		$this->assertSame( ContractStatus::all(), array_keys( $counts ) );
		$this->assertSame( array( 0, 0, 0, 0, 0 ), array_values( $counts ) );
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
	 * @testdox insert_with_origin_cycle records cycle 1's snapshot refs on the contract too.
	 */
	public function test_insert_with_origin_cycle_records_refs_on_the_contract(): void {
		$contract = $this->make_contract();
		$cycle    = $this->make_cycle( 0, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot(), 1001 );
		$cycle->set_status( CycleStatus::billed() );

		$id = $this->sut->insert_with_origin_cycle( $contract, $cycle );
		$this->assertGreaterThan( 0, $id );

		// The signup cycle was stamped with the contract id and its snapshots resolved.
		$this->assertSame( $id, $cycle->get_contract_id() );
		$this->assertNotNull( $cycle->get_plan_snapshot_id() );
		$this->assertNotNull( $cycle->get_items_snapshot_id() );

		// The contract carries the SAME snapshot refs as cycle 1 (latest/live).
		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( $cycle->get_plan_snapshot_id(), $reloaded->get_plan_snapshot_id() );
		$this->assertSame( $cycle->get_items_snapshot_id(), $reloaded->get_items_snapshot_id() );

		// Cycle 1 is the billed signup, reachable as the chain's most-recent cycle.
		$current = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $current );
		$this->assertSame( 1, $current->get_count() );
		$this->assertTrue( $current->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox extension_slug defaults to null when unset.
	 */
	public function test_extension_slug_defaults_to_null_when_unset(): void {
		$id = $this->sut->insert(
			Contract::create(
				array(
					'customer_id'     => 1,
					'currency'        => 'EUR',
					'selling_plan_id' => 2,
					'origin_order_id' => 3,
					'start_gmt'       => '2026-06-15 00:00:00',
				)
			)
		);

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
				'meta'            => array( 'source_channel' => 'email' ),
			)
		);
		$mutated->set_id( $id );

		$this->assertTrue( $this->sut->update( $mutated ) );

		$reloaded = $this->sut->find( $id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$items = $reloaded->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'Tea tin', $items[0]['item_name'] );
		$this->assertSame( 'email', $reloaded->get_meta()['source_channel'] );
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
	 * @testdox append_cycle inserts a cycle reachable as the chain's current cycle.
	 */
	public function test_append_cycle_and_find_chain_head(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot() );
		$this->sut->append_cycle( $cycle );

		$this->assertNotNull( $cycle->get_id() );

		$current = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $current );
		$this->assertSame( $cycle->get_id(), $current->get_id() );
		$this->assertSame( 1, $current->get_sequence_no() );
		$this->assertSame( 1, $current->get_count() );
		$this->assertTrue( $current->get_status()->equals( CycleStatus::pending() ) );
		$this->assertSame( '2026-07-15 00:00:00', $current->get_starts_at_gmt() );
		$this->assertSame( '19.99000000', $current->get_expected_total() );
		$this->assertSame( 'lite', $current->get_extension_slug() );

		// Snapshots decoded back into typed value objects on an in-flight cycle.
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
		$this->sut->append_cycle( $second, $first );

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

		$prev = null;
		for ( $n = 1; $n <= 3; $n++ ) {
			$cycle = $this->make_cycle( $id, $n, $n, sprintf( '2026-%02d-15 00:00:00', 6 + $n ), sprintf( '2026-%02d-15 00:00:00', 7 + $n ) );
			$this->sut->append_cycle( $cycle, $prev );
			$prev = $cycle;
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
	 * @testdox max_count tracks the highest count appended (the MAX(count) + 1 anchor).
	 */
	public function test_max_count_reads_the_per_chain_counter(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$this->assertNull( $this->sut->max_count( $id ), 'An empty chain has no counting cycle.' );

		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' ) );
		$this->assertSame( 1, $this->sut->max_count( $id ) );

		$this->sut->append_cycle( $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00' ) );
		$this->assertSame( 2, $this->sut->max_count( $id ) );

		// The next chargeable number is derived as MAX(count) + 1; appending it must
		// advance the counter, confirming the derivation is wired through the writes.
		$next = (int) $this->sut->max_count( $id ) + 1;
		$this->sut->append_cycle( $this->make_cycle( $id, 3, $next, '2026-09-15 00:00:00', '2026-10-15 00:00:00' ) );
		$this->assertSame( 3, $this->sut->max_count( $id ) );
	}

	/**
	 * @testdox find_cycles_by_order_id returns every cycle linked to an order.
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
		sort( $contract_ids );
		$this->assertSame( array( $first_id, $second_id ), $contract_ids );
	}

	/**
	 * @testdox update_cycle persists a status transition on a stored cycle.
	 */
	public function test_update_cycle_persists_a_status_change(): void {
		$id = $this->sut->insert( $this->make_contract() );

		$cycle = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00' );
		$this->sut->append_cycle( $cycle );

		$cycle->set_status( CycleStatus::billed() );
		$this->sut->update_cycle( $cycle );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertTrue( $reloaded->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox The cycle crash-recovery lease column round-trips through append and update.
	 */
	public function test_cycle_claimed_until_round_trips(): void {
		$id = $this->sut->insert( $this->make_contract() );

		// Appended with a lease set.
		$cycle = Cycle::create(
			array(
				'contract_id'    => $id,
				'sequence_no'    => 1,
				'count'          => 1,
				'status'         => CycleStatus::pending(),
				'starts_at_gmt'  => '2026-07-15 00:00:00',
				'ends_at_gmt'    => '2026-08-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
				'claimed_until'  => '2026-07-15 00:15:00',
			)
		);
		$this->sut->append_cycle( $cycle );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertSame( '2026-07-15 00:15:00', $reloaded->get_claimed_until_gmt() );

		// Cleared on update (a settled cycle holds no lease).
		$reloaded->set_status( CycleStatus::billed() );
		$reloaded->set_claimed_until_gmt( null );
		$this->sut->update_cycle( $reloaded );

		$settled = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $settled );
		$this->assertNull( $settled->get_claimed_until_gmt() );
	}

	/**
	 * @testdox reclaim_expired_cycle wins the CAS for an expired-lease pending cycle and extends the lease.
	 *
	 * The expiry predicate and the fresh lease both anchor on the database clock, so the
	 * test seeds the lease relative to real time.
	 */
	public function test_reclaim_expired_cycle_succeeds_for_an_expired_lease(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->append_pending_cycle_with_lease( $id, gmdate( 'Y-m-d H:i:s', time() - 60 ) );

		// The lease expired a minute ago per the DB clock: the CAS matches.
		$won = $this->sut->reclaim_expired_cycle( (int) $cycle->get_id(), 900 );
		$this->assertTrue( $won, 'The first worker reclaims an expired-lease pending cycle.' );

		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertNotNull( $reloaded->get_claimed_until_gmt() );
		$this->assertGreaterThan( time() + 800, strtotime( $reloaded->get_claimed_until_gmt() . ' UTC' ), 'The lease is extended a TTL into the future.' );
	}

	/**
	 * @testdox reclaim_expired_cycle arbitrates the two-worker race: the first wins, the second loses.
	 *
	 * The compare-and-set that prevents a double charge. Both workers find the same
	 * expired-lease cycle; the first CAS matches the row and extends the lease into the
	 * future, so the second CAS (predicate `claimed_until <= now`) matches zero rows and
	 * loses. Exactly one worker reclaims, so the cycle is charged at most once.
	 */
	public function test_reclaim_expired_cycle_arbitrates_the_race(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->append_pending_cycle_with_lease( $id, gmdate( 'Y-m-d H:i:s', time() - 60 ) );

		$cycle_id = (int) $cycle->get_id();

		// Worker A: the lease has expired per the DB clock, so the CAS wins and extends it.
		$first = $this->sut->reclaim_expired_cycle( $cycle_id, 900 );
		$this->assertTrue( $first, 'The first worker wins the reclaim.' );

		// Worker B: same read, but the lease now sits a TTL in the future - zero rows match.
		$second = $this->sut->reclaim_expired_cycle( $cycle_id, 900 );
		$this->assertFalse( $second, 'The second worker loses the race: no double reclaim.' );

		// The lease reflects the winner's extension.
		$reloaded = $this->sut->find_chain_head( $id );
		$this->assertInstanceOf( Cycle::class, $reloaded );
		$this->assertNotNull( $reloaded->get_claimed_until_gmt() );
		$this->assertGreaterThan( time() + 800, strtotime( $reloaded->get_claimed_until_gmt() . ' UTC' ) );
	}

	/**
	 * @testdox reclaim_expired_cycle does not touch a settled (non-pending) cycle.
	 */
	public function test_reclaim_expired_cycle_skips_a_settled_cycle(): void {
		$id    = $this->sut->insert( $this->make_contract() );
		$cycle = $this->append_pending_cycle_with_lease( $id, gmdate( 'Y-m-d H:i:s', time() - 60 ) );

		// Settle it billed (clearing the lease, as the money-path does).
		$cycle->set_status( CycleStatus::billed() );
		$cycle->set_claimed_until_gmt( null );
		$this->sut->update_cycle( $cycle );

		$won = $this->sut->reclaim_expired_cycle( (int) $cycle->get_id(), 900 );
		$this->assertFalse( $won, 'A billed cycle is never reclaimable: the status predicate excludes it.' );
	}

	/**
	 * Append a pending cycle 1 carrying a crash-recovery lease, returning it (with its id).
	 *
	 * @param int    $contract_id   Contract id.
	 * @param string $claimed_until The lease expiry GMT string to stamp.
	 */
	private function append_pending_cycle_with_lease( int $contract_id, string $claimed_until ): Cycle {
		$cycle = Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => 1,
				'count'          => 1,
				'status'         => CycleStatus::pending(),
				'starts_at_gmt'  => '2026-07-15 00:00:00',
				'ends_at_gmt'    => '2026-08-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
				'claimed_until'  => $claimed_until,
			)
		);
		$this->sut->append_cycle( $cycle );

		return $cycle;
	}

	/**
	 * @testdox find_due returns only active contracts whose next_payment has arrived, oldest first.
	 */
	public function test_find_due_returns_only_due_active_contracts_oldest_first(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$due_old    = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$due_recent = $this->insert_contract_due_at( '2026-07-14 00:00:00', ContractStatus::ACTIVE );
		$not_yet    = $this->insert_contract_due_at( '2026-08-15 00:00:00', ContractStatus::ACTIVE );
		$on_hold    = $this->insert_contract_due_at( '2026-06-01 00:00:00', ContractStatus::ON_HOLD );

		$ids = $this->due_ids( $now, 50 );

		// Only the two due+active contracts, oldest-due first; the future and the non-active excluded.
		$this->assertSame( array( $due_old, $due_recent ), $ids );
		$this->assertNotContains( $not_yet, $ids );
		$this->assertNotContains( $on_hold, $ids );
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

		// The limit caps the batch from the oldest-due end; the newest-due row is left for a later tick.
		$this->assertSame( array( $first, $second ), $this->due_ids( $now, 2 ) );
	}

	/**
	 * @testdox find_due excludes gateway-scheduled contracts (the gateway owns their renewal).
	 */
	public function test_find_due_excludes_gateway_scheduled_contracts(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$primitive = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$gateway   = $this->insert_contract_due_at( '2026-06-01 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_GATEWAY );

		$ids = $this->due_ids( $now, 50 );

		$this->assertContains( $primitive, $ids );
		$this->assertNotContains( $gateway, $ids, 'A gateway-scheduled contract must never enter the primitive scan.' );
	}

	/**
	 * @testdox find_due returns the head fields the selector needs.
	 */
	public function test_find_due_returns_the_head_fields(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$id         = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );
		$candidates = $this->sut->find_due( $now, 50 );

		$this->assertCount( 1, $candidates );
		$row = $candidates[0];
		$this->assertInstanceOf( RenewalCandidate::class, $row );
		$this->assertSame( $id, $row->get_contract_id() );
		$this->assertSame( 1, $row->get_head_count() );
		$this->assertSame( CycleStatus::BILLED, $row->get_head_status() );
		$this->assertSame( '2026-06-15 00:00:00', $row->get_head_ends_at_gmt() );
	}

	/**
	 * @testdox find_due excludes a contract whose head is not actionable (failed / processing).
	 */
	public function test_find_due_excludes_non_actionable_heads(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$billed     = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::BILLED );
		$failed     = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::FAILED );
		$processing = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::PROCESSING );

		$ids = $this->due_ids( $now, 50 );

		$this->assertContains( $billed, $ids );
		$this->assertNotContains( $failed, $ids, 'A failed head awaits dunning, not the scan.' );
		$this->assertNotContains( $processing, $ids, 'A processing head awaits its gateway, not the scan.' );
	}

	/**
	 * @testdox find_due excludes a billed head whose period has not ended (the advance due-guard).
	 */
	public function test_find_due_excludes_a_billed_head_not_yet_ended(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		// next_payment has arrived (the coarse index would pick it), but the head's own period
		// runs into the future - so the successor is not yet due and the scan must exclude it.
		$not_ended = $this->insert_contract_due_at( '2026-07-01 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::BILLED, null, '2026-08-15 00:00:00' );

		$this->assertNotContains( $not_ended, $this->due_ids( $now, 50 ) );
	}

	/**
	 * @testdox find_due includes a reclaim-ready pending head but not a live-lease one.
	 */
	public function test_find_due_includes_reclaim_ready_pending_but_not_live_lease(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );

		$reclaimable = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::PENDING, '2026-07-14 00:00:00' );
		$live_lease  = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::PENDING, '2026-08-15 00:00:00' );

		$ids = $this->due_ids( $now, 50 );

		$this->assertContains( $reclaimable, $ids, 'A pending head whose lease has expired is reclaim-ready.' );
		$this->assertNotContains( $live_lease, $ids, 'A pending head with a live lease is a concurrent claim, not for the scan.' );
	}

	/**
	 * @testdox find_due returns no rows for a non-positive limit.
	 */
	public function test_find_due_returns_no_rows_for_a_non_positive_limit(): void {
		$now = new \DateTimeImmutable( '2026-07-15 00:00:00', new \DateTimeZone( 'UTC' ) );
		$this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE );

		$this->assertSame( array(), $this->sut->find_due( $now, 0 ) );
		$this->assertSame( array(), $this->sut->find_due( $now, -1 ) );
	}

	/**
	 * @testdox transition_cycle_status settles atomically: one caller wins, repeats lose.
	 */
	public function test_transition_cycle_status_is_an_atomic_compare_and_set(): void {
		$contract_id = $this->insert_contract_due_at( '2026-06-15 00:00:00', ContractStatus::ACTIVE, Contract::SCHEDULE_SOURCE_PRIMITIVE, CycleStatus::PENDING, '2026-07-14 00:00:00' );

		$head = $this->sut->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$cycle_id = (int) $head->get_id();

		// The winning settle: status flips, order stamped, lease cleared, reason NULL - one write.
		$this->assertTrue( $this->sut->transition_cycle_status( $cycle_id, CycleStatus::PENDING, CycleStatus::BILLED, 4242 ) );

		$settled = $this->sut->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $settled );
		$this->assertSame( CycleStatus::BILLED, $settled->get_status()->get_value() );
		$this->assertSame( 4242, $settled->get_order_id() );
		$this->assertNull( $settled->get_claimed_until_gmt() );
		$this->assertNull( $settled->get_reason() );

		// A racing settler that read the old status matches zero rows and loses.
		$this->assertFalse( $this->sut->transition_cycle_status( $cycle_id, CycleStatus::PENDING, CycleStatus::FAILED, 9999, 'gateway-charge-failed' ) );

		$after = $this->sut->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $after );
		$this->assertSame( CycleStatus::BILLED, $after->get_status()->get_value(), 'The losing transition writes nothing.' );
		$this->assertSame( 4242, $after->get_order_id() );
	}

	/**
	 * The contract ids of the due scan at `$now`, in scan order.
	 *
	 * @param \DateTimeImmutable $now   The cutoff moment.
	 * @param int                $limit The batch size.
	 * @return array<int, int>
	 */
	private function due_ids( \DateTimeImmutable $now, int $limit ): array {
		return array_map(
			static function ( RenewalCandidate $candidate ): int {
				return $candidate->get_contract_id();
			},
			$this->sut->find_due( $now, $limit )
		);
	}

	/**
	 * Insert a contract with the given schedule date and status, plus a head cycle so the
	 * cycle-aware scan can join it. The head is billed and ends when the next payment is due
	 * (advance-ready) unless overridden. Returns the contract id.
	 *
	 * @param string|null $next_payment_gmt The next-payment date, or null for no schedule/head.
	 * @param string      $status           The contract status (a ContractStatus value).
	 * @param string      $schedule_source  Who owns the schedule (primitive or gateway).
	 * @param string      $head_status      The head cycle status (a CycleStatus value).
	 * @param string|null $claimed_until    The head cycle lease expiry, or null for none.
	 * @param string|null $head_ends_at     The head period end; defaults to `$next_payment_gmt`.
	 */
	private function insert_contract_due_at(
		?string $next_payment_gmt,
		string $status,
		string $schedule_source = Contract::SCHEDULE_SOURCE_PRIMITIVE,
		string $head_status = CycleStatus::BILLED,
		?string $claimed_until = null,
		?string $head_ends_at = null
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
						'status'         => CycleStatus::from( $head_status ),
						'starts_at_gmt'  => '2026-01-15 00:00:00',
						'ends_at_gmt'    => $head_ends_at ?? $next_payment_gmt,
						'expected_total' => '19.99',
						'currency'       => 'USD',
						'claimed_until'  => $claimed_until,
					)
				)
			);
		}

		return $id;
	}

	/**
	 * @testdox Consecutive cycles with an unchanged plan/items share one snapshot row each.
	 */
	public function test_copy_forward_reuses_unchanged_snapshots(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );

		$first = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot() );
		$this->sut->append_cycle( $first );

		// The next cycle's plan/items are unchanged: copy-forward should reuse the ids.
		$second = $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot() );
		$this->sut->append_cycle( $second, $first );

		$this->assertSame( $first->get_plan_snapshot_id(), $second->get_plan_snapshot_id() );
		$this->assertSame( $first->get_items_snapshot_id(), $second->get_items_snapshot_id() );

		// Exactly two snapshot rows: one plan payload, one items payload.
		$snapshots = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$snapshots} WHERE contract_id = %d", $id ) );
		$this->assertSame( 2, $row_count );
	}

	/**
	 * @testdox A changed plan snapshot inserts a new row instead of copy-forwarding.
	 */
	public function test_copy_forward_inserts_a_new_row_when_the_plan_changes(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );

		$first = $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot() );
		$this->sut->append_cycle( $first );

		// The plan terms changed, so the plan snapshot must not be reused.
		$changed_plan = PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 7,
				'cadence'         => 'weekly',
			)
		);
		$second       = $this->make_cycle( $id, 2, 2, '2026-08-15 00:00:00', '2026-09-15 00:00:00', $changed_plan, $this->sample_items_snapshot() );
		$this->sut->append_cycle( $second, $first );

		$this->assertNotSame( $first->get_plan_snapshot_id(), $second->get_plan_snapshot_id() );
		// The items were unchanged, so that row is still shared.
		$this->assertSame( $first->get_items_snapshot_id(), $second->get_items_snapshot_id() );

		$snapshots = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$snapshots} WHERE contract_id = %d", $id ) );
		$this->assertSame( 3, $row_count, 'Two plan payloads plus one shared items payload.' );
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

		// No counting cycle, so the per-chain counter is null.
		$this->assertNull( $this->sut->max_count( $id ) );
	}

	/**
	 * @testdox delete removes the contract, its children, cycles, and snapshots.
	 */
	public function test_delete_removes_contract_children_cycles_and_snapshots(): void {
		global $wpdb;

		$id = $this->sut->insert( $this->make_contract() );
		$this->sut->append_cycle( $this->make_cycle( $id, 1, 1, '2026-07-15 00:00:00', '2026-08-15 00:00:00', $this->sample_plan_snapshot(), $this->sample_items_snapshot() ) );

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

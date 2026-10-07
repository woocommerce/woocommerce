<?php
/**
 * Integration tests for PlanRepository.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository
 */
class PlanRepositoryTest extends EngineIntegrationTestCase {

	/**
	 * Insert a plan with a monthly billing payload.
	 *
	 * @param PlanRepository       $repo           Repository.
	 * @param string               $name           Plan name.
	 * @param string|null          $extension_slug Owner slug.
	 * @param array<string, mixed> $args           Extra Plan::create() args.
	 */
	private function insert_plan( PlanRepository $repo, string $name, ?string $extension_slug = 'lite', array $args = array() ): int {
		return $repo->insert(
			Plan::create(
				array_merge(
					array(
						'name'           => $name,
						'billing_policy' => array(
							'period'   => 'month',
							'interval' => 1,
						),
						'extension_slug' => $extension_slug,
					),
					$args
				)
			)
		);
	}

	/**
	 * Ids of a plan list.
	 *
	 * @param array<int, Plan> $plans Plans.
	 * @return array<int, int|null>
	 */
	private static function ids( array $plans ): array {
		return array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans );
	}

	public function test_plan_round_trips_all_three_opaque_policies(): void {
		$repo     = new PlanRepository();
		$billing  = array(
			'period'         => 'fortnight',
			'interval'       => 1,
			'trial_duration' => array( 'unit' => 'day' ),
		);
		$pricing  = array(
			'policies'   => array(
				array(
					'type'  => 'percentage',
					'value' => 10,
				),
			),
			'custom_key' => array( 'nested' => '1.50' ),
		);
		$delivery = array(
			'anchor' => array( 'day' => 3 ),
			'note'   => 'opaque',
		);

		$plan = Plan::create(
			array(
				'name'            => 'Monthly',
				'billing_policy'  => $billing,
				'pricing_policy'  => $pricing,
				'delivery_policy' => $delivery,
				'status'          => PlanStatus::ARCHIVED,
				'extension_slug'  => 'lite',
			)
		);

		$id = $repo->insert( $plan );
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $id, $plan->get_id() );

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertSame( 'Monthly', $fetched->get_name() );
		$this->assertSame( 'lite', $fetched->get_extension_slug() );
		$this->assertSame( PlanStatus::ARCHIVED, $fetched->get_status() );
		$this->assertSame( $billing, $fetched->get_billing_policy() );
		$this->assertSame( $pricing, $fetched->get_pricing_policy() );
		$this->assertSame( $delivery, $fetched->get_delivery_policy() );
		$this->assertNotNull( $fetched->get_date_created_gmt() );
		$this->assertNotNull( $fetched->get_date_updated_gmt() );
	}

	public function test_plan_without_policies_round_trips_with_null_billing(): void {
		$repo = new PlanRepository();

		$plan = Plan::create(
			array(
				'name'           => 'Bare',
				'extension_slug' => 'lite',
			)
		);
		$id   = $repo->insert( $plan );

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertNull( $fetched->get_billing_policy() );
		$this->assertNull( $fetched->get_pricing_policy() );
		$this->assertNull( $fetched->get_delivery_policy() );
	}

	public function test_update_fields_persists_name_status_and_policies_and_bumps_only_the_updated_date(): void {
		global $wpdb;

		$repo = new PlanRepository();
		$id   = $this->insert_plan( $repo, 'Before' );

		$table = $wpdb->prefix . 'wc_selling_plans';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET date_created_gmt = %s, date_updated_gmt = %s WHERE id = %d", '2020-01-01 00:00:00', '2020-01-01 00:00:00', $id ) );

		$plan = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $plan );

		$plan->set_name( 'After' );
		$plan->set_status( PlanStatus::ARCHIVED );
		$plan->set_billing_policy( array( 'period' => 'week' ) );
		$plan->set_pricing_policy( array( 'policies' => array() ) );
		$plan->set_delivery_policy( array( 'x' => 1 ) );
		$this->assertTrue( $repo->update_fields( $plan, array( 'name', 'status', 'billing_policy', 'pricing_policy', 'delivery_policy' ) ) );
		$this->assertNotSame( '2020-01-01 00:00:00', $plan->get_date_updated_gmt(), 'The update time is stamped back onto the entity.' );

		$updated = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $updated );
		$this->assertSame( 'After', $updated->get_name() );
		$this->assertSame( PlanStatus::ARCHIVED, $updated->get_status() );
		$this->assertSame( array( 'period' => 'week' ), $updated->get_billing_policy() );
		$this->assertSame( array( 'policies' => array() ), $updated->get_pricing_policy() );
		$this->assertSame( array( 'x' => 1 ), $updated->get_delivery_policy() );
		$this->assertSame( '2020-01-01 00:00:00', $updated->get_date_created_gmt() );
		$this->assertNotSame( '2020-01-01 00:00:00', $updated->get_date_updated_gmt() );

		$updated->set_billing_policy( null );
		$repo->update_fields( $updated, array( 'billing_policy' ) );
		$cleared = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $cleared );
		$this->assertNull( $cleared->get_billing_policy() );
	}

	/**
	 * @testdox update_fields on a deleted plan returns false.
	 */
	public function test_update_fields_returns_false_for_a_deleted_plan(): void {
		$repo  = new PlanRepository();
		$stale = $repo->find( $this->insert_plan( $repo, 'Gone' ) );
		$this->assertInstanceOf( Plan::class, $stale );
		$this->assertTrue( $repo->delete( (int) $stale->get_id() ) );

		$stale->set_name( 'Renamed' );

		$this->assertFalse( $repo->update_fields( $stale, array( 'name' ) ) );
	}

	/**
	 * @testdox update_fields writing identical values (no changed rows) returns true.
	 */
	public function test_update_fields_with_identical_values_returns_true(): void {
		$repo = new PlanRepository();
		$plan = $repo->find( $this->insert_plan( $repo, 'Same' ) );
		$this->assertInstanceOf( Plan::class, $plan );

		// The first write may bump the update time; the second, within the same second, changes no row.
		$this->assertTrue( $repo->update_fields( $plan, array( 'name' ) ) );
		$this->assertTrue( $repo->update_fields( $plan, array( 'name' ) ) );
	}

	public function test_update_fields_without_an_id_throws(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Unsaved',
				'extension_slug' => 'lite',
			)
		);

		$this->expectException( \RuntimeException::class );

		( new PlanRepository() )->update_fields( $plan, array( 'name' ) );
	}

	public function test_update_fields_writes_only_the_named_columns(): void {
		$repo = new PlanRepository();
		$id   = $this->insert_plan( $repo, 'Original' );

		$stale = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $stale );

		$other = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $other );
		$other->set_name( 'Renamed elsewhere' );
		$repo->update_fields( $other, array( 'name' ) );

		$stale->set_status( PlanStatus::ARCHIVED );
		$repo->update_fields( $stale, array( 'status' ) );

		$stored = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $stored );
		$this->assertSame( 'Renamed elsewhere', $stored->get_name() );
		$this->assertSame( PlanStatus::ARCHIVED, $stored->get_status() );
	}

	/**
	 * @testWith ["extension_slug"]
	 *           ["sort_order"]
	 *
	 * @param string $field Field that is not a writable column.
	 */
	public function test_update_fields_refuses_a_field_that_is_not_writable( string $field ): void {
		$repo = new PlanRepository();
		$plan = $repo->find( $this->insert_plan( $repo, 'Guarded' ) );
		$this->assertInstanceOf( Plan::class, $plan );

		$this->expectException( \InvalidArgumentException::class );

		$repo->update_fields( $plan, array( $field ) );
	}

	public function test_query_and_count_filter_by_status_and_search(): void {
		$repo = new PlanRepository();

		$this->insert_plan( $repo, 'Alpha monthly' );
		$second_id = $this->insert_plan( $repo, 'Beta weekly' );
		$this->insert_plan( $repo, 'Archived yearly', 'lite', array( 'status' => PlanStatus::ARCHIVED ) );

		$active = $repo->query(
			array(
				'status' => PlanStatus::ACTIVE,
				'search' => 'weekly',
			)
		);

		$this->assertSame( array( $second_id ), self::ids( $active ) );
		$this->assertSame( 2, $repo->count( array( 'status' => PlanStatus::ACTIVE ) ) );
		$this->assertSame( 1, $repo->count( array( 'status' => PlanStatus::ARCHIVED ) ) );
	}

	public function test_query_status_accepts_a_list(): void {
		$repo = new PlanRepository();

		$active_id   = $this->insert_plan( $repo, 'Active' );
		$archived_id = $this->insert_plan( $repo, 'Archived', 'lite', array( 'status' => PlanStatus::ARCHIVED ) );

		$args = array( 'status' => array( PlanStatus::ACTIVE, PlanStatus::ARCHIVED ) );

		$this->assertSame( array( $active_id, $archived_id ), self::ids( $repo->query( $args ) ) );
		$this->assertSame( 2, $repo->count( $args ) );
		$this->assertSame( array( $archived_id ), self::ids( $repo->query( array( 'status' => array( PlanStatus::ARCHIVED ) ) ) ) );
	}

	public function test_query_empty_or_invalid_status_list_matches_nothing(): void {
		$repo = new PlanRepository();
		$this->insert_plan( $repo, 'Active' );

		$this->assertCount( 0, $repo->query( array( 'status' => array() ) ) );
		$this->assertSame( 0, $repo->count( array( 'status' => array() ) ) );
		$this->assertCount( 0, $repo->query( array( 'status' => array( PlanStatus::ACTIVE, 5 ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'status' => '' ) ) );
		$this->assertCount( 1, $repo->query( array( 'status' => null ) ) );
	}

	public function test_query_defaults_to_id_order_and_sorts_by_name(): void {
		$repo = new PlanRepository();

		$charlie = $this->insert_plan( $repo, 'Charlie' );
		$alpha   = $this->insert_plan( $repo, 'Alpha' );
		$bravo   = $this->insert_plan( $repo, 'Bravo' );

		$this->assertSame( array( $charlie, $alpha, $bravo ), self::ids( $repo->query() ) );
		$this->assertSame( array( $alpha, $bravo, $charlie ), self::ids( $repo->query( array( 'orderby' => 'name' ) ) ) );
		$this->assertSame(
			array( $charlie, $bravo, $alpha ),
			self::ids(
				$repo->query(
					array(
						'orderby' => 'name',
						'order'   => 'desc',
					)
				)
			)
		);
		$this->assertSame(
			array( $bravo, $alpha, $charlie ),
			self::ids( $repo->query( array( 'order' => 'desc' ) ) )
		);
		$this->assertSame( array( $charlie, $alpha, $bravo ), self::ids( $repo->query( array( 'orderby' => 'status' ) ) ), 'An unknown orderby falls back to id.' );
	}

	/**
	 * Search terms that previously looked like placeholders after LIKE wildcards.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function prepare_specifier_search_terms_provider(): array {
		return array(
			'starts with s' => array( 'status-specifier-regression' ),
			'starts with d' => array( 'daily-specifier-regression' ),
			'starts with f' => array( 'fixed-specifier-regression' ),
			'starts with F' => array( 'Featured-specifier-regression' ),
			'starts with i' => array( 'intro-specifier-regression' ),
		);
	}

	/**
	 * @dataProvider prepare_specifier_search_terms_provider
	 *
	 * @param string $search Search term.
	 */
	public function test_query_search_terms_starting_with_prepare_specifiers( string $search ): void {
		$repo = new PlanRepository();

		$this->insert_plan( $repo, 'Unrelated prepare regression plan', 'lite' );
		$expected_id = $this->insert_plan( $repo, $search . ' plan', 'lite' );

		$query_args = array(
			'extension_slugs' => array( 'lite' ),
			'status'          => PlanStatus::ACTIVE,
			'search'          => $search,
			'orderby'         => 'id',
			'order'           => 'asc',
			'limit'           => 10,
			'offset'          => 0,
		);
		$plans      = $repo->query( $query_args );

		$this->assertCount( 1, $plans );
		$this->assertSame( $expected_id, $plans[0]->get_id() );

		$this->assertSame( 1, $repo->count( $query_args ) );
	}

	public function test_invalid_extension_scopes_do_not_return_unscoped_results(): void {
		$repo = new PlanRepository();

		$id = $this->insert_plan( $repo, 'Scoped', 'lite' );

		$this->assertInstanceOf( Plan::class, $repo->find( $id, 'any' ) );
		// Test with extension_slugs array.
		$this->assertCount( 1, $repo->query( array( 'extension_slugs' => array( 'any' ) ) ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slugs' => array( 'any' ) ) ) );
		// Test with null extension_slugs.
		$this->assertCount( 1, $repo->query( array( 'extension_slugs' => null ) ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slugs' => null ) ) );

		$this->assertNull( $repo->find( $id, '' ) );
		$this->assertNull( $repo->find( $id, 'bad slug' ) );
		$this->assertCount( 0, $repo->query( array( 'extension_slugs' => array() ) ) );
		$this->assertSame( 0, $repo->count( array( 'extension_slugs' => array() ) ) );
		$this->assertCount( 0, $repo->query( array( 'extension_slugs' => array( '' ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'extension_slugs' => 'not-an-array' ) ) );
		$this->assertCount( 0, $repo->query( array( 'extension_slugs' => array( 'lite', '' ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'extension_slugs' => array( 'bad slug' ) ) ) );
	}

	public function test_query_extension_slugs_filters_by_single_and_multiple_slugs(): void {
		$repo = new PlanRepository();

		$lite_id  = $this->insert_plan( $repo, 'Lite plan', 'lite' );
		$other_id = $this->insert_plan( $repo, 'Other plan', 'other-extension' );

		$single = $repo->query( array( 'extension_slugs' => array( 'lite' ) ) );
		$this->assertSame( array( $lite_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $single ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slugs' => array( 'lite' ) ) ) );

		$both = $repo->query( array( 'extension_slugs' => array( 'lite', 'other-extension' ) ) );
		$this->assertSame( array( $lite_id, $other_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $both ) );
	}

	public function test_query_singular_extension_slug_arg_is_unknown_and_ignored(): void {
		$repo = new PlanRepository();

		$plan_id = $this->insert_plan( $repo, 'Scoped', 'lite' );

		$plans = $repo->query( array( 'extension_slug' => 'other-extension' ) );
		$this->assertSame( array( $plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slug' => '' ) ) );
	}

	public function test_query_ids_returns_only_those_plans(): void {
		$repo = new PlanRepository();

		$first_plan_id  = $this->insert_plan( $repo, 'First', 'lite' );
		$second_plan_id = $this->insert_plan( $repo, 'Second', 'lite' );
		$this->insert_plan( $repo, 'Third', 'lite' );

		$plans = $repo->query( array( 'ids' => array( $first_plan_id, $second_plan_id ) ) );

		$this->assertSame( array( $first_plan_id, $second_plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 2, $repo->count( array( 'ids' => array( $first_plan_id, $second_plan_id ) ) ) );
	}

	public function test_query_ids_composes_with_status_and_extension_slugs(): void {
		$repo = new PlanRepository();

		$active_id  = $this->insert_plan( $repo, 'Active lite', 'lite' );
		$foreign_id = $this->insert_plan( $repo, 'Other extension', 'other-extension' );

		$archived = $repo->find( $this->insert_plan( $repo, 'Archived lite', 'lite' ) );
		$this->assertInstanceOf( Plan::class, $archived );
		$archived->set_status( PlanStatus::ARCHIVED );
		$repo->update_fields( $archived, array( 'status' ) );

		$plans = $repo->query(
			array(
				'status'          => PlanStatus::ACTIVE,
				'extension_slugs' => array( 'lite' ),
				'ids'             => array( $active_id, $foreign_id, (int) $archived->get_id() ),
			)
		);

		$this->assertCount( 1, $plans );
		$this->assertSame( $active_id, $plans[0]->get_id() );
	}

	public function test_query_empty_or_invalid_ids_match_nothing(): void {
		$repo    = new PlanRepository();
		$plan_id = $this->insert_plan( $repo, 'Plan', 'lite' );

		$this->assertCount( 0, $repo->query( array( 'ids' => array() ) ) );
		$this->assertSame( 0, $repo->count( array( 'ids' => array() ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => array( $plan_id, 0 ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => array( 'junk' ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => 'not-an-array' ) ) );
	}

	public function test_query_null_ids_behaves_as_arg_absent(): void {
		$repo = new PlanRepository();

		$first_plan_id  = $this->insert_plan( $repo, 'First', 'lite' );
		$second_plan_id = $this->insert_plan( $repo, 'Second', 'lite' );

		$plans = $repo->query( array( 'ids' => null ) );

		$this->assertSame( array( $first_plan_id, $second_plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 2, $repo->count( array( 'ids' => null ) ) );
	}

	public function test_delete_removes_the_row(): void {
		$repo = new PlanRepository();

		$id = $this->insert_plan( $repo, 'Doomed' );

		$this->assertTrue( $repo->delete( $id ) );
		$this->assertNull( $repo->find( $id ) );
	}
}

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
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository
 */
class PlanRepositoryTest extends EngineIntegrationTestCase {

	private function make_plan( PlanRepository $repo, string $name, string $extension_slug, int $sort_order = 0 ): int {
		return $repo->insert(
			Plan::create(
				array(
					'name'           => $name,
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
					'extension_slug' => $extension_slug,
					'sort_order'     => $sort_order,
				)
			)
		);
	}

	public function test_plan_round_trips_with_policies_and_extension_slug(): void {
		$repo = new PlanRepository();

		$plan = Plan::create(
			array(
				'name'           => 'Monthly',
				'description'    => 'A monthly plan',
				'billing_policy' => BillingPolicy::from_array(
					array(
						'period'     => 'month',
						'interval'   => 1,
						'max_cycles' => 12,
					)
				),
				'pricing_policy' => PricingPolicy::from_array(
					array(
						'policies' => array(
							array(
								'type'  => 'percentage',
								'value' => 10,
							),
						),
					)
				),
				'status'         => Plan::STATUS_ARCHIVED,
				'sort_order'     => 4,
				'extension_slug' => 'lite',
			)
		);

		$id = $repo->insert( $plan );
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $id, $plan->get_id() );

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertSame( 'Monthly', $fetched->get_name() );
		$this->assertSame( 'A monthly plan', $fetched->get_description() );
		$this->assertSame( 'lite', $fetched->get_extension_slug() );
		$this->assertSame( Plan::STATUS_ARCHIVED, $fetched->get_status() );
		$this->assertSame( 4, $fetched->get_sort_order() );
		$this->assertSame( 'month', $fetched->get_billing_policy()->get_period() );
		$this->assertSame( 12, $fetched->get_billing_policy()->get_max_cycles() );
		$this->assertNotNull( $fetched->get_pricing_policy() );
		$this->assertSame( 90.0, $fetched->calculate_price( 100.0 ) );
	}

	public function test_plan_without_optional_policies_round_trips(): void {
		$repo = new PlanRepository();

		$id = $repo->insert(
			Plan::create(
				array(
					'name'           => 'Bare',
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'week',
							'interval' => 2,
						)
					),
				)
			)
		);

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertNull( $fetched->get_pricing_policy() );
		$this->assertNull( $fetched->get_delivery_policy() );
		$this->assertNull( $fetched->get_extension_slug() );
	}

	public function test_merchant_code_round_trips_through_insert_and_find(): void {
		$repo = new PlanRepository();

		$id = $repo->insert(
			Plan::create(
				array(
					'name'           => 'Coded',
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
					'merchant_code'  => 'coffee-club',
				)
			)
		);

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertSame( 'coffee-club', $fetched->get_merchant_code() );
	}

	public function test_duplicate_merchant_code_insert_throws_within_one_extension(): void {
		$repo = new PlanRepository();

		$make = static function ( string $extension_slug ): Plan {
			return Plan::create(
				array(
					'name'           => 'Duplicate code',
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
					'merchant_code'  => 'dupe-code',
					'extension_slug' => $extension_slug,
				)
			);
		};

		$repo->insert( $make( 'lite' ) );

		$this->expectException( \RuntimeException::class );
		$repo->insert( $make( 'lite' ) );
	}

	public function test_same_merchant_code_coexists_across_extensions(): void {
		$repo = new PlanRepository();

		$make = static function ( string $extension_slug ): Plan {
			return Plan::create(
				array(
					'name'           => 'Shared code',
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
					'merchant_code'  => 'monthly-box',
					'extension_slug' => $extension_slug,
				)
			);
		};

		$first_id  = $repo->insert( $make( 'lite' ) );
		$second_id = $repo->insert( $make( 'other-extension' ) );

		$this->assertGreaterThan( 0, $first_id );
		$this->assertGreaterThan( $first_id, $second_id );
	}

	public function test_plans_without_merchant_code_coexist(): void {
		$repo = new PlanRepository();

		$first_id  = $this->make_plan( $repo, 'First uncoded', 'lite' );
		$second_id = $this->make_plan( $repo, 'Second uncoded', 'lite' );

		$this->assertGreaterThan( 0, $first_id );
		$this->assertGreaterThan( $first_id, $second_id );

		$first = $repo->find( $first_id );
		$this->assertInstanceOf( Plan::class, $first );
		$this->assertNull( $first->get_merchant_code() );
	}

	public function test_update_persists_changes(): void {
		$repo = new PlanRepository();

		$plan = Plan::create(
			array(
				'name'           => 'Before',
				'billing_policy' => BillingPolicy::from_array(
					array(
						'period'   => 'month',
						'interval' => 1,
					)
				),
			)
		);
		$id   = $repo->insert( $plan );

		$plan->set_name( 'After' );
		$plan->set_status( Plan::STATUS_ARCHIVED );
		$plan->set_sort_order( 8 );
		$this->assertTrue( $repo->update( $plan ) );

		$updated = $repo->find( $id );
		$this->assertInstanceOf( Plan::class, $updated );
		$this->assertSame( 'After', $updated->get_name() );
		$this->assertSame( Plan::STATUS_ARCHIVED, $updated->get_status() );
		$this->assertSame( 8, $updated->get_sort_order() );
	}

	public function test_query_count_and_reorder_use_plan_lifecycle_fields(): void {
		$repo = new PlanRepository();

		$first    = Plan::create(
			array(
				'name'           => 'Alpha monthly',
				'billing_policy' => BillingPolicy::from_array(
					array(
						'period'   => 'month',
						'interval' => 1,
					)
				),
				'status'         => Plan::STATUS_ACTIVE,
				'sort_order'     => 1,
				'extension_slug' => 'lite',
			)
		);
		$second   = Plan::create(
			array(
				'name'           => 'Beta weekly',
				'billing_policy' => BillingPolicy::from_array(
					array(
						'period'   => 'week',
						'interval' => 1,
					)
				),
				'status'         => Plan::STATUS_ACTIVE,
				'sort_order'     => 2,
				'extension_slug' => 'lite',
			)
		);
		$archived = Plan::create(
			array(
				'name'           => 'Archived yearly',
				'billing_policy' => BillingPolicy::from_array(
					array(
						'period'   => 'year',
						'interval' => 1,
					)
				),
				'status'         => Plan::STATUS_ARCHIVED,
				'sort_order'     => 3,
				'extension_slug' => 'lite',
			)
		);

		$first_id    = $repo->insert( $first );
		$second_id   = $repo->insert( $second );
		$archived_id = $repo->insert( $archived );

		$active = $repo->query(
			array(
				'status' => Plan::STATUS_ACTIVE,
				'search' => 'weekly',
			)
		);

		$this->assertCount( 1, $active );
		$this->assertSame( $second_id, $active[0]->get_id() );
		$this->assertSame( 1, $repo->count( array( 'status' => Plan::STATUS_ARCHIVED ) ) );

		$this->assertTrue(
			$repo->reorder(
				'lite',
				array(
					$first_id    => 9,
					$second_id   => 1,
					$archived_id => 2,
				)
			)
		);

		$ordered = $repo->query(
			array(
				'orderby' => 'sort_order',
				'order'   => 'asc',
				'limit'   => 3,
			)
		);

		$this->assertSame( array( $second_id, $archived_id, $first_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $ordered ) );
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

		$this->make_plan( $repo, 'Unrelated prepare regression plan', 'lite' );
		$expected_id = $this->make_plan( $repo, $search . ' plan', 'lite' );

		$query_args = array(
			'extension_slugs' => array( 'lite' ),
			'status'          => Plan::STATUS_ACTIVE,
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

		$id = $this->make_plan( $repo, 'Scoped', 'lite' );

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

		$lite_id  = $this->make_plan( $repo, 'Lite plan', 'lite', 1 );
		$other_id = $this->make_plan( $repo, 'Other plan', 'other-extension', 2 );

		$single = $repo->query( array( 'extension_slugs' => array( 'lite' ) ) );
		$this->assertSame( array( $lite_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $single ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slugs' => array( 'lite' ) ) ) );

		$both = $repo->query( array( 'extension_slugs' => array( 'lite', 'other-extension' ) ) );
		$this->assertSame( array( $lite_id, $other_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $both ) );
	}

	public function test_query_singular_extension_slug_arg_is_unknown_and_ignored(): void {
		$repo = new PlanRepository();

		$plan_id = $this->make_plan( $repo, 'Scoped', 'lite' );

		$plans = $repo->query( array( 'extension_slug' => 'other-extension' ) );
		$this->assertSame( array( $plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 1, $repo->count( array( 'extension_slug' => '' ) ) );
	}

	public function test_reorder_fails_before_updates_when_an_id_is_missing_or_outside_extension(): void {
		$repo = new PlanRepository();

		$first_id = $this->make_plan( $repo, 'First', 'lite', 1 );
		$other_id = $this->make_plan( $repo, 'Other', 'other-extension', 2 );

		$this->assertFalse(
			$repo->reorder(
				'lite',
				array(
					$first_id => 9,
					999999    => 1,
				)
			)
		);

		$first = $repo->find( $first_id, 'lite' );
		$this->assertInstanceOf( Plan::class, $first );
		$this->assertSame( 1, $first->get_sort_order() );

		$this->assertFalse(
			$repo->reorder(
				'lite',
				array(
					$first_id => 9,
					$other_id => 1,
				)
			)
		);

		$first = $repo->find( $first_id, 'lite' );
		$other = $repo->find( $other_id, 'other-extension' );
		$this->assertInstanceOf( Plan::class, $first );
		$this->assertInstanceOf( Plan::class, $other );
		$this->assertSame( 1, $first->get_sort_order() );
		$this->assertSame( 2, $other->get_sort_order() );
	}

	public function test_query_ids_returns_only_those_plans(): void {
		$repo = new PlanRepository();

		$first_plan_id  = $this->make_plan( $repo, 'First', 'lite', 1 );
		$second_plan_id = $this->make_plan( $repo, 'Second', 'lite', 2 );
		$this->make_plan( $repo, 'Third', 'lite', 3 );

		$plans = $repo->query( array( 'ids' => array( $first_plan_id, $second_plan_id ) ) );

		$this->assertSame( array( $first_plan_id, $second_plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 2, $repo->count( array( 'ids' => array( $first_plan_id, $second_plan_id ) ) ) );
	}

	public function test_query_ids_composes_with_status_and_extension_slugs(): void {
		$repo = new PlanRepository();

		$active_id  = $this->make_plan( $repo, 'Active lite', 'lite', 1 );
		$foreign_id = $this->make_plan( $repo, 'Other extension', 'other-extension', 2 );

		$archived = $repo->find( $this->make_plan( $repo, 'Archived lite', 'lite', 3 ) );
		$this->assertInstanceOf( Plan::class, $archived );
		$archived->set_status( Plan::STATUS_ARCHIVED );
		$this->assertTrue( $repo->update( $archived ) );

		$plans = $repo->query(
			array(
				'status'          => Plan::STATUS_ACTIVE,
				'extension_slugs' => array( 'lite' ),
				'ids'             => array( $active_id, $foreign_id, (int) $archived->get_id() ),
			)
		);

		$this->assertCount( 1, $plans );
		$this->assertSame( $active_id, $plans[0]->get_id() );
	}

	public function test_query_empty_or_invalid_ids_match_nothing(): void {
		$repo    = new PlanRepository();
		$plan_id = $this->make_plan( $repo, 'Plan', 'lite' );

		$this->assertCount( 0, $repo->query( array( 'ids' => array() ) ) );
		$this->assertSame( 0, $repo->count( array( 'ids' => array() ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => array( $plan_id, 0 ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => array( 'junk' ) ) ) );
		$this->assertCount( 0, $repo->query( array( 'ids' => 'not-an-array' ) ) );
	}

	public function test_query_null_ids_behaves_as_arg_absent(): void {
		$repo = new PlanRepository();

		$first_plan_id  = $this->make_plan( $repo, 'First', 'lite', 1 );
		$second_plan_id = $this->make_plan( $repo, 'Second', 'lite', 2 );

		$plans = $repo->query( array( 'ids' => null ) );

		$this->assertSame( array( $first_plan_id, $second_plan_id ), array_map( static fn ( Plan $plan ): ?int => $plan->get_id(), $plans ) );
		$this->assertSame( 2, $repo->count( array( 'ids' => null ) ) );
	}

	public function test_delete_removes_the_row(): void {
		$repo = new PlanRepository();

		$id = $repo->insert(
			Plan::create(
				array(
					'name'           => 'Doomed',
					'billing_policy' => BillingPolicy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
				)
			)
		);

		$this->assertTrue( $repo->delete( $id ) );
		$this->assertNull( $repo->find( $id ) );
	}
}

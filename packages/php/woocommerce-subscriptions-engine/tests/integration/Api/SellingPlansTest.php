<?php
/**
 * Integration tests for the SellingPlans facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\SellingPlans;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\SellingPlans
 */
class SellingPlansTest extends EngineIntegrationTestCase {

	private const SLUG = 'lite';

	/**
	 * Insert a plan.
	 *
	 * @param string               $name      Plan name.
	 * @param array<string, mixed> $overrides Attribute overrides.
	 */
	private function insert_plan( string $name, array $overrides = array() ): int {
		return ( new PlanRepository() )->insert(
			Plan::create(
				array_merge(
					array(
						'name'           => $name,
						'billing_policy' => array(
							'period'   => 'month',
							'interval' => 1,
						),
						'extension_slug' => self::SLUG,
					),
					$overrides
				)
			)
		);
	}

	/**
	 * Map views to their ids.
	 *
	 * @param array<int, PlanView> $plans Views to map.
	 * @return array<int, int>
	 */
	private static function plan_ids( array $plans ): array {
		return array_map(
			static function ( PlanView $plan ): int {
				return $plan->get_id();
			},
			$plans
		);
	}

	public function test_list_plans_without_args_returns_every_status_in_id_order(): void {
		$first_id    = $this->insert_plan( 'Zulu' );
		$archived_id = $this->insert_plan( 'Archived', array( 'status' => PlanStatus::ARCHIVED ) );
		$second_id   = $this->insert_plan( 'Alpha' );
		$this->insert_plan( 'Foreign', array( 'extension_slug' => 'other-extension' ) );

		$plans = ( new SellingPlans( array( self::SLUG ) ) )->list_plans();

		$this->assertSame( array( $first_id, $archived_id, $second_id ), self::plan_ids( $plans ) );
		$this->assertContainsOnlyInstancesOf( PlanView::class, $plans );
	}

	public function test_list_plans_filters_by_a_status_or_a_status_list(): void {
		$active_id   = $this->insert_plan( 'Active' );
		$archived_id = $this->insert_plan( 'Archived', array( 'status' => PlanStatus::ARCHIVED ) );

		$catalog = new SellingPlans( array( self::SLUG ) );

		$this->assertSame( array( $active_id ), self::plan_ids( $catalog->list_plans( array( 'status' => PlanStatus::ACTIVE ) ) ) );
		$this->assertSame(
			array( $active_id, $archived_id ),
			self::plan_ids( $catalog->list_plans( array( 'status' => array( PlanStatus::ACTIVE, PlanStatus::ARCHIVED ) ) ) )
		);
	}

	public function test_get_plans_returns_owned_plans_in_id_order_with_an_optional_status(): void {
		$first_id    = $this->insert_plan( 'First' );
		$second_id   = $this->insert_plan( 'Second' );
		$excluded_id = $this->insert_plan( 'Excluded' );
		$archived_id = $this->insert_plan( 'Archived', array( 'status' => PlanStatus::ARCHIVED ) );
		$foreign_id  = $this->insert_plan( 'Foreign', array( 'extension_slug' => 'other-extension' ) );

		$catalog   = new SellingPlans( array( self::SLUG ) );
		$requested = array( $archived_id, $second_id, $first_id, $foreign_id, 999999 );

		// Id order regardless of request order; foreign and unknown ids are absent.
		$all = self::plan_ids( $catalog->get_plans( $requested ) );
		$this->assertSame( array( $first_id, $second_id, $archived_id ), $all );
		$this->assertNotContains( $excluded_id, $all );

		$this->assertSame(
			array( $first_id, $second_id ),
			self::plan_ids( $catalog->get_plans( $requested, array( 'status' => PlanStatus::ACTIVE ) ) )
		);
	}

	public function test_get_plans_empty_or_invalid_ids_yield_an_empty_array(): void {
		$plan_id = $this->insert_plan( 'Plan' );

		$catalog = new SellingPlans( array( self::SLUG ) );

		// Non-int junk coverage lives in PlanRepositoryTest; the facade takes int ids.
		$this->assertSame( array(), $catalog->get_plans( array() ) );
		$this->assertSame( array(), $catalog->get_plans( array( $plan_id, 0 ) ) );
	}

	public function test_get_plan_returns_an_in_scope_plan_in_any_status(): void {
		$archived_id = $this->insert_plan(
			'Archived',
			array(
				'status'         => PlanStatus::ARCHIVED,
				'pricing_policy' => array( 'opaque' => true ),
			)
		);
		$foreign_id  = $this->insert_plan( 'Foreign', array( 'extension_slug' => 'other-extension' ) );

		$catalog = new SellingPlans( array( self::SLUG ) );
		$plan    = $catalog->get_plan( $archived_id );

		$this->assertInstanceOf( PlanView::class, $plan );
		$this->assertSame( $archived_id, $plan->get_id() );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( self::SLUG, $plan->get_owner() );
		$this->assertSame( array( 'opaque' => true ), $plan->get_pricing_policy() );

		$this->assertNull( $catalog->get_plan( $foreign_id ) );
		$this->assertNull( $catalog->get_plan( 999999 ) );
		$this->assertNull( $catalog->get_plan( 0 ) );
		$this->assertNull( $catalog->get_plan( -1 ) );
	}

	public function test_two_slug_instance_reads_across_both_slugs(): void {
		$lite_id    = $this->insert_plan( 'Lite plan' );
		$other_id   = $this->insert_plan( 'Other plan', array( 'extension_slug' => 'other-extension' ) );
		$foreign_id = $this->insert_plan( 'Foreign', array( 'extension_slug' => 'third-extension' ) );

		$catalog = new SellingPlans( array( self::SLUG, 'other-extension' ) );

		$this->assertSame( array( $lite_id, $other_id ), self::plan_ids( $catalog->list_plans() ) );
		$this->assertSame( array( $lite_id, $other_id ), self::plan_ids( $catalog->get_plans( array( $other_id, $lite_id, $foreign_id ) ) ) );
		$this->assertInstanceOf( PlanView::class, $catalog->get_plan( $other_id ) );
		$this->assertNull( $catalog->get_plan( $foreign_id ) );
	}
}

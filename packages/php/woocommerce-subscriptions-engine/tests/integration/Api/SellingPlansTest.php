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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\SellingPlans
 */
class SellingPlansTest extends EngineIntegrationTestCase {

	private const SLUG = 'lite';

	/**
	 * Insert a plan group.
	 *
	 * @param string $merchant_code  Merchant code.
	 * @param string $extension_slug Owning extension slug.
	 */
	private function make_group( string $merchant_code, string $extension_slug = self::SLUG ): int {
		return ( new PlanGroupRepository() )->insert(
			PlanGroup::create(
				array(
					'name'           => 'Group ' . $merchant_code,
					'merchant_code'  => $merchant_code,
					'extension_slug' => $extension_slug,
				)
			)
		);
	}

	/**
	 * Insert a plan.
	 *
	 * @param int                  $group_id  Parent group id.
	 * @param string               $name      Plan name.
	 * @param array<string, mixed> $overrides Attribute overrides.
	 */
	private function make_plan( int $group_id, string $name, array $overrides = array() ): int {
		return ( new PlanRepository() )->insert(
			Plan::create(
				$group_id,
				array_merge(
					array(
						'name'           => $name,
						'billing_policy' => BillingPolicy::from_array(
							array(
								'period'   => 'month',
								'interval' => 1,
							)
						),
						'extension_slug' => self::SLUG,
					),
					$overrides
				)
			)
		);
	}

	/**
	 * Map plans to their ids.
	 *
	 * @param array<int, Plan> $plans Plans to map.
	 * @return array<int, int|null>
	 */
	private static function plan_ids( array $plans ): array {
		return array_map(
			static function ( Plan $plan ): ?int {
				return $plan->get_id();
			},
			$plans
		);
	}

	public function test_list_plans_excludes_archived_and_foreign_slug_plans(): void {
		$group_id = $this->make_group( 'listing' );

		$second_id = $this->make_plan( $group_id, 'Second', array( 'sort_order' => 2 ) );
		$first_id  = $this->make_plan( $group_id, 'First', array( 'sort_order' => 1 ) );
		$this->make_plan( $group_id, 'Archived', array( 'status' => Plan::STATUS_ARCHIVED ) );
		$this->make_plan( $group_id, 'Foreign', array( 'extension_slug' => 'other-extension' ) );

		$plans = ( new SellingPlans( array( self::SLUG ) ) )->list_plans();

		$this->assertSame( array( $first_id, $second_id ), self::plan_ids( $plans ) );
	}

	public function test_get_plans_returns_active_owned_plans_in_display_order(): void {
		$group_id = $this->make_group( 'fetching' );

		$second_id   = $this->make_plan( $group_id, 'Second', array( 'sort_order' => 2 ) );
		$first_id    = $this->make_plan( $group_id, 'First', array( 'sort_order' => 1 ) );
		$excluded_id = $this->make_plan( $group_id, 'Excluded', array( 'sort_order' => 3 ) );
		$archived_id = $this->make_plan( $group_id, 'Archived', array( 'status' => Plan::STATUS_ARCHIVED ) );
		$foreign_id  = $this->make_plan( $group_id, 'Foreign', array( 'extension_slug' => 'other-extension' ) );

		$catalog = new SellingPlans( array( self::SLUG ) );

		$plans = $catalog->get_plans( array( $second_id, $first_id, $archived_id, $foreign_id, 999999 ) );

		// Display order regardless of request order; archived, foreign, and unknown ids are absent.
		$this->assertSame( array( $first_id, $second_id ), self::plan_ids( $plans ) );
		$this->assertNotContains( $excluded_id, self::plan_ids( $plans ) );
	}

	public function test_get_plans_empty_or_invalid_ids_yield_an_empty_array(): void {
		$group_id = $this->make_group( 'empty-ids' );
		$plan_id  = $this->make_plan( $group_id, 'Plan' );

		$catalog = new SellingPlans( array( self::SLUG ) );

		// Non-int junk coverage lives in PlanRepositoryTest; the facade takes int ids.
		$this->assertSame( array(), $catalog->get_plans( array() ) );
		$this->assertSame( array(), $catalog->get_plans( array( $plan_id, 0 ) ) );
	}

	public function test_two_slug_instance_reads_across_both_slugs(): void {
		$lite_group_id  = $this->make_group( 'two-slug-lite' );
		$other_group_id = $this->make_group( 'two-slug-other', 'other-extension' );

		$lite_id    = $this->make_plan( $lite_group_id, 'Lite plan', array( 'sort_order' => 1 ) );
		$other_id   = $this->make_plan(
			$other_group_id,
			'Other plan',
			array(
				'sort_order'     => 2,
				'extension_slug' => 'other-extension',
			)
		);
		$foreign_id = $this->make_plan(
			$lite_group_id,
			'Foreign',
			array(
				'sort_order'     => 3,
				'extension_slug' => 'third-extension',
			)
		);

		$catalog = new SellingPlans( array( self::SLUG, 'other-extension' ) );

		$this->assertSame( array( $lite_id, $other_id ), self::plan_ids( $catalog->list_plans() ) );
		$this->assertSame( array( $lite_id, $other_id ), self::plan_ids( $catalog->get_plans( array( $other_id, $lite_id, $foreign_id ) ) ) );
	}
}

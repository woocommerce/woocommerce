<?php
/**
 * Unit tests for the PlanView DTO.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api\View;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView
 */
class PlanViewTest extends TestCase {

	public function test_every_getter_maps_from_a_stored_plan(): void {
		$billing  = array(
			'period'   => 'month',
			'interval' => 1,
		);
		$pricing  = array( 'policies' => array( array( 'type' => 'percentage' ) ) );
		$delivery = array( 'anchor' => 3 );

		$view = PlanView::from_plan(
			Plan::from_storage(
				array(
					'id'               => 7,
					'name'             => 'Monthly',
					'status'           => 'archived',
					'extension_slug'   => 'acme-subs',
					'billing_policy'   => $billing,
					'pricing_policy'   => $pricing,
					'delivery_policy'  => $delivery,
					'date_created_gmt' => '2026-01-01 00:00:00',
					'date_updated_gmt' => '2026-01-02 00:00:00',
				)
			)
		);

		$this->assertSame( 7, $view->get_id() );
		$this->assertSame( 'acme-subs', $view->get_owner() );
		$this->assertSame( 'archived', $view->get_status() );
		$this->assertSame( 'Monthly', $view->get_name() );
		$this->assertSame( $billing, $view->get_billing_policy() );
		$this->assertSame( $pricing, $view->get_pricing_policy() );
		$this->assertSame( $delivery, $view->get_delivery_policy() );
		$this->assertSame( '2026-01-01 00:00:00', $view->get_date_created_gmt() );
		$this->assertSame( '2026-01-02 00:00:00', $view->get_date_updated_gmt() );
	}

	public function test_an_unsaved_plan_has_id_zero_and_no_dates(): void {
		$view = PlanView::from_plan( Plan::create( array( 'name' => 'Draft' ) ) );

		$this->assertSame( 0, $view->get_id() );
		$this->assertNull( $view->get_owner() );
		$this->assertNull( $view->get_date_created_gmt() );
		$this->assertNull( $view->get_date_updated_gmt() );
	}

	public function test_null_policies_stay_null(): void {
		$view = PlanView::from_plan( Plan::create( array( 'name' => 'Bare' ) ) );

		$this->assertNull( $view->get_billing_policy() );
		$this->assertNull( $view->get_pricing_policy() );
		$this->assertNull( $view->get_delivery_policy() );
	}

	public function test_the_view_does_not_follow_later_entity_changes(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Before',
				'pricing_policy' => array( 'a' => 1 ),
			)
		);
		$view = PlanView::from_plan( $plan );

		$plan->set_name( 'After' );
		$plan->set_pricing_policy( array( 'b' => 2 ) );

		$this->assertSame( 'Before', $view->get_name() );
		$this->assertSame( array( 'a' => 1 ), $view->get_pricing_policy() );
	}
}

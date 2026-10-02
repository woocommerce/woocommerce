<?php
/**
 * Unit tests for PlanSnapshot.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\ValueObject;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot
 */
class PlanSnapshotTest extends TestCase {

	public function test_schema_version_defaults_to_one(): void {
		$snapshot = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ) );

		$this->assertSame( 1, $snapshot->get_schema_version() );
	}

	public function test_schema_version_is_carried(): void {
		$snapshot = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ), 3 );

		$this->assertSame( 3, $snapshot->get_schema_version() );
	}

	/**
	 * @testdox get_selling_plan_id returns the plan id when present.
	 */
	public function test_get_selling_plan_id_returns_the_id_when_present(): void {
		$snapshot = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ) );

		$this->assertSame( 7, $snapshot->get_selling_plan_id() );
	}

	/**
	 * @testdox get_selling_plan_id returns null when the key is absent.
	 */
	public function test_get_selling_plan_id_is_null_when_absent(): void {
		$snapshot = PlanSnapshot::from_array( array( 'name' => 'Monthly box' ) );

		$this->assertNull( $snapshot->get_selling_plan_id() );
	}

	public function test_round_trips_through_array(): void {
		$data     = array(
			'selling_plan_id' => 7,
			'name'            => 'Monthly box',
			'billing_policy'  => array(
				'period'   => 'month',
				'interval' => 1,
			),
		);
		$snapshot = PlanSnapshot::from_array( $data );

		$this->assertSame( $data, $snapshot->to_array() );
	}

	public function test_to_payload_returns_the_plan_terms_in_their_original_order(): void {
		$data     = array(
			'name'            => 'Monthly box',
			'selling_plan_id' => 7,
		);
		$snapshot = PlanSnapshot::from_array( $data );

		// The payload is the data as given; no key reordering happens here. Equal
		// consecutive plans dedupe by copy-forward in storage, not by a canonical hash.
		$this->assertSame( $data, $snapshot->to_payload() );
	}

	public function test_from_payload_reconstructs_an_equal_snapshot(): void {
		$snapshot = PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 7,
				'name'            => 'Monthly box',
			),
			2
		);

		$restored = PlanSnapshot::from_payload( $snapshot->to_payload(), $snapshot->get_schema_version() );

		$this->assertSame( $snapshot->to_payload(), $restored->to_payload() );
		$this->assertSame( 2, $restored->get_schema_version() );
	}

	public function test_negative_schema_version_throws(): void {
		$this->expectException( DomainException::class );

		PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ), 0 );
	}

	/**
	 * @testdox get_billing_policy reconstructs the frozen cadence from the payload.
	 */
	public function test_get_billing_policy_reconstructs_the_frozen_cadence(): void {
		$snapshot = PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 7,
				'name'            => 'Monthly box',
				'billing_policy'  => array(
					'period'         => 'month',
					'interval'       => 3,
					'min_cycles'     => null,
					'max_cycles'     => 12,
					'trial_duration' => null,
				),
			)
		);

		$policy = $snapshot->get_billing_policy();

		$this->assertInstanceOf( BillingPolicy::class, $policy );
		$this->assertSame( 'month', $policy->get_period() );
		$this->assertSame( 3, $policy->get_interval() );
		$this->assertSame( 12, $policy->get_max_cycles() );
	}

	/**
	 * @testdox get_billing_policy is null when the payload carries no billing policy.
	 */
	public function test_get_billing_policy_is_null_when_absent(): void {
		$snapshot = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ) );

		$this->assertNull( $snapshot->get_billing_policy() );
	}

	/**
	 * @testdox get_billing_policy degrades to null for a structurally-invalid stored policy.
	 */
	public function test_get_billing_policy_is_null_for_an_unreadable_policy(): void {
		// `interval` missing: BillingPolicy::from_array() would throw; the accessor swallows
		// it and degrades to "no cadence" rather than fataling the read.
		$snapshot = PlanSnapshot::from_array(
			array(
				'billing_policy' => array( 'period' => 'month' ),
			)
		);

		$this->assertNull( $snapshot->get_billing_policy() );
	}
}

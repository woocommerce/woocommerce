<?php
/**
 * Unit tests for the Plan entity (pure-Core behavior).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan
 */
class PlanTest extends TestCase {

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

	public function test_create_defaults(): void {
		$plan = Plan::create( array( 'name' => 'Monthly box' ) );

		$this->assertNull( $plan->get_id() );
		$this->assertSame( 'Monthly box', $plan->get_name() );
		$this->assertSame( PlanStatus::ACTIVE, $plan->get_status() );
		$this->assertNull( $plan->get_extension_slug() );
		$this->assertNull( $plan->get_billing_policy() );
		$this->assertNull( $plan->get_pricing_policy() );
		$this->assertNull( $plan->get_delivery_policy() );
		$this->assertNull( $plan->get_date_created_gmt() );
		$this->assertNull( $plan->get_date_updated_gmt() );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_policy_fields(): array {
		return array(
			'billing'  => array( 'billing_policy' ),
			'pricing'  => array( 'pricing_policy' ),
			'delivery' => array( 'delivery_policy' ),
		);
	}

	/**
	 * @dataProvider provide_policy_fields
	 *
	 * @param string $field Policy field.
	 */
	public function test_a_policy_round_trips_opaquely_through_storage( string $field ): void {
		$payload = array(
			'period'   => 'fortnight',
			'anything' => array(
				'nested' => array( 1, 'two', array( 'three' => true ) ),
				'empty'  => array(),
			),
			'flag'     => null,
		);

		$plan = Plan::create(
			array(
				'name'           => 'Opaque',
				'extension_slug' => 'my-ext',
				$field           => $payload,
			)
		);

		$storage = $plan->to_storage();
		$this->assertSame( $payload, $storage[ $field ] );

		$hydrated = Plan::from_storage( $storage );
		$this->assertSame( $payload, $this->policy( $hydrated, $field ) );
		$this->assertSame( 'my-ext', $hydrated->get_extension_slug() );
	}

	/**
	 * @dataProvider provide_policy_fields
	 *
	 * @param string $field Policy field.
	 */
	public function test_an_empty_object_policy_is_kept_as_an_empty_array( string $field ): void {
		$plan = Plan::from_storage(
			array(
				'name' => 'Empty',
				$field => array(),
			)
		);

		$this->assertSame( array(), $this->policy( $plan, $field ) );
	}

	/**
	 * @dataProvider provide_policy_fields
	 *
	 * @param string $field Policy field.
	 */
	public function test_a_null_policy_stays_null( string $field ): void {
		$plan = Plan::create(
			array(
				'name' => 'Null',
				$field => null,
			)
		);

		$this->assertNull( $plan->to_storage()[ $field ] );
		$this->assertNull( $this->policy( Plan::from_storage( $plan->to_storage() ), $field ) );
	}

	/**
	 * @return array<string, array{0: string, 1: mixed}>
	 */
	public function provide_bad_policy_values(): array {
		$cases = array();
		foreach ( array( 'billing_policy', 'pricing_policy', 'delivery_policy' ) as $field ) {
			$cases[ "{$field} list" ]   = array( $field, array( 'a', 'b' ) );
			$cases[ "{$field} scalar" ] = array( $field, 'monthly' );
			$cases[ "{$field} int" ]    = array( $field, 5 );
		}
		return $cases;
	}

	/**
	 * @dataProvider provide_bad_policy_values
	 *
	 * @param string $field Policy field.
	 * @param mixed  $value Bad value.
	 */
	public function test_create_rejects_a_non_object_policy( string $field, $value ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $field );

		Plan::create(
			array(
				'name' => 'Bad',
				$field => $value,
			)
		);
	}

	/**
	 * @dataProvider provide_bad_policy_values
	 *
	 * @param string $field Policy field.
	 * @param mixed  $value Bad value.
	 */
	public function test_from_storage_rejects_a_non_object_policy( string $field, $value ): void {
		$this->expectException( InvalidArgumentException::class );

		Plan::from_storage(
			array(
				'name' => 'Bad',
				$field => $value,
			)
		);
	}

	/**
	 * @dataProvider provide_policy_fields
	 *
	 * @param string $field Policy field.
	 */
	public function test_a_setter_rejects_a_list_policy( string $field ): void {
		$plan   = Plan::create( array( 'name' => 'Bad' ) );
		$setter = 'set_' . $field;

		$this->expectException( InvalidArgumentException::class );

		$plan->{$setter}( array( 'a', 'b' ) );
	}

	/**
	 * @dataProvider provide_policy_fields
	 *
	 * @param string $field Policy field.
	 */
	public function test_a_setter_replaces_the_whole_payload( string $field ): void {
		$plan   = Plan::create(
			array(
				'name' => 'Replace',
				$field => array(
					'a' => 1,
					'b' => 2,
				),
			)
		);
		$setter = 'set_' . $field;

		$plan->{$setter}( array( 'c' => 3 ) );
		$this->assertSame( array( 'c' => 3 ), $this->policy( $plan, $field ) );

		$plan->{$setter}( null );
		$this->assertNull( $this->policy( $plan, $field ) );
	}

	public function test_create_rejects_an_unregistered_status(): void {
		$this->expectException( InvalidArgumentException::class );

		Plan::create(
			array(
				'name'   => 'Unknown',
				'status' => 'seasonal',
			)
		);
	}

	public function test_set_status_rejects_an_unregistered_status(): void {
		$plan = Plan::create( array( 'name' => 'Unknown' ) );

		$this->expectException( InvalidArgumentException::class );

		$plan->set_status( 'seasonal' );
	}

	public function test_a_registered_extension_status_is_accepted(): void {
		StatusRegistry::register( StatusRegistry::KIND_PLAN, 'seasonal' );

		$plan = Plan::create(
			array(
				'name'   => 'Seasonal',
				'status' => 'seasonal',
			)
		);
		$this->assertSame( 'seasonal', $plan->get_status() );

		$plan->set_status( PlanStatus::ARCHIVED );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->to_storage()['status'] );
	}

	public function test_from_storage_hydrates_an_unregistered_stored_status(): void {
		$plan = Plan::from_storage(
			array(
				'id'     => 5,
				'name'   => 'Legacy',
				'status' => 'retired-by-ext',
			)
		);

		$this->assertSame( 'retired-by-ext', $plan->get_status() );

		$plan->set_status( 'retired-by-ext' );
		$this->assertSame( 'retired-by-ext', $plan->to_storage()['status'] );
	}

	public function test_from_storage_hydrates_the_id_and_dates(): void {
		$plan = Plan::from_storage(
			array(
				'id'               => '12',
				'name'             => 'Stored',
				'status'           => 'archived',
				'date_created_gmt' => '2026-01-02 03:04:05',
				'date_updated_gmt' => '2026-02-03 04:05:06',
			)
		);

		$this->assertSame( 12, $plan->get_id() );
		$this->assertSame( PlanStatus::ARCHIVED, $plan->get_status() );
		$this->assertSame( '2026-01-02 03:04:05', $plan->get_date_created_gmt() );
		$this->assertSame( '2026-02-03 04:05:06', $plan->get_date_updated_gmt() );
	}

	public function test_to_storage_has_exactly_the_record_columns(): void {
		$plan = Plan::create( array( 'name' => 'Columns' ) );

		$this->assertEqualsCanonicalizing(
			array( 'name', 'status', 'extension_slug', 'billing_policy', 'pricing_policy', 'delivery_policy' ),
			array_keys( $plan->to_storage() )
		);
	}

	public function test_name_and_id_are_mutable(): void {
		$plan = Plan::create( array( 'name' => 'Before' ) );

		$plan->set_name( 'After' );
		$plan->set_id( 9 );

		$this->assertSame( 'After', $plan->get_name() );
		$this->assertSame( 9, $plan->get_id() );
	}

	/**
	 * Read a policy by field name.
	 *
	 * @param Plan   $plan  Plan.
	 * @param string $field Policy field.
	 * @return array<string, mixed>|null
	 */
	private function policy( Plan $plan, string $field ): ?array {
		switch ( $field ) {
			case 'billing_policy':
				return $plan->get_billing_policy();
			case 'pricing_policy':
				return $plan->get_pricing_policy();
			default:
				return $plan->get_delivery_policy();
		}
	}
}

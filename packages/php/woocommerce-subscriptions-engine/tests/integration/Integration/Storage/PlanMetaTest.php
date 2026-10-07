<?php
/**
 * Integration tests for the multi-value plan meta reads and writes on
 * PlanRepository (WordPress post-meta semantics).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository
 */
class PlanMetaTest extends EngineIntegrationTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var PlanRepository
	 */
	private $sut;

	/**
	 * A stored plan id.
	 *
	 * @var int
	 */
	private $id;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new PlanRepository();
		$this->id  = $this->sut->insert(
			Plan::create(
				array(
					'name'           => 'Meta plan',
					'extension_slug' => 'acme-subs',
				)
			)
		);
	}

	public function test_add_meta_keeps_every_value_under_one_key_in_order(): void {
		$first  = $this->sut->add_meta( $this->id, 'note', 'one' );
		$second = $this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertIsInt( $first );
		$this->assertIsInt( $second );
		$this->assertGreaterThan( $first, $second );
		$this->assertSame( array( 'one', 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( 'one', $this->sut->get_meta( $this->id, 'note', true ) );
	}

	public function test_unique_add_on_an_existing_key_adds_nothing(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_adds_when_absent(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_rewrites_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three' ) );
		$this->assertSame( array( 'three', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_update_meta_with_a_previous_value_rewrites_only_matching_rows(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'four', 'missing' ) );
	}

	public function test_update_meta_to_the_same_value_reports_no_change(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_delete_meta_with_a_value_removes_only_that_row(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_delete_meta_without_a_value_removes_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );
		$this->sut->add_meta( $this->id, 'other', 'kept' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'other' ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, 'note' ) );
	}

	public function test_get_meta_without_a_key_groups_every_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'flag', 'yes' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertSame(
			array(
				'note' => array( 'one', 'two' ),
				'flag' => array( 'yes' ),
			),
			$this->sut->get_meta( $this->id )
		);
	}

	public function test_arrays_round_trip_through_serialization(): void {
		$value = array(
			'a' => 1,
			'b' => array( 'c' ),
		);

		$this->sut->add_meta( $this->id, 'payload', $value );

		$this->assertSame( $value, $this->sut->get_meta( $this->id, 'payload', true ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, 'payload', $value ) );
	}

	public function test_missing_key_reads_as_empty(): void {
		$this->assertSame( '', $this->sut->get_meta( $this->id, 'missing', true ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'missing' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	public function test_meta_is_scoped_to_its_plan(): void {
		$plan  = Plan::create(
			array(
				'name'           => 'Other',
				'extension_slug' => 'acme-subs',
			)
		);
		$other = $this->sut->insert( $plan );
		$this->sut->add_meta( $other, 'note', 'theirs' );

		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_meta_survives_a_plan_update(): void {
		$this->sut->add_meta( $this->id, 'note', 'kept' );

		$plan = $this->sut->find( $this->id );
		$this->assertInstanceOf( Plan::class, $plan );
		$plan->set_name( 'Renamed' );
		$this->sut->update_fields( $plan, array( 'name' ) );

		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	public function test_delete_removes_the_plan_meta(): void {
		$this->sut->add_meta( $this->id, 'note', 'gone' );

		$this->assertTrue( $this->sut->delete( $this->id ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	public function test_a_wrong_owner_delete_keeps_the_plan_and_its_meta(): void {
		$this->sut->add_meta( $this->id, 'note', 'kept' );

		$this->assertFalse( $this->sut->delete( $this->id, 'another-extension' ) );
		$this->assertNotNull( $this->sut->find( $this->id ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @dataProvider provide_write_methods
	 *
	 * @param string $method Write method name.
	 */
	public function test_an_empty_key_is_rejected_on_writes( string $method ): void {
		$this->expectException( InvalidArgumentException::class );

		$this->sut->{$method}( $this->id, '', 'value' );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_write_methods(): array {
		return array(
			'add'    => array( 'add_meta' ),
			'update' => array( 'update_meta' ),
			'delete' => array( 'delete_meta' ),
		);
	}
}

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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

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

	/**
	 * @testdox add_meta keeps every value under one key in order.
	 */
	public function test_add_meta_keeps_every_value_under_one_key_in_order(): void {
		$first  = $this->sut->add_meta( $this->id, 'note', 'one' );
		$second = $this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertIsInt( $first );
		$this->assertIsInt( $second );
		$this->assertGreaterThan( $first, $second );
		$this->assertSame( array( 'one', 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( 'one', $this->sut->get_meta( $this->id, 'note', true ) );
	}

	/**
	 * @testdox unique add on an existing key adds nothing.
	 */
	public function test_unique_add_on_an_existing_key_adds_nothing(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta adds the key when absent.
	 */
	public function test_update_meta_adds_when_absent(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta rewrites every row for the key.
	 */
	public function test_update_meta_rewrites_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three' ) );
		$this->assertSame( array( 'three', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta with a previous value rewrites only matching rows.
	 */
	public function test_update_meta_with_a_previous_value_rewrites_only_matching_rows(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'four', 'missing' ) );
	}

	/**
	 * @testdox update_meta to the same value reports no change.
	 */
	public function test_update_meta_to_the_same_value_reports_no_change(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );

		$this->assertFalse( $this->sut->update_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox delete_meta with a value removes only that row.
	 */
	public function test_delete_meta_with_a_value_removes_only_that_row(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note', 'one' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox delete_meta without a value removes every row for the key.
	 */
	public function test_delete_meta_without_a_value_removes_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, 'note', 'one' );
		$this->sut->add_meta( $this->id, 'note', 'two' );
		$this->sut->add_meta( $this->id, 'other', 'kept' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, 'note' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'other' ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox get_meta without a key groups every key.
	 */
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

	/**
	 * @testdox arrays round-trip through serialization.
	 */
	public function test_arrays_round_trip_through_serialization(): void {
		$value = array(
			'a' => 1,
			'b' => array( 'c' ),
		);

		$this->sut->add_meta( $this->id, 'payload', $value );

		$this->assertSame( $value, $this->sut->get_meta( $this->id, 'payload', true ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, 'payload', $value ) );
	}

	/**
	 * @testdox a missing key reads as empty.
	 */
	public function test_missing_key_reads_as_empty(): void {
		$this->assertSame( '', $this->sut->get_meta( $this->id, 'missing', true ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'missing' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	/**
	 * @testdox meta is scoped to its plan.
	 */
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

	/**
	 * @testdox meta survives a plan update.
	 */
	public function test_meta_survives_a_plan_update(): void {
		$this->sut->add_meta( $this->id, 'note', 'kept' );

		$plan = $this->sut->find( $this->id );
		$this->assertInstanceOf( Plan::class, $plan );
		$plan->set_name( 'Renamed' );
		$this->sut->update_fields( $plan, array( 'name' ) );

		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox delete removes the plan's meta.
	 */
	public function test_delete_removes_the_plan_meta(): void {
		$this->sut->add_meta( $this->id, 'note', 'gone' );

		$this->assertTrue( $this->sut->delete( $this->id ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id ) );
	}

	/**
	 * @testdox delete throws when the meta rows fail to delete, so a caller transaction can roll back.
	 */
	public function test_delete_throws_when_the_meta_delete_fails(): void {
		$this->assert_write_throws_on_a_failed_query(
			'DELETE FROM `' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META ) . '`',
			'Failed to delete meta rows',
			function (): void {
				$this->sut->delete( $this->id );
			}
		);
	}

	/**
	 * @testdox delete throws when the plan row fails to delete, and keeps the plan and its meta.
	 */
	public function test_delete_throws_when_the_plan_row_delete_fails(): void {
		$this->sut->add_meta( $this->id, 'note', 'kept' );

		$this->assert_write_throws_on_a_failed_query(
			'DELETE FROM `' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ) . '`',
			'Failed to delete plan ' . $this->id,
			function (): void {
				$this->sut->delete( $this->id );
			}
		);

		$this->assertNotNull( $this->sut->find( $this->id ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox a failed meta write throws.
	 * @dataProvider provide_failing_meta_writes
	 *
	 * @param string            $statement Start of the failing SQL statement.
	 * @param string            $message   Expected message fragment.
	 * @param string            $method    Repository method.
	 * @param array<int, mixed> $args      Arguments after the plan id.
	 */
	public function test_a_failed_meta_write_throws( string $statement, string $message, string $method, array $args ): void {
		$this->sut->add_meta( $this->id, 'existing', 'one' );

		$this->assert_write_throws_on_a_failed_query(
			$statement . ' `' . SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META ) . '`',
			$message,
			function () use ( $method, $args ): void {
				$this->sut->{$method}( $this->id, ...$args );
			}
		);
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: array<int, mixed>}>
	 */
	public function provide_failing_meta_writes(): array {
		return array(
			'add'    => array( 'INSERT INTO', 'Failed to add plan meta', 'add_meta', array( 'note', 'x' ) ),
			'update' => array( 'UPDATE', 'Failed to update plan meta', 'update_meta', array( 'existing', 'two' ) ),
			'delete' => array( 'DELETE FROM', 'Failed to delete plan meta', 'delete_meta', array( 'existing' ) ),
		);
	}

	/**
	 * Break the queries starting with `$statement` and assert `$write` throws.
	 *
	 * @param string   $statement Start of the SQL statement to break.
	 * @param string   $message   Expected message fragment.
	 * @param callable $write     Write to run.
	 */
	private function assert_write_throws_on_a_failed_query( string $statement, string $message, callable $write ): void {
		global $wpdb;

		$break = static function ( string $query ) use ( $statement ): string {
			return 0 === strpos( $query, $statement ) ? 'SELECT broken syntax (' : $query;
		};
		add_filter( 'query', $break );
		$suppressed = $wpdb->suppress_errors( true );

		try {
			$write();
			$this->fail( 'Expected the write to throw.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( $message, $e->getMessage() );
		} finally {
			$wpdb->suppress_errors( $suppressed );
			remove_filter( 'query', $break );
		}
	}

	/**
	 * @testdox a wrong-owner delete keeps the plan and its meta.
	 */
	public function test_a_wrong_owner_delete_keeps_the_plan_and_its_meta(): void {
		$this->sut->add_meta( $this->id, 'note', 'kept' );

		$this->assertFalse( $this->sut->delete( $this->id, 'another-extension' ) );
		$this->assertNotNull( $this->sut->find( $this->id ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox an empty key is rejected on writes.
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

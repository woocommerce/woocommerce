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
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository
 */
class PlanMetaTest extends EngineIntegrationTestCase {

	/**
	 * Owning extension of the fixture plans.
	 */
	private const OWNER = 'acme-subs';

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
					'extension_slug' => self::OWNER,
				)
			)
		);
	}

	/**
	 * @testdox add_meta keeps every value under one key in order.
	 */
	public function test_add_meta_keeps_every_value_under_one_key_in_order(): void {
		$first  = $this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$second = $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

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
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two', true ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta adds the key when absent.
	 */
	public function test_update_meta_adds_when_absent(): void {
		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta rewrites every row for the key.
	 */
	public function test_update_meta_rewrites_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'three' ) );
		$this->assertSame( array( 'three', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox update_meta with a previous value rewrites only matching rows.
	 */
	public function test_update_meta_with_a_previous_value_rewrites_only_matching_rows(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'three', 'two' ) );
		$this->assertSame( array( 'one', 'three' ), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertFalse( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'four', 'missing' ) );
	}

	/**
	 * @testdox update_meta to the same value reports no change.
	 */
	public function test_update_meta_to_the_same_value_reports_no_change(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->assertFalse( $this->sut->update_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox delete_meta with a value removes only that row.
	 */
	public function test_delete_meta_with_a_value_removes_only_that_row(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note', 'one' ) );
		$this->assertSame( array( 'two' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox delete_meta without a value removes every row for the key.
	 */
	public function test_delete_meta_without_a_value_removes_every_row_for_the_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );
		$this->sut->add_meta( $this->id, self::OWNER, 'other', 'kept' );

		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'note' ) );
		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'other' ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, self::OWNER, 'note' ) );
	}

	/**
	 * @testdox get_meta without a key groups every key.
	 */
	public function test_get_meta_without_a_key_groups_every_key(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );
		$this->sut->add_meta( $this->id, self::OWNER, 'flag', 'yes' );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'two' );

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

		$this->sut->add_meta( $this->id, self::OWNER, 'payload', $value );

		$this->assertSame( $value, $this->sut->get_meta( $this->id, 'payload', true ) );
		$this->assertTrue( $this->sut->delete_meta( $this->id, self::OWNER, 'payload', $value ) );
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
				'extension_slug' => self::OWNER,
			)
		);
		$other = $this->sut->insert( $plan );
		$this->sut->add_meta( $other, self::OWNER, 'note', 'theirs' );

		$this->assertSame( array(), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox meta survives a plan update.
	 */
	public function test_meta_survives_a_plan_update(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'kept' );

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
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'gone' );

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
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'kept' );

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
	 * @param string            $statement Start of the failing SQL statement, `{meta}` / `{plans}` for the tables.
	 * @param string            $message   Expected message fragment.
	 * @param string            $method    Repository method.
	 * @param array<int, mixed> $args      Arguments after the plan id.
	 */
	public function test_a_failed_meta_write_throws( string $statement, string $message, string $method, array $args ): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'existing', 'one' );

		$this->assert_write_throws_on_a_failed_query(
			strtr(
				$statement,
				array(
					'{meta}'  => SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META ),
					'{plans}' => SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ),
				)
			),
			$message,
			function () use ( $method, $args ): void {
				$this->sut->{$method}( $this->id, self::OWNER, ...$args );
			}
		);
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: array<int, mixed>}>
	 */
	public function provide_failing_meta_writes(): array {
		return array(
			'add'                       => array( 'INSERT INTO {meta} ', 'Failed to add plan meta', 'add_meta', array( 'note', 'x' ) ),
			'update'                    => array( 'UPDATE {meta} m ', 'Failed to update plan meta', 'update_meta', array( 'existing', 'two' ) ),
			'update classifying read'   => array( 'SELECT EXISTS ( SELECT 1 FROM {meta} m ', 'Failed to read plan meta', 'update_meta', array( 'absent', 'x' ) ),
			'update add branch'         => array( 'INSERT INTO {meta} ', 'Failed to add plan meta', 'update_meta', array( 'absent', 'x' ) ),
			'unique add existence read' => array( 'SELECT p.id FROM {plans} p ', 'Failed to read plan', 'add_meta', array( 'existing', 'x', true ) ),
			'delete'                    => array( 'DELETE m FROM {meta} ', 'Failed to delete plan meta', 'delete_meta', array( 'existing' ) ),
		);
	}

	/**
	 * @testdox a failed plan update or existence read throws.
	 * @dataProvider provide_failing_plan_update_queries
	 *
	 * @param string $statement Start of the failing SQL statement, `{plans}` for the table.
	 * @param string $message   Expected message fragment.
	 */
	public function test_a_failed_plan_update_throws( string $statement, string $message ): void {
		$plans = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$plan  = $this->sut->find( $this->id );
		$this->assertInstanceOf( Plan::class, $plan );

		// The UPDATE changes no row, so update_fields() reads whether the plan exists.
		$change_nothing = static function ( string $query ) use ( $plans ): string {
			return 0 === strpos( $query, "UPDATE {$plans} p " ) ? $query . ' AND 0 = 1' : $query;
		};
		add_filter( 'query', $change_nothing );

		try {
			$this->assert_write_throws_on_a_failed_query(
				str_replace( '{plans}', $plans, $statement ),
				$message,
				function () use ( $plan ): void {
					$this->sut->update_fields( $plan, array( 'name' ) );
				}
			);
		} finally {
			remove_filter( 'query', $change_nothing );
		}
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provide_failing_plan_update_queries(): array {
		return array(
			'update'                  => array( 'UPDATE {plans} p ', 'Failed to update plan' ),
			'existence read after it' => array( 'SELECT p.id FROM {plans} p ', 'Failed to read plan' ),
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
	 * @testdox a delete under another extension slug keeps the plan and its meta.
	 */
	public function test_a_delete_under_another_extension_slug_keeps_the_plan_and_its_meta(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'kept' );

		$this->assertFalse( $this->sut->delete( $this->id, 'another-extension' ) );
		$this->assertNotNull( $this->sut->find( $this->id ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox add_meta and update_meta on a missing plan or a plan of another extension throw and write no orphan row.
	 */
	public function test_writes_on_a_missing_or_foreign_plan_write_nothing(): void {
		foreach ( array( array( 999999, self::OWNER ), array( $this->id, 'another-extension' ) ) as list( $plan_id, $owner ) ) {
			foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
				try {
					$this->sut->{$method}( $plan_id, $owner, 'note', 'orphan' );
					$this->fail( "Expected {$method}() to refuse plan {$plan_id} of {$owner}." );
				} catch ( InvalidArgumentException $e ) {
					$this->assertStringContainsString( sprintf( 'has id %d', $plan_id ), $e->getMessage() );
					$this->assertStringContainsString( $owner, $e->getMessage() );
				}
			}
			$this->assertSame( array(), $this->sut->get_meta( $plan_id ), 'No meta row is written.' );
		}
	}

	/**
	 * @testdox a unique add returns null for a taken key on an own plan and throws for a plan of another extension with that key.
	 */
	public function test_a_unique_add_tells_a_taken_key_from_a_foreign_plan(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'one' );

		$this->assertNull( $this->sut->add_meta( $this->id, self::OWNER, 'note', 'two', true ) );
		$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, 'fresh', 'x', true ), 'An absent key is added by the NOT EXISTS insert.' );

		try {
			$this->sut->add_meta( $this->id, 'another-extension', 'note', 'theirs', true );
			$this->fail( 'Expected a unique add on a foreign plan to throw.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertSame( array( 'one' ), $this->sut->get_meta( $this->id, 'note' ), 'Nothing is written.' );
		}
	}

	/**
	 * @testdox update_meta and delete_meta on a plan of another extension change nothing.
	 */
	public function test_update_and_delete_on_a_foreign_plan_change_nothing(): void {
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'kept' );

		try {
			$this->sut->update_meta( $this->id, 'another-extension', 'note', 'theirs' );
			$this->fail( 'Expected update_meta() to refuse a foreign plan.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
		}
		$this->assertFalse( $this->sut->delete_meta( $this->id, 'another-extension', 'note' ) );
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );
	}

	/**
	 * @testdox the plan owner matches exactly: a slug differing only in case reads as another extension.
	 */
	public function test_the_plan_owner_match_is_case_sensitive(): void {
		$upper = strtoupper( self::OWNER );
		$this->sut->add_meta( $this->id, self::OWNER, 'note', 'kept' );

		$this->assertNull( $this->sut->find( $this->id, $upper ) );
		$this->assertFalse( $this->sut->exists( $this->id, $upper ) );
		$this->assertFalse( $this->sut->delete_meta( $this->id, $upper, 'note' ) );
		foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
			try {
				$this->sut->{$method}( $this->id, $upper, 'note', 'theirs' );
				$this->fail( "Expected {$method}() to refuse a case variant of the owner." );
			} catch ( InvalidArgumentException $e ) {
				$this->assertStringContainsString( $upper, $e->getMessage() );
			}
		}
		$this->assertSame( array( 'kept' ), $this->sut->get_meta( $this->id, 'note' ) );

		$plan = $this->sut->find( $this->id );
		$this->assertInstanceOf( Plan::class, $plan );
		$plan->set_name( 'Renamed' );
		$this->assertTrue( $this->sut->update_fields( $plan, array( 'name' ) ), 'The exact owner still writes.' );
	}

	/**
	 * @testdox a meta key longer than the 255-character column is refused and stores nothing, in ASCII or multibyte characters; a 255-character key is stored.
	 */
	public function test_an_over_long_meta_key_is_refused(): void {
		foreach ( array( 'k', 'é' ) as $char ) {
			$too_long = str_repeat( $char, 256 );
			foreach ( array( 'add_meta', 'update_meta' ) as $method ) {
				try {
					$this->sut->{$method}( $this->id, self::OWNER, $too_long, 'value' );
					$this->fail( "Expected {$method}() to refuse an over-long key of '{$char}'." );
				} catch ( RuntimeException $e ) {
					$this->assertStringContainsString( 'is longer than the column allows', $e->getMessage(), $method );
				}
			}
			$this->assertSame( array(), $this->sut->get_meta( $this->id ) );

			$longest = str_repeat( $char, 255 );
			$this->assertIsInt( $this->sut->add_meta( $this->id, self::OWNER, $longest, 'one' ) );
			$this->sut->delete_meta( $this->id, self::OWNER, $longest );
			$this->assertTrue( $this->sut->update_meta( $this->id, self::OWNER, $longest, 'two' ) );
			$this->assertSame( array( $longest => array( 'two' ) ), $this->sut->get_meta( $this->id ) );
			$this->sut->delete_meta( $this->id, self::OWNER, $longest );
		}
	}
}

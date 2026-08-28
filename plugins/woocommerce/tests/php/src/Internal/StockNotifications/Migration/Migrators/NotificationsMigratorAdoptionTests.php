<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for natural-key adoption: a legacy row that matches an existing Core notification
 * marks that row instead of inserting a duplicate.
 *
 * The natural key is deliberately narrower than `SignupService::is_already_signed_up()`,
 * so several of these cases assert the opposite of what signup would have concluded.
 */
class NotificationsMigratorAdoptionTests extends WC_Unit_Test_Case {

	/**
	 * Migrator under test.
	 *
	 * @var NotificationsMigrator
	 */
	private NotificationsMigrator $migrator;

	/**
	 * A published simple product every seeded row points at.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up the legacy tables and a product to subscribe to.
	 */
	public function setUp(): void {
		parent::setUp();

		LegacyStore::create_tables();
		LegacyStore::truncate_all();

		// Autoloaded options survive the per-test transaction rollback through the object
		// cache, so they are cleared on the way in as well as on the way out.
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		$this->migrator   = new NotificationsMigrator( new Reporter() );
		$this->product_id = $this->create_product();
	}

	/**
	 * Drop the legacy tables and the options the migration writes.
	 */
	public function tearDown(): void {
		LegacyStore::drop_tables();
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		parent::tearDown();
	}

	/**
	 * @testdox a legacy row matching a Core notification should mark it rather than duplicate it.
	 */
	public function test_matching_core_row_is_adopted_not_duplicated(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertCount( 1, LegacyStore::get_core_rows(), 'Adoption must not insert a second row.' );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ] );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_adopted' )[ $existing ] );
	}

	/**
	 * @testdox adoption should leave the target's status, dates and meta untouched.
	 */
	public function test_adoption_leaves_the_target_untouched(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );
		$before   = LegacyStore::get_core_rows()[0];

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
				'is_active'  => 'off',
			)
		);

		$this->migrate_all();

		$after = LegacyStore::get_core_rows()[0];
		$this->assertSame( $before, $after, 'The merchant\'s own row must survive adoption byte for byte.' );
		$this->assertSame( $existing, (int) $after['id'] );
	}

	/**
	 * @testdox a cancelled Core row should not be adopted.
	 */
	public function test_cancelled_core_row_is_not_adopted(): void {
		$this->create_core_notification( 'shopper@example.com', NotificationStatus::CANCELLED );

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertCount( 2, LegacyStore::get_core_rows(), 'A cancelled row is not a signup, so a fresh row is inserted.' );
	}

	/**
	 * @testdox an active Core row should win over a cancelled one sharing the natural key.
	 */
	public function test_active_target_wins_over_cancelled(): void {
		$this->create_core_notification( 'shopper@example.com', NotificationStatus::CANCELLED );
		$active = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$this->migrate_all();

		$this->assertCount( 2, LegacyStore::get_core_rows() );
		$this->assertSame( array( $active => array( (string) $legacy_id ) ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
	}

	/**
	 * @testdox a registered legacy row should not adopt a guest Core row for the same email.
	 */
	public function test_registered_legacy_row_does_not_adopt_a_guest_core_row(): void {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'registered-guest@example.com' ) );
		$this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_id'    => $user_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertCount( 2, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a guest legacy row should not adopt a registered Core row for the same email.
	 */
	public function test_guest_legacy_row_does_not_adopt_a_registered_core_row(): void {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'guest-registered@example.com' ) );
		$this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE, $user_id );

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_id'    => 0,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertCount( 2, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a legacy address that fails is_email() should still adopt its Core counterpart.
	 */
	public function test_address_failing_is_email_still_adopts(): void {
		$address = 'shopper@localhost';
		$this->assertFalse( is_email( $address ), 'This address must fail is_email() for the test to mean anything.' );

		// Core's own validation refuses this address today, so the row is inserted directly:
		// the point is a row that already exists on the store, not one Core would create now.
		$existing = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => $address,
			)
		);

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => $address,
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ] );
	}

	/**
	 * @testdox two duplicate legacy rows in one batch should resolve to a single target.
	 */
	public function test_duplicate_legacy_rows_in_one_batch_resolve_to_one_target(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$first  = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);
		$second = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$this->migrate_all();

		$this->assertCount( 1, LegacyStore::get_core_rows() );
		$this->assertSame(
			array( (string) $first, (string) $second ),
			LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ],
			'Both legacy ids must resolve to the one target row.'
		);
	}

	/**
	 * @testdox two duplicate legacy rows in different batches should resolve to a single target.
	 */
	public function test_duplicate_legacy_rows_across_batches_resolve_to_one_target(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$first = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'other@example.com',
			)
		);

		$second = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		// One row per batch, so a within-batch dedupe would not see the pair.
		$cursor = 0;
		while ( true ) {
			$batch = $this->migrator->get_batch( $cursor, 1 );

			if ( empty( $batch ) ) {
				break;
			}

			$this->migrator->migrate_batch( $batch, wc_get_container()->get( DbWriter::class ) );
			$cursor = (int) end( $batch );
		}

		$this->assertSame(
			array( (string) $first, (string) $second ),
			LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ]
		);
	}

	/**
	 * @testdox duplicate legacy rows mapping to sent should migrate as two separate rows.
	 */
	public function test_duplicate_sent_rows_migrate_separately(): void {
		$first  = LegacyStore::add_notification(
			array(
				'product_id'         => $this->product_id,
				'user_email'         => 'shopper@example.com',
				'last_notified_date' => 1600000100,
			)
		);
		$second = LegacyStore::add_notification(
			array(
				'product_id'         => $this->product_id,
				'user_email'         => 'shopper@example.com',
				'last_notified_date' => 1600000200,
			)
		);

		foreach ( array( $first, $second ) as $legacy_id ) {
			LegacyStore::add_meta( $legacy_id, '_hash_key', str_pad( 'key-' . $legacy_id, 32, '0' ) );
			LegacyStore::add_meta( $legacy_id, '_hash_iv', str_pad( 'iv-' . $legacy_id, 16, '0' ) );
		}

		$this->migrate_all();

		$rows = LegacyStore::get_core_rows();
		$this->assertCount( 2, $rows, 'A sent row is never an adoption target, so both rows migrate.' );

		$markers = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );
		$this->assertSame( array( (string) $first ), $markers[ (int) $rows[0]['id'] ] );
		$this->assertSame( array( (string) $second ), $markers[ (int) $rows[1]['id'] ] );

		$tokens = LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' );
		$this->assertCount( 1, $tokens[ (int) $rows[0]['id'] ] );
		$this->assertCount( 1, $tokens[ (int) $rows[1]['id'] ] );
		$this->assertNotSame( $tokens[ (int) $rows[0]['id'] ][0], $tokens[ (int) $rows[1]['id'] ][0], 'Each row carries its own token.' );
	}

	/**
	 * @testdox posted_attributes should be part of the natural key.
	 */
	public function test_posted_attributes_are_part_of_the_natural_key(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);
		LegacyStore::add_meta( $legacy_id, 'posted_attributes', array( 'attribute_pa_color' => 'blue' ) );

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0, 'A different variation choice is a different signup.' );
		$this->assertCount( 2, LegacyStore::get_core_rows() );
		$this->assertArrayNotHasKey( $existing, LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
	}

	/**
	 * @testdox an adopted row loaded before adoption should see the marker afterwards.
	 */
	public function test_adopted_row_meta_cache_is_invalidated(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		// Load the target into memory first: the marker is written by direct SQL, so a stale
		// meta cache would hide it from every later read in the same request.
		$loaded = new Notification( $existing );
		$this->assertSame( '', (string) $loaded->get_meta( '_wc_bis_legacy_id' ) );

		$this->migrate_all();

		$reloaded = new Notification( $existing );
		$this->assertSame( (string) $legacy_id, (string) $reloaded->get_meta( '_wc_bis_legacy_id' ) );
	}

	/**
	 * @testdox a marker write that does not persist should fail the row instead of adopting it.
	 */
	public function test_failed_marker_write_fails_the_row(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$writer = new class() extends DbWriter {
			/**
			 * Report every marker write as lost, the way a DB error does.
			 *
			 * @param int   $notification_id Target notification id.
			 * @param array $meta            List of key/value pairs.
			 * @return int
			 */
			public function insert_notification_meta( int $notification_id, array $meta ): int {
				return 0;
			}
		};

		$outcomes = $this->migrator->migrate_batch( $this->migrator->get_batch( 0, 500 ), $writer );

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_FAILED ] ?? 0, 'A lost marker write is a row failure.' );
		$this->assertSame( 0, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertCount( 1, LegacyStore::get_legacy_meta( $legacy_id, '_wc_bis_migration_failed' ) );
		$this->assertArrayNotHasKey( $existing, LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );

		// The failure marker is what keeps the row from being re-tried forever.
		$this->assertSame( array(), $this->migrator->get_batch( 0, 500 ) );
	}

	/**
	 * Migrate every outstanding candidate row through the live writer.
	 *
	 * @return array<string,int> Outcome counts.
	 */
	private function migrate_all(): array {
		return $this->migrator->migrate_batch( $this->migrator->get_batch( 0, 500 ), wc_get_container()->get( DbWriter::class ) );
	}

	/**
	 * Create a Core notification the way a native signup would.
	 *
	 * @param string $email   Subscriber email.
	 * @param string $status  Notification status.
	 * @param int    $user_id Subscriber user id, or 0 for a guest.
	 * @return int The notification id.
	 */
	private function create_core_notification( string $email, string $status, int $user_id = 0 ): int {
		$notification = new Notification();
		$notification->set_product_id( $this->product_id );
		$notification->set_user_id( $user_id );
		$notification->set_user_email( $email );
		$notification->set_status( $status );
		$notification->save();

		return $notification->get_id();
	}

	/**
	 * Create a published simple product.
	 *
	 * @return int
	 */
	private function create_product(): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Migration test product' );
		$product->save();

		return $product->get_id();
	}
}

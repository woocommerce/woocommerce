<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
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

			$this->migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
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
		$this->assertSame( '', (string) $loaded->get_meta( '_wc_bis_legacy_id_' . $legacy_id ) );

		$this->migrate_all();

		$reloaded = new Notification( $existing );
		$this->assertSame( (string) $legacy_id, (string) $reloaded->get_meta( '_wc_bis_legacy_id_' . $legacy_id ) );
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

		$writer = new class() extends Writer {
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

		// The failure marker is what keeps the row from being re-tried forever: the scan still
		// serves it, and the batch drops it before it can adopt anything.
		$this->assertSame( array( $legacy_id ), $this->migrator->get_batch( 0, 500 ) );
		$this->assertSame( array(), $this->migrate_all() );
		$this->assertArrayNotHasKey( $existing, LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
	}

	/**
	 * @testdox adoption should match a Core address that differs only in case.
	 */
	public function test_address_case_is_not_part_of_the_natural_key(): void {
		$existing = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => ' Shopper@Example.com ',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ] );
	}

	/**
	 * @testdox a lower-case legacy address should adopt a Core row stored in mixed case.
	 */
	public function test_a_mixed_case_core_address_is_adopted(): void {
		$existing = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'Shopper@Example.com',
			)
		);

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ] );
	}

	/**
	 * @testdox an active target should win over a lower-id pending one, on every run.
	 */
	public function test_the_active_target_wins_whatever_the_ids_are(): void {
		$pending = $this->create_core_notification( 'shopper@example.com', NotificationStatus::PENDING );
		$active  = $this->create_core_notification( 'shopper@example.com', NotificationStatus::ACTIVE );

		$this->assertLessThan( $active, $pending, 'The pending target has to hold the lower id for this to mean anything.' );

		$first_id  = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);
		$second_id = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$this->migrate_all();

		$this->assertSame(
			array( (string) $first_id, (string) $second_id ),
			LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $active ],
			'Both rows adopt the active target, not the older pending one.'
		);
		$this->assertArrayNotHasKey( $pending, LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) );
	}

	/**
	 * @testdox a variation row should adopt only on a byte-identical posted_attributes.
	 */
	public function test_a_variation_row_adopts_only_on_identical_posted_attributes(): void {
		$attributes = array( 'attribute_pa_color' => 'blue' );

		$matching = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'match@example.com',
			)
		);
		LegacyStore::add_core_meta( $matching, 'posted_attributes', maybe_serialize( $attributes ) );

		$other = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'other@example.com',
			)
		);
		LegacyStore::add_core_meta( $other, 'posted_attributes', maybe_serialize( array( 'attribute_pa_color' => 'red' ) ) );

		$adopting = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'match@example.com',
			)
		);
		LegacyStore::add_meta( $adopting, 'posted_attributes', $attributes );

		$inserting = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'other@example.com',
			)
		);
		LegacyStore::add_meta( $inserting, 'posted_attributes', $attributes );

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 );

		$markers = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );

		$this->assertSame( array( (string) $adopting ), $markers[ $matching ] );
		$this->assertArrayNotHasKey( $other, $markers, 'A different variation choice is a different signup.' );
	}

	/**
	 * @testdox a legacy row with no posted_attributes should not adopt a row that has some.
	 */
	public function test_a_row_without_posted_attributes_does_not_adopt_one_with_them(): void {
		$existing = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);
		LegacyStore::add_core_meta( $existing, 'posted_attributes', maybe_serialize( array( 'attribute_pa_color' => 'blue' ) ) );

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertCount( 2, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox adoption should resolve a whole batch in a fixed number of queries.
	 */
	public function test_adoption_resolves_a_batch_in_a_fixed_number_of_queries(): void {
		$user_id = $this->factory()->user->create();

		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_core_notification( "guest{$i}@example.com", NotificationStatus::ACTIVE );
			LegacyStore::add_notification(
				array(
					'product_id' => $this->product_id,
					'user_email' => "guest{$i}@example.com",
				)
			);
		}

		$this->create_core_notification( 'registered@example.com', NotificationStatus::ACTIVE, $user_id );
		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_id'    => $user_id,
				'user_email' => 'registered@example.com',
			)
		);

		global $wpdb;
		$table   = $wpdb->prefix . 'wc_stock_notifications ';
		$queries = array();

		$counter = function ( $query ) use ( &$queries, $table ) {
			if ( false !== strpos( $query, $table ) && 0 === stripos( ltrim( $query ), 'SELECT' ) ) {
				$queries[] = $query;
			}

			return $query;
		};

		add_filter( 'query', $counter );
		$outcomes = $this->migrate_all();
		remove_filter( 'query', $counter );

		$this->assertSame( 4, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertLessThanOrEqual( 2, count( $queries ), 'One lookup per branch for the whole batch, never one per row.' );
	}

	/**
	 * @testdox an unverified legacy row adopting a pending Core row should carry its verification digest.
	 */
	public function test_unverified_row_adopting_a_pending_core_row_writes_the_verification_digest(): void {
		$existing = $this->create_core_notification( 'shopper@example.com', NotificationStatus::PENDING );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'user_email'  => 'shopper@example.com',
				'is_verified' => 'no',
				'is_active'   => 'off',
			)
		);
		LegacyStore::add_verification_data( $legacy_id, 'a-verification-code', time() );

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $existing ] );
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_adopted' )[ $existing ] );

		$stored = LegacyStore::get_core_meta( '_wc_bis_legacy_verify_hash' );

		$this->assertArrayHasKey( $existing, $stored );

		$token = LegacyHash::compute_verification( 'a-verification-code', LegacyStore::VERIFICATION_KEY, LegacyStore::VERIFICATION_IV );

		$this->assertTrue( LegacyHash::verify( $stored[ $existing ][0], (string) $token ) );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_legacy_links' ) );
	}

	/**
	 * Adoption writes markers only, never status, so an active legacy row landing on a
	 * pending Core row leaves the subscriber pending: live in the extension, not live in
	 * Core, and silent about it once the extension is deactivated. The row is the merchant's
	 * and stays as it is — but the outcome has to say so rather than report a plain success.
	 *
	 * @testdox an active legacy row adopting a pending Core row should report the downgrade.
	 */
	public function test_an_active_row_adopting_a_pending_target_reports_a_downgrade(): void {
		$pending = $this->create_core_notification( 'shopper@example.com', NotificationStatus::PENDING );

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'user_email'  => 'shopper@example.com',
				'is_active'   => 'on',
				'is_verified' => 'yes',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame(
			array( (string) $legacy_id ),
			LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $pending ],
			'The row still adopts; nothing about the data changes.'
		);
		$this->assertSame(
			1,
			$outcomes[ Reporter::OUTCOME_ADOPTED_DOWNGRADED ] ?? 0,
			'A subscriber who came out less live than they went in is not a plain adoption.'
		);
		$this->assertArrayNotHasKey(
			Reporter::OUTCOME_ADOPTED,
			$outcomes,
			'The downgrade must not also be counted as an ordinary adoption.'
		);
	}

	/**
	 * @testdox a pending legacy row adopting a pending Core row should report a plain adoption.
	 */
	public function test_a_pending_row_adopting_a_pending_target_is_not_a_downgrade(): void {
		$this->create_core_notification( 'shopper@example.com', NotificationStatus::PENDING );

		LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'user_email'  => 'shopper@example.com',
				'is_verified' => 'no',
				'is_active'   => 'off',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 );
		$this->assertArrayNotHasKey( Reporter::OUTCOME_ADOPTED_DOWNGRADED, $outcomes );
	}

	/**
	 * Migrate every outstanding candidate row through the live writer.
	 *
	 * @return array<string,int> Outcome counts.
	 */
	private function migrate_all(): array {
		return $this->migrator->migrate_batch( $this->migrator->get_batch( 0, 500 ), wc_get_container()->get( Writer::class ) );
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

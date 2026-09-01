<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Integration tests for the notifications section of the BIS migration: the candidate
 * predicate, the per-column mapping, the dry run, and the batch accounting invariant.
 */
class NotificationsMigratorTests extends WC_Unit_Test_Case {

	/**
	 * Migrator under test.
	 *
	 * @var NotificationsMigrator
	 */
	private NotificationsMigrator $migrator;

	/**
	 * Reporter shared with the migrator.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * A published simple product every seeded row points at unless it says otherwise.
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

		$this->reporter   = new Reporter();
		$this->migrator   = new NotificationsMigrator( $this->reporter );
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
	 * @testdox every row that cannot be migrated should be skipped with a recorded outcome.
	 */
	public function test_ineligible_rows_are_skipped_with_a_recorded_outcome(): void {
		$eligible = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );

		$unverified = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
				'user_email'  => 'unverified@example.com',
			)
		);

		$trashed_product = $this->create_product();
		wp_trash_post( $trashed_product );
		LegacyStore::add_notification( array( 'product_id' => $trashed_product ) );

		$page_id = $this->factory()->post->create( array( 'post_type' => 'page' ) );
		LegacyStore::add_notification( array( 'product_id' => $page_id ) );

		LegacyStore::add_notification( array( 'product_id' => 999999 ) );

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => str_repeat( 'a', 95 ) . '@example.com',
			)
		);

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'not-an-email',
			)
		);

		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => '',
			)
		);

		$batch = $this->migrator->get_batch( 0, 100 );

		$this->assertCount( 8, $batch, 'The scan serves every row; candidacy is decided in migrate_batch().' );

		$outcomes = $this->migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );

		$this->assertSame( 2, $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0, 'An unverified row is a candidate, not a loss.' );
		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_EMAIL_TOO_LONG ] ?? 0 );
		$this->assertSame( 2, $outcomes[ Reporter::OUTCOME_INVALID_EMAIL ] ?? 0, 'The empty and malformed addresses are both invalid.' );
		$this->assertSame( 3, $outcomes[ Reporter::OUTCOME_PRODUCT_MISSING ] ?? 0, 'Trashed, non-product and missing products all count.' );
		$this->assertSame( count( $batch ), array_sum( $outcomes ), 'Every row the scan serves has to leave the batch with an outcome.' );

		$this->assertCount( 2, LegacyStore::get_core_rows(), 'Only the two eligible rows are inserted.' );
		$this->assertSame(
			array( (string) $eligible, (string) $unverified ),
			array_merge( ...array_values( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) ) )
		);
	}

	/**
	 * @testdox an already migrated row should be skipped again on a re-run, without an outcome.
	 */
	public function test_an_already_migrated_row_is_skipped_without_an_outcome(): void {
		$legacy_id = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );

		$this->migrate_all();
		$this->assertCount( 1, LegacyStore::get_core_rows() );

		$outcomes = $this->migrator->migrate_batch( array( $legacy_id ), wc_get_container()->get( Writer::class ) );

		$this->assertSame( array(), $outcomes, 'A settled row is not a loss, so it records nothing.' );
		$this->assertCount( 1, LegacyStore::get_core_rows(), 'A re-run must not insert the row again.' );
	}

	/**
	 * @testdox a row marked permanently failed should be skipped again, without an outcome.
	 */
	public function test_a_failed_row_is_skipped_without_an_outcome(): void {
		$legacy_id = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );
		LegacyStore::add_meta( $legacy_id, '_wc_bis_migration_failed', array( 'reason' => 'exception' ) );

		$outcomes = $this->migrator->migrate_batch( array( $legacy_id ), wc_get_container()->get( Writer::class ) );

		$this->assertSame( array(), $outcomes );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox the skip outcomes should include unverified rows, now that they are candidates.
	 */
	public function test_skip_outcomes_include_unverified_rows(): void {
		LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'user_email'  => str_repeat( 'a', 95 ) . '@example.com',
			)
		);
		LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'user_email'  => 'not-an-email',
			)
		);
		LegacyStore::add_notification(
			array(
				'product_id'  => 999999,
				'is_verified' => 'no',
			)
		);

		$outcomes = $this->migrate_all();

		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_EMAIL_TOO_LONG ] ?? 0 );
		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_INVALID_EMAIL ] ?? 0 );
		$this->assertSame( 1, $outcomes[ Reporter::OUTCOME_PRODUCT_MISSING ] ?? 0 );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox a verified row should never land in the pending status.
	 */
	public function test_a_verified_row_never_lands_in_pending(): void {
		LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );
		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'is_active'  => 'off',
				'user_email' => 'cancelled@example.com',
			)
		);
		LegacyStore::add_notification(
			array(
				'product_id'         => $this->product_id,
				'last_notified_date' => 1600000100,
				'user_email'         => 'sent@example.com',
			)
		);

		$this->migrate_all();

		foreach ( LegacyStore::get_core_rows() as $row ) {
			$this->assertNotSame( NotificationStatus::PENDING, $row['status'] );
		}
	}

	/**
	 * @testdox get_batch should be side-effect free.
	 */
	public function test_get_batch_is_side_effect_free(): void {
		$first  = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );
		$second = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'second@example.com',
			)
		);

		$this->assertSame( array( $first, $second ), $this->migrator->get_batch( 0, 100 ) );
		$this->assertSame( array( $first, $second ), $this->migrator->get_batch( 0, 100 ) );
		$this->assertSame( 2, $this->migrator->count_remaining() );
		$this->assertSame( 0, $this->migrator->count_remaining( $second ), 'The count is of rows above the cursor.' );
	}

	/**
	 * @testdox a dry run should write nothing while reporting the same outcomes as a real run.
	 */
	public function test_dry_run_writes_nothing_and_reports_the_same_shape(): void {
		$ids = array(
			LegacyStore::add_notification( array( 'product_id' => $this->product_id ) ),
			LegacyStore::add_notification(
				array(
					'product_id' => $this->product_id,
					'user_email' => 'second@example.com',
					'is_active'  => 'off',
				)
			),
		);

		$dry_outcomes = $this->migrator->migrate_batch( $ids, new Writer( true ) );

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'A dry run must not insert Core rows.' );
		$this->assertFalse( get_option( 'wc_bis_migration_has_migrated_rows' ), 'A dry run must not write options.' );
		$this->assertSame( array( $ids[0], $ids[1] ), $this->migrator->get_batch( 0, 100 ), 'A dry run must leave every row outstanding.' );
		$this->assertSame( 2, $this->migrator->count_remaining() );

		$real_migrator = new NotificationsMigrator( new Reporter() );
		$real_outcomes = $real_migrator->migrate_batch( $ids, wc_get_container()->get( Writer::class ) );

		$this->assertSame( $dry_outcomes, $real_outcomes, 'The dry run report must be shape-identical to the real one.' );
	}

	/**
	 * @testdox a migrated row should carry the mapped Core column values.
	 */
	public function test_migrated_row_carries_mapped_columns(): void {
		$user_id = $this->factory()->user->create();

		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'     => $this->product_id,
				'user_id'        => $user_id,
				'user_email'     => '  Shopper@Example.com ',
				'create_date'    => 1600000000,
				'subscribe_date' => 1600000500,
			)
		);
		LegacyStore::add_meta( $legacy_id, '_customer_locale', 'el_GR' );
		LegacyStore::add_meta( $legacy_id, 'posted_attributes', array( 'attribute_pa_color' => 'blue' ) );

		$this->migrate_all();

		$rows = LegacyStore::get_core_rows();
		$this->assertCount( 1, $rows );

		$row = $rows[0];
		$this->assertSame( $this->product_id, (int) $row['product_id'] );
		$this->assertSame( $user_id, (int) $row['user_id'] );
		$this->assertSame( 'shopper@example.com', $row['user_email'], 'The email should be trimmed and lowercased.' );
		$this->assertSame( NotificationStatus::ACTIVE, $row['status'] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', 1600000000 ), $row['date_created_gmt'] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', 1600000500 ), $row['date_confirmed_gmt'] );
		$this->assertNull( $row['date_last_attempt_gmt'] );
		$this->assertNull( $row['date_notified_gmt'] );
		$this->assertNull( $row['date_cancelled_gmt'] );
		$this->assertNull( $row['cancellation_source'] );

		$notification_id = (int) $row['id'];
		$this->assertSame( array( 'el_GR' ), LegacyStore::get_core_meta( '_customer_locale' )[ $notification_id ] );
		$this->assertSame(
			array( maybe_serialize( array( 'attribute_pa_color' => 'blue' ) ) ),
			LegacyStore::get_core_meta( 'posted_attributes' )[ $notification_id ],
			'posted_attributes must be serialized exactly once, by the writer.'
		);
		$this->assertSame( array( (string) $legacy_id ), LegacyStore::get_core_meta( '_wc_bis_legacy_id' )[ $notification_id ] );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_migrated_rows' ) );
	}

	/**
	 * @testdox a delivered row should map to sent and carry its delivery clocks.
	 */
	public function test_delivered_row_maps_to_sent_with_its_clocks(): void {
		LegacyStore::add_notification(
			array(
				'product_id'         => $this->product_id,
				'create_date'        => 1600000000,
				'subscribe_date'     => 1600000100,
				'last_notified_date' => 1600000200,
			)
		);

		$this->migrate_all();

		$row = LegacyStore::get_core_rows()[0];
		$this->assertSame( NotificationStatus::SENT, $row['status'] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', 1600000200 ), $row['date_notified_gmt'] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', 1600000200 ), $row['date_last_attempt_gmt'] );
	}

	/**
	 * @testdox a cancelled row should take its source and date from the activity log.
	 */
	public function test_cancelled_row_takes_source_and_date_from_activity(): void {
		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_active'   => 'off',
				'create_date' => 1600000000,
			)
		);
		LegacyStore::add_activity( $legacy_id, 'unsubscribed', 1600009999 );

		$this->migrate_all();

		$row = LegacyStore::get_core_rows()[0];
		$this->assertSame( NotificationStatus::CANCELLED, $row['status'] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', 1600009999 ), $row['date_cancelled_gmt'] );
		$this->assertSame( NotificationCancellationSource::USER, $row['cancellation_source'] );
	}

	/**
	 * @testdox a cancelled row with no activity should fall back to the system source.
	 */
	public function test_cancelled_row_without_activity_falls_back_to_system(): void {
		LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'is_active'  => 'off',
			)
		);

		$this->migrate_all();

		$row = LegacyStore::get_core_rows()[0];
		$this->assertSame( NotificationStatus::CANCELLED, $row['status'] );
		$this->assertSame( NotificationCancellationSource::SYSTEM, $row['cancellation_source'] );
	}

	/**
	 * @testdox every admitted row should leave the batch as migrated, adopted or failed.
	 */
	public function test_admitted_rows_equal_migrated_plus_adopted_plus_failed(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			LegacyStore::add_notification(
				array(
					'product_id' => $this->product_id,
					'user_email' => "shopper{$i}@example.com",
				)
			);
		}

		$admitted = $this->migrator->get_batch( 0, 100 );
		$this->assertCount( 5, $admitted );

		$outcomes = $this->migrator->migrate_batch( $admitted, wc_get_container()->get( Writer::class ) );

		$accounted = ( $outcomes[ Reporter::OUTCOME_MIGRATED ] ?? 0 )
			+ ( $outcomes[ Reporter::OUTCOME_ADOPTED ] ?? 0 )
			+ ( $outcomes[ Reporter::OUTCOME_FAILED ] ?? 0 );

		$this->assertSame( count( $admitted ), $accounted, 'No row may leave a batch by a third route.' );
		$this->assertSame( 0, $this->migrator->count_remaining( (int) max( $admitted ) ) );
	}

	/**
	 * @testdox the legacy meta table should be read a fixed number of times per batch, not once per row.
	 */
	public function test_legacy_meta_is_read_once_per_batch(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$legacy_id = LegacyStore::add_notification(
				array(
					'product_id' => $this->product_id,
					'user_email' => "shopper{$i}@example.com",
				)
			);
			LegacyStore::add_meta( $legacy_id, '_customer_locale', 'en_US' );
		}

		$batch = $this->migrator->get_batch( 0, 100 );

		global $wpdb;
		$meta_table = $wpdb->prefix . 'woocommerce_bis_notificationsmeta';
		$queries    = 0;

		$counter = function ( $query ) use ( &$queries, $meta_table ) {
			if ( false !== strpos( $query, $meta_table ) && 0 === stripos( ltrim( $query ), 'SELECT' ) ) {
				++$queries;
			}

			return $query;
		};

		add_filter( 'query', $counter );
		$this->migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
		remove_filter( 'query', $counter );

		$this->assertSame( 2, $queries, 'One failure-marker lookup and one meta fetch, both for the whole batch.' );
	}

	/**
	 * @testdox get_batch should honour the cursor and the batch size.
	 */
	public function test_get_batch_honours_cursor_and_size(): void {
		$ids = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$ids[] = LegacyStore::add_notification(
				array(
					'product_id' => $this->product_id,
					'user_email' => "shopper{$i}@example.com",
				)
			);
		}

		$this->assertSame( array_slice( $ids, 0, 2 ), $this->migrator->get_batch( 0, 2 ) );
		$this->assertSame( array_slice( $ids, 2, 2 ), $this->migrator->get_batch( $ids[1], 2 ) );
		$this->assertSame( array(), $this->migrator->get_batch( end( $ids ), 2 ) );
	}

	/**
	 * @testdox an unverified row should migrate as pending, with no confirmation date.
	 */
	public function test_an_unverified_row_migrates_as_pending(): void {
		LegacyStore::add_notification(
			array(
				'product_id'     => $this->product_id,
				'is_verified'    => 'no',
				'is_active'      => 'off',
				'subscribe_date' => 0,
			)
		);

		$this->migrate_all();

		$rows = LegacyStore::get_core_rows();

		$this->assertCount( 1, $rows );
		$this->assertSame( NotificationStatus::PENDING, $rows[0]['status'] );
		$this->assertNull( $rows[0]['date_confirmed_gmt'], 'A row that never completed verification has nothing to confirm.' );
	}

	/**
	 * @testdox an unverified row with a cancelling event should migrate as cancelled, not pending.
	 */
	public function test_an_unverified_row_with_a_deactivated_event_migrates_as_cancelled(): void {
		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
			)
		);
		LegacyStore::add_activity( $legacy_id, 'deactivated', 1600009999 );

		$this->migrate_all();

		$rows = LegacyStore::get_core_rows();

		$this->assertCount( 1, $rows );
		$this->assertSame( NotificationStatus::CANCELLED, $rows[0]['status'] );
	}

	/**
	 * @testdox a pending row should carry its legacy verification token digest, never the secrets.
	 */
	public function test_a_pending_row_carries_the_verification_digest_and_not_the_secrets(): void {
		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
			)
		);
		LegacyStore::add_verification_data( $legacy_id, 'a-verification-code', time() );

		$this->migrate_all();

		$stored = LegacyStore::get_core_meta( '_wc_bis_legacy_verify_hash_' . $legacy_id );
		$rows   = LegacyStore::get_core_rows();

		$this->assertArrayHasKey( (int) $rows[0]['id'], $stored );

		$meta_value = $stored[ (int) $rows[0]['id'] ][0];
		$parsed     = LegacyHash::parse( $meta_value );

		$this->assertNotNull( $parsed );
		$this->assertNotNull( $parsed[1], 'A verification digest is stored with its expiry.' );

		$token = LegacyHash::compute_verification( 'a-verification-code', LegacyStore::VERIFICATION_KEY, LegacyStore::VERIFICATION_IV );

		$this->assertTrue( LegacyHash::verify( $meta_value, (string) $token ) );
		$this->assertSame( 'yes', get_option( 'wc_bis_migration_has_legacy_links' ) );

		foreach ( array( '_verification_code', '_verification_key', '_verification_iv', '_verification_created_at' ) as $secret ) {
			$this->assertSame( array(), LegacyStore::get_core_meta( $secret ), "The legacy {$secret} secret must not reach Core meta." );
		}
	}

	/**
	 * @testdox the verification digest should be written only for an unexpired pending row.
	 */
	public function test_the_verification_digest_is_skipped_when_it_cannot_be_honoured(): void {
		$expired_row = LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
				'user_email'  => 'expired@example.com',
			)
		);
		LegacyStore::add_verification_data( $expired_row, 'a-verification-code', time() - ( 2 * HOUR_IN_SECONDS ) );

		LegacyStore::add_notification(
			array(
				'product_id'  => $this->product_id,
				'is_verified' => 'no',
				'is_active'   => 'off',
				'user_email'  => 'no-secrets@example.com',
			)
		);

		$active_row = LegacyStore::add_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'active@example.com',
			)
		);
		LegacyStore::add_verification_data( $active_row, 'a-verification-code', time() );

		$this->migrate_all();

		$this->assertCount( 3, LegacyStore::get_core_rows(), 'All three rows migrate; only the digest is withheld.' );
		$this->assertSame( array(), LegacyStore::get_core_meta( '_wc_bis_legacy_verify_hash' ) );
	}

	/**
	 * The flag registers the legacy link shim on every front-end request, and the shim
	 * answers legacy links itself instead of leaving them to the still-active extension. A
	 * batch that rolled back has migrated nothing, so setting the flag from that batch would
	 * turn the shim on with no migrated row behind it for any link to resolve to. The option
	 * write does not join the insert's transaction, so ordering is the only thing protecting
	 * this.
	 *
	 * @testdox a batch that rolls back should not leave the legacy-link shim switched on.
	 */
	public function test_a_rolled_back_batch_does_not_set_the_legacy_links_flag(): void {
		$legacy_id = LegacyStore::add_notification( array( 'product_id' => $this->product_id ) );

		// The secrets are what make the row carry a legacy unsubscribe token, and so what
		// would set the flag.
		LegacyStore::add_meta( $legacy_id, '_hash_key', str_pad( 'key', 32, '0' ) );
		LegacyStore::add_meta( $legacy_id, '_hash_iv', str_pad( 'iv', 16, '0' ) );

		$this->assertFalse( get_option( 'wc_bis_migration_has_legacy_links' ) );

		// Fail the insert itself, so the whole batch rolls back after build_meta() has
		// already seen a row carrying a legacy token.
		$thrower = static function ( $query ) {
			if ( false !== stripos( $query, 'INSERT INTO' ) && false !== stripos( $query, 'stock_notifications' ) ) {
				throw new \RuntimeException( 'forced insert failure' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );

		$thrown = null;

		try {
			$this->migrate_all();
		} catch ( \Throwable $e ) {
			$thrown = $e;
		} finally {
			remove_filter( 'query', $thrower );
		}

		$this->assertNotNull( $thrown, 'The batch was supposed to fail.' );
		$this->assertSame( array(), LegacyStore::get_core_rows(), 'The batch must have rolled back.' );
		$this->assertFalse(
			get_option( 'wc_bis_migration_has_legacy_links' ),
			'A batch that migrated nothing must not register the link shim.'
		);
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

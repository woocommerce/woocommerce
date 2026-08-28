<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Compat;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat\LegacyUnsubscribeShim;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the shim that keeps legacy `bis_unsub` email links working after migration.
 *
 * Links are built the way the legacy extension built them - the token is the sha256 of an
 * AES-256-CBC encryption of `{id}-{product}-{create_date}`, base64-encoded into the query
 * string - so these exercise the real URL shape rather than a hand-made one.
 */
class LegacyUnsubscribeShimTests extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * A 32-byte key, as the legacy extension stored per notification.
	 *
	 * @var string
	 */
	private const HASH_KEY = '0123456789abcdef0123456789abcdef';

	/**
	 * A 16-byte IV, as the legacy extension stored per notification.
	 *
	 * @var string
	 */
	private const HASH_IV = 'fedcba9876543210';

	/**
	 * Legacy `create_date` shared by the seeded rows.
	 *
	 * @var int
	 */
	private const CREATE_DATE = 1600000000;

	/**
	 * Shim under test.
	 *
	 * @var LegacyUnsubscribeShim
	 */
	private LegacyUnsubscribeShim $shim;

	/**
	 * A published simple product the seeded rows point at.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up the legacy tables, the feature toggle and the shim.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		LegacyStore::create_tables();
		LegacyStore::truncate_all();
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		$this->shim       = wc_get_container()->get( LegacyUnsubscribeShim::class );
		$this->product_id = $this->create_product();

		wc_clear_notices();
	}

	/**
	 * Clear the request state, the legacy tables and the options.
	 */
	public function tearDown(): void {
		unset( $_GET['bis_unsub'], $_GET['bis_unsub_id'], $_GET['bis_unsub_ref'] );

		wc_clear_notices();
		LegacyStore::drop_tables();
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );

		parent::tearDown();
	}

	/**
	 * @testdox a migrated row should be cancelled by its own legacy link.
	 */
	public function test_legacy_link_cancels_the_migrated_row(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );

		$row = LegacyStore::get_core_rows()[0];
		$this->assertSame( NotificationStatus::CANCELLED, $row['status'] );
		$this->assertSame( NotificationCancellationSource::USER, $row['cancellation_source'] );
	}

	/**
	 * @testdox the stored meta should hold a digest of the token, never the token itself.
	 */
	public function test_stored_meta_is_a_digest_not_the_raw_token(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$token  = $this->legacy_token( $legacy_id );
		$stored = LegacyStore::get_core_meta( '_wc_bis_legacy_unsub_hash' );
		$value  = reset( $stored )[0];

		$this->assertStringStartsWith( $legacy_id . ':', $value );
		$this->assertStringNotContainsString( $token, $value, 'The raw token must never be stored.' );
		$this->assertTrue( wp_verify_fast_hash( $token, substr( $value, strlen( $legacy_id . ':' ) ) ) );
	}

	/**
	 * @testdox a pre-1.2.0 link should get the generic notice and change nothing.
	 */
	public function test_pre_1_2_0_link_gets_the_generic_notice(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		// Links from before 1.2.0 carried no `bis_unsub_id`.
		$this->assertTrue( $this->request( 0, $this->legacy_token( $legacy_id ), 'notification' ) );

		$this->assert_stale_link_notice();
		$this->assertSame( NotificationStatus::ACTIVE, LegacyStore::get_core_rows()[0]['status'] );
	}

	/**
	 * @testdox a confirmation link should cancel every row for the same product and email.
	 */
	public function test_confirmation_link_is_product_scoped(): void {
		$first  = $this->seed_legacy_row( 'shopper@example.com' );
		$second = $this->seed_legacy_row( 'shopper@example.com', $this->create_product() );
		$this->migrate();

		$this->assertTrue( $this->request( $first, $this->legacy_token( $first ), 'confirmation' ) );

		$statuses = $this->statuses_by_legacy_id();

		$this->assertSame( NotificationStatus::CANCELLED, $statuses[ $first ] );
		$this->assertSame( NotificationStatus::ACTIVE, $statuses[ $second ], 'A different product is out of scope.' );
	}

	/**
	 * @testdox a guest notification link should cancel every legacy row for that email.
	 */
	public function test_guest_notification_link_is_email_scoped(): void {
		$first  = $this->seed_legacy_row( 'shopper@example.com' );
		$second = $this->seed_legacy_row( 'shopper@example.com', $this->create_product() );
		$other  = $this->seed_legacy_row( 'someone-else@example.com' );
		$this->migrate();

		$this->assertTrue( $this->request( $first, $this->legacy_token( $first ), 'notification' ) );

		$statuses = $this->statuses_by_legacy_id();

		$this->assertSame( NotificationStatus::CANCELLED, $statuses[ $first ] );
		$this->assertSame( NotificationStatus::CANCELLED, $statuses[ $second ] );
		$this->assertSame( NotificationStatus::ACTIVE, $statuses[ $other ], 'Another address is out of scope.' );
	}

	/**
	 * @testdox a native Core notification should never be cancelled by a legacy link.
	 */
	public function test_native_core_notification_is_not_swept_in(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$native = new Notification();
		$native->set_product_id( $this->create_product() );
		$native->set_user_email( 'shopper@example.com' );
		$native->set_status( NotificationStatus::ACTIVE );
		$native->save();

		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );

		$reloaded = new Notification( $native->get_id() );
		$this->assertSame( NotificationStatus::ACTIVE, $reloaded->get_status(), 'A native signup carries its own link.' );
	}

	/**
	 * @testdox editing the Core created date should not break the link.
	 */
	public function test_editing_the_core_created_date_does_not_break_the_link(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$notification = new Notification( (int) LegacyStore::get_core_rows()[0]['id'] );
		$notification->set_date_created( time() );
		$notification->save();

		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );

		$this->assertSame( NotificationStatus::CANCELLED, LegacyStore::get_core_rows()[0]['status'] );
	}

	/**
	 * @testdox both links of an adopted duplicate pair should work, and neither token should cross over.
	 */
	public function test_adopted_duplicates_keep_both_links_and_reject_a_crossed_token(): void {
		$first  = $this->seed_legacy_row( 'shopper@example.com' );
		$second = $this->seed_legacy_row( 'shopper@example.com' );

		// One row per batch: the first has to be inserted before the second can adopt it.
		$this->migrate( 1 );

		$this->assertCount( 1, LegacyStore::get_core_rows(), 'The duplicate pair collapses onto one row.' );

		// A token minted for one legacy id, presented with the other's id, must not verify.
		$this->assertTrue( $this->request( $second, $this->legacy_token( $first ), 'notification' ) );
		$this->assert_stale_link_notice();
		$this->assertSame( NotificationStatus::ACTIVE, LegacyStore::get_core_rows()[0]['status'] );

		wc_clear_notices();

		$this->assertTrue( $this->request( $second, $this->legacy_token( $second ), 'notification' ) );
		$this->assertSame( NotificationStatus::CANCELLED, LegacyStore::get_core_rows()[0]['status'] );
	}

	/**
	 * @testdox an unknown id, a tampered token and an already-cancelled row should all read the same.
	 */
	public function test_every_stale_cause_produces_the_same_response(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$this->assertTrue( $this->request( 999999, $this->legacy_token( $legacy_id ), 'notification' ) );
		$unknown_id_notices = wc_get_notices();
		wc_clear_notices();

		$this->assertTrue( $this->request( $legacy_id, 'tampered-token', 'notification' ) );
		$tampered_notices = wc_get_notices();
		wc_clear_notices();

		// An already-cancelled row resolves and verifies, but has nothing left to cancel.
		$this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' );
		wc_clear_notices();
		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );
		$already_cancelled_notices = wc_get_notices();

		$this->assertSame( $unknown_id_notices, $tampered_notices, 'The cause must not be distinguishable.' );
		$this->assertNotEmpty( $already_cancelled_notices, 'An already-cancelled row still gets a notice.' );
	}

	/**
	 * @testdox cancelling should fire the lifecycle action once, and not on the second click.
	 */
	public function test_cancelling_fires_the_lifecycle_action_once(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$fired    = 0;
		$listener = static function () use ( &$fired ) {
			++$fired;
		};

		add_action( 'woocommerce_customer_stock_notifications_cancelled', $listener );

		$this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' );
		wc_clear_notices();
		$this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' );

		remove_action( 'woocommerce_customer_stock_notifications_cancelled', $listener );

		$this->assertSame( 1, $fired, 'The already-cancelled row must not fire it again.' );
	}

	/**
	 * @testdox a second click on a cancelled row should change nothing about it.
	 */
	public function test_a_second_click_leaves_the_cancelled_row_untouched(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		$this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' );
		wc_clear_notices();

		$after_first = LegacyStore::get_core_rows()[0];
		$this->assertSame( NotificationStatus::CANCELLED, $after_first['status'] );

		$writes   = 0;
		$recorder = function ( $query ) use ( &$writes ) {
			if ( 0 === stripos( ltrim( (string) $query ), 'UPDATE' ) && false !== stripos( (string) $query, 'wc_stock_notifications' ) ) {
				++$writes;
			}

			return $query;
		};

		add_filter( 'query', $recorder );
		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );
		remove_filter( 'query', $recorder );

		// The customer still gets a notice, but nothing behind it moves: not the cancellation
		// date, not the modified date, not the source, and no write at all.
		$this->assertSame( $after_first, LegacyStore::get_core_rows()[0] );
		$this->assertSame( 0, $writes, 'An already-cancelled row must not be written again.' );
	}

	/**
	 * @testdox a padded base64 token should survive the link's url encoding.
	 */
	public function test_padded_token_survives_the_links_url_encoding(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- reproducing the legacy link format.
		$encoded = base64_encode( $this->legacy_token( $legacy_id ) );

		$this->assertStringEndsWith( '=', $encoded, 'This fixture needs a padded token.' );

		// PHP has already decoded the query string by the time the shim reads $_GET, so what
		// the shim receives is what the link's urlencode() round-tripped to.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- the legacy link used urlencode(), so the round trip has to as well.
		$round_tripped = urldecode( urlencode( $encoded ) );

		$this->assertTrue( $this->request_raw( $legacy_id, $round_tripped, 'notification' ) );
		$this->assertSame( NotificationStatus::CANCELLED, LegacyStore::get_core_rows()[0]['status'] );
	}

	/**
	 * @testdox a real legacy token should never encode to a + or / that URL parsing could mangle.
	 */
	public function test_a_real_token_never_encodes_to_plus_or_slash(): void {
		// The legacy token is a sha256 hex digest, so its base64 form is drawn from a
		// 16-character input alphabet that cannot reach base64 indices 62 (+) and 63 (/).
		// The plan asks for a + and / round trip; this asserts why no real link can carry
		// one, so a future reader does not add URL-safe base64 handling for a case that
		// cannot occur. The synthetic double-encoded token below covers the escaping path.
		$legacy_ids = array();

		for ( $i = 0; $i < 10; $i++ ) {
			$legacy_ids[] = $this->seed_legacy_row( "shopper{$i}@example.com" );
		}

		foreach ( $legacy_ids as $legacy_id ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- reproducing the legacy link format.
			$encoded = base64_encode( $this->legacy_token( $legacy_id ) );

			$this->assertMatchesRegularExpression( '/^[A-Za-z0-9=]+$/', $encoded );
		}

		$this->migrate();

		$first = $legacy_ids[0];
		$this->assertTrue( $this->request( $first, $this->legacy_token( $first ), 'notification' ) );
		$this->assertSame( NotificationStatus::CANCELLED, $this->statuses_by_legacy_id()[ $first ] );
	}

	/**
	 * @testdox the second urldecode() after base64_decode() should not be deletable as redundant.
	 */
	public function test_second_urldecode_after_base64_decode_is_required(): void {
		global $wpdb;

		$legacy_id       = 555555;
		$notification_id = LegacyStore::add_core_notification(
			array(
				'product_id' => $this->product_id,
				'user_email' => 'shopper@example.com',
			)
		);
		$meta_table      = $wpdb->prefix . 'wc_stock_notificationmeta';

		// A token containing characters urlencode() must percent-escape, reproducing legacy's
		// own double-encoding of the `bis_unsub` parameter (plan: "Input handling").
		$token = 'legacy token/value+needs escaping';

		$wpdb->insert(
			$meta_table,
			array(
				'notification_id' => $notification_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'        => '_wc_bis_legacy_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'      => (string) $legacy_id,
			)
		);
		$wpdb->insert(
			$meta_table,
			array(
				'notification_id' => $notification_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'        => '_wc_bis_legacy_unsub_hash',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'      => LegacyHash::to_meta_value( $legacy_id, $token ),
			)
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- reproducing the legacy link's own double-encoding.
		$token_raw = base64_encode( urlencode( $token ) );

		// Prove the fixture actually needs the second urldecode(): base64_decode() alone -
		// what the shim would produce if that line were deleted - does not recover the token.
		$this->assertNotSame( $token, base64_decode( $token_raw ), 'Fixture must require urldecode() to recover the real token.' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- asserting against the shim's own decode step.

		$this->assertTrue( $this->request_raw( $legacy_id, $token_raw, 'notification' ) );
		$this->assertSame( NotificationStatus::CANCELLED, LegacyStore::get_core_rows()[0]['status'] );
	}

	/**
	 * @testdox the feature toggle being off should get the generic notice, never a fatal out of Notification::__construct().
	 */
	public function test_feature_toggle_off_gets_the_generic_notice(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );

		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );

		$this->assert_stale_link_notice();
		$this->assertSame( NotificationStatus::ACTIVE, LegacyStore::get_core_rows()[0]['status'] );

		// The customer sees a stale link either way, so the reason only survives in the log.
		$this->assertLogged( 'warning', sprintf( 'could not be loaded for legacy id %d', $legacy_id ) );
	}

	/**
	 * @testdox a `bis_unsub` link should still get the generic notice once the legacy links flag and hash meta are gone.
	 */
	public function test_generic_notice_survives_the_legacy_meta_being_removed(): void {
		$legacy_id = $this->seed_legacy_row( 'shopper@example.com' );
		$this->migrate();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$wpdb->delete( $wpdb->prefix . 'wc_stock_notificationmeta', array( 'meta_key' => '_wc_bis_legacy_unsub_hash' ) );
		delete_option( 'wc_bis_migration_has_legacy_links' );

		$this->assertTrue( $this->request( $legacy_id, $this->legacy_token( $legacy_id ), 'notification' ) );

		$this->assert_stale_link_notice();
		$this->assertSame( NotificationStatus::ACTIVE, LegacyStore::get_core_rows()[0]['status'], 'Nothing should be cancelled once resolution is impossible.' );
	}

	/**
	 * Seed a legacy row carrying both hash secrets.
	 *
	 * @param string   $email      Subscriber email.
	 * @param int|null $product_id Product the row points at, defaulting to the shared one.
	 * @return int The legacy id.
	 */
	private function seed_legacy_row( string $email, ?int $product_id = null ): int {
		$legacy_id = LegacyStore::add_notification(
			array(
				'product_id'  => $product_id ?? $this->product_id,
				'user_email'  => $email,
				'create_date' => self::CREATE_DATE,
			)
		);

		LegacyStore::add_meta( $legacy_id, '_hash_key', self::HASH_KEY );
		LegacyStore::add_meta( $legacy_id, '_hash_iv', self::HASH_IV );

		return $legacy_id;
	}

	/**
	 * Reproduce the legacy extension's `WC_BIS_Notification_Data::get_hash()`.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return string
	 */
	private function legacy_token( int $legacy_id ): string {
		global $wpdb;

		// Table name is $wpdb->prefix-based, never user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$product_id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT product_id FROM {$wpdb->prefix}woocommerce_bis_notifications WHERE id = %d", $legacy_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$input     = "{$legacy_id}-{$product_id}-" . self::CREATE_DATE;
		$encrypted = openssl_encrypt( $input, 'AES-256-CBC', self::HASH_KEY, 0, self::HASH_IV );

		return hash( 'sha256', $encrypted );
	}

	/**
	 * Migrate every outstanding legacy row.
	 *
	 * @param int $batch_size Rows per batch.
	 * @return void
	 */
	private function migrate( int $batch_size = 100 ): void {
		$migrator = new NotificationsMigrator( new Reporter() );
		$cursor   = 0;

		while ( true ) {
			$batch = $migrator->get_batch( $cursor, $batch_size );

			if ( empty( $batch ) ) {
				break;
			}

			$migrator->migrate_batch( $batch, wc_get_container()->get( DbWriter::class ) );
			$cursor = (int) end( $batch );
		}
	}

	/**
	 * Drive one legacy unsubscribe request, base64-encoding the token as the link does.
	 *
	 * @param int    $legacy_id Value for `bis_unsub_id`; 0 omits the parameter entirely.
	 * @param string $token     Raw legacy token.
	 * @param string $ref       Value for `bis_unsub_ref`.
	 * @return bool True when the shim redirected, which every terminal outcome does.
	 */
	private function request( int $legacy_id, string $token, string $ref ): bool {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- reproducing the legacy link format.
		return $this->request_raw( $legacy_id, base64_encode( $token ), $ref );
	}

	/**
	 * Drive one legacy unsubscribe request with an already-encoded token value.
	 *
	 * @param int    $legacy_id     Value for `bis_unsub_id`; 0 omits the parameter entirely.
	 * @param string $encoded_token Value as PHP would hand it over in `$_GET`.
	 * @param string $ref           Value for `bis_unsub_ref`.
	 * @return bool True when the shim redirected.
	 */
	private function request_raw( int $legacy_id, string $encoded_token, string $ref ): bool {
		$_GET['bis_unsub']     = $encoded_token;
		$_GET['bis_unsub_ref'] = $ref;

		if ( $legacy_id > 0 ) {
			$_GET['bis_unsub_id'] = (string) $legacy_id;
		} else {
			unset( $_GET['bis_unsub_id'] );
		}

		$redirected = false;

		// The shim redirects and exits; throwing from the filter stops it before the exit.
		$catcher = static function () {
			throw new \RuntimeException( 'redirected' );
		};

		add_filter( 'wp_redirect', $catcher );

		try {
			$this->shim->maybe_process_legacy_unsubscribe();
		} catch ( \RuntimeException $exception ) {
			$redirected = 'redirected' === $exception->getMessage();
		} finally {
			remove_filter( 'wp_redirect', $catcher );
		}

		return $redirected;
	}

	/**
	 * Map each migrated Core row's status by the legacy id it carries.
	 *
	 * @return array<int,string>
	 */
	private function statuses_by_legacy_id(): array {
		$statuses = array();
		$rows     = array();

		foreach ( LegacyStore::get_core_rows() as $row ) {
			$rows[ (int) $row['id'] ] = $row['status'];
		}

		foreach ( LegacyStore::get_core_meta( '_wc_bis_legacy_id' ) as $notification_id => $legacy_ids ) {
			foreach ( $legacy_ids as $legacy_id ) {
				$statuses[ (int) $legacy_id ] = $rows[ $notification_id ];
			}
		}

		return $statuses;
	}

	/**
	 * Assert the uniform stale-link notice was added.
	 *
	 * @return void
	 */
	private function assert_stale_link_notice(): void {
		$notices = wc_get_notices();
		$texts   = wp_list_pluck( $notices['notice'] ?? array(), 'notice' );

		$this->assertContains(
			'This unsubscribe link is invalid or has expired.',
			$texts,
			'The stale-link message belongs under the notice type, not success.'
		);
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

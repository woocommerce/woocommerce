<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncBackfill;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the {@see WCEmailTemplateSyncTracker} class.
 *
 * Verifies the shared payload shape, the suppress-during-backfill gate, and the
 * per-`(post_id, template_version_to)` dedup transient that guarantees the
 * `_update_available` event fires at most once per tuple.
 */
class WCEmailTemplateSyncTrackerTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory shared with the detector tests.
	 *
	 * @var string
	 */
	private string $fixtures_base;

	/**
	 * Keys injected into \WC_Emails::$emails during the current test.
	 *
	 * @var string[]
	 */
	private array $injected_email_keys = array();

	/**
	 * Transactional email post manager singleton.
	 *
	 * @var WCTransactionalEmailPostsManager
	 */
	private WCTransactionalEmailPostsManager $posts_manager;

	/**
	 * Captured `(event_name, payload)` tuples written by the test event recorder.
	 *
	 * @var array<int, array{string, array<string,mixed>}>
	 */
	private array $captured_events = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		update_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION, 'yes' );

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		$this->captured_events = array();
		WCEmailTemplateSyncTracker::set_event_recorder(
			function ( string $event_name, array $payload ): void {
				$this->captured_events[] = array( $event_name, $payload );
			}
		);
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		WCEmailTemplateSyncTracker::set_event_recorder( null );
		WCEmailTemplateSyncRegistry::reset_cache();

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		// Clear any dedup transients written by the tests so a re-run starts clean.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_wc_email_update_available_fired\\_%'" );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		delete_option( WCEmailTemplateSyncTracker::BACKFILL_COMPLETED_TRACKED_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * @testdox Should fire `_update_available` with the documented shared payload keys.
	 */
	public function test_record_update_available_fires_with_shared_payload(): void {
		$email_id = 'wc_test_tracker_payload';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::record_update_available( $post_id );

		$this->assertCount( 1, $this->captured_events, 'Tracker should record exactly one event.' );

		list( $event_name, $payload ) = $this->captured_events[0];

		$this->assertSame( WCEmailTemplateSyncTracker::EVENT_UPDATE_AVAILABLE, $event_name );

		$expected_keys = array(
			'email_id',
			'template_version_from',
			'template_version_to',
			'source_hash_to',
			'classification',
			'was_backfilled',
		);
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $payload, "Payload is missing `{$key}` key." );
		}
		$this->assertArrayNotHasKey( 'source_hash_from', $payload, 'Payload must not include `source_hash_from` (RSM-145 §15.4).' );

		$this->assertSame( $email_id, $payload['email_id'] );
		$this->assertNotSame( '', $payload['template_version_to'], 'template_version_to should come from the registry.' );
		$this->assertNotSame( '', $payload['source_hash_to'], 'source_hash_to should be sha1 of the canonical render.' );
	}

	/**
	 * @testdox Should dedup repeat `_update_available` calls at the same template_version_to.
	 */
	public function test_record_update_available_dedups_same_version_to(): void {
		$email_id = 'wc_test_tracker_dedup';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::record_update_available( $post_id );
		WCEmailTemplateSyncTracker::record_update_available( $post_id );
		WCEmailTemplateSyncTracker::record_update_available( $post_id );

		$this->assertCount( 1, $this->captured_events, 'Repeat calls at the same template_version_to must dedup.' );
	}

	/**
	 * @testdox Should re-fire `_update_available` when the per-tuple transient is cleared.
	 */
	public function test_record_update_available_refires_when_transient_cleared(): void {
		$email_id = 'wc_test_tracker_transient_cleared';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::record_update_available( $post_id );
		$this->assertCount( 1, $this->captured_events );

		// Simulate the dedup window expiring (or a new `template_version_to`
		// producing a different transient key — same observable effect).
		// Use `delete_transient()` so wp_cache's in-memory copy is invalidated
		// alongside the row in `$wpdb->options`.
		$config     = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id );
		$version_to = is_array( $config ) ? (string) ( $config['version'] ?? '' ) : '';
		delete_transient( 'wc_email_update_available_fired_' . $post_id . '_' . md5( $version_to ) );

		WCEmailTemplateSyncTracker::record_update_available( $post_id );

		$this->assertCount( 2, $this->captured_events, 'Cleared dedup transient must allow a fresh event.' );
	}

	/**
	 * @testdox Should suppress every per-post event while the backfill is running.
	 */
	public function test_events_are_suppressed_during_backfill(): void {
		$email_id = 'wc_test_tracker_suppress';
		$post_id  = $this->generate_stamped_post( $email_id );

		$this->set_backfill_running( true );

		WCEmailTemplateSyncTracker::record_update_available( $post_id );
		WCEmailTemplateSyncTracker::record_auto_applied( $post_id );
		WCEmailTemplateSyncTracker::record_selective_applied( $post_id );

		$this->set_backfill_running( false );

		$this->assertSame( array(), $this->captured_events, 'No per-post events should fire while backfilling.' );
	}

	/**
	 * @testdox Should fire `_update_applied` with auto-applier extensions.
	 */
	public function test_record_auto_applied_payload(): void {
		$email_id = 'wc_test_tracker_auto_applied';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::record_auto_applied( $post_id );

		$this->assertCount( 1, $this->captured_events );
		list( $event_name, $payload ) = $this->captured_events[0];
		$this->assertSame( WCEmailTemplateSyncTracker::EVENT_UPDATE_APPLIED, $event_name );
		$this->assertSame( WCEmailTemplateSyncTracker::APPLIED_FROM_AUTO, $payload['applied_from'] );
		$this->assertTrue( $payload['auto_resolved'] );
		$this->assertFalse( $payload['had_customizations'] );
	}

	/**
	 * @testdox Should fire `_update_applied` with selective-applier extensions.
	 */
	public function test_record_selective_applied_payload(): void {
		$email_id = 'wc_test_tracker_selective_applied';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::record_selective_applied( $post_id );

		$this->assertCount( 1, $this->captured_events );
		list( $event_name, $payload ) = $this->captured_events[0];
		$this->assertSame( WCEmailTemplateSyncTracker::EVENT_UPDATE_APPLIED, $event_name );
		$this->assertSame( WCEmailTemplateSyncTracker::APPLIED_FROM_SELECTIVE_REST, $payload['applied_from'] );
		$this->assertFalse( $payload['auto_resolved'] );
		$this->assertTrue( $payload['had_customizations'] );
	}

	/**
	 * @testdox Should fire `_backfill_completed` with posts_backfilled count and wc_version.
	 */
	public function test_on_backfill_complete_records_event_with_count(): void {
		$email_id = 'wc_test_tracker_backfill_completed';
		$post_a   = $this->generate_stamped_post( $email_id );

		// Stamp `was_backfilled = true` on this post so the count includes it.
		update_post_meta( $post_a, WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY, true );

		WCEmailTemplateSyncTracker::on_backfill_complete();

		$this->assertCount( 1, $this->captured_events );
		list( $event_name, $payload ) = $this->captured_events[0];
		$this->assertSame( WCEmailTemplateSyncTracker::EVENT_BACKFILL_COMPLETED, $event_name );
		$this->assertSame( 1, $payload['posts_backfilled'] );
		$this->assertArrayHasKey( 'wc_version', $payload );
		$this->assertNotSame( '', $payload['wc_version'] );
	}

	/**
	 * @testdox Should only fire `_backfill_completed` once per site even on repeat hook firings.
	 */
	public function test_on_backfill_complete_is_one_shot(): void {
		$email_id = 'wc_test_tracker_backfill_one_shot';
		$post_id  = $this->generate_stamped_post( $email_id );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY, true );

		WCEmailTemplateSyncTracker::on_backfill_complete();
		WCEmailTemplateSyncTracker::on_backfill_complete();
		WCEmailTemplateSyncTracker::on_backfill_complete();

		$this->assertCount( 1, $this->captured_events, 'Repeat invocations must not double-count the backfill.' );
		$this->assertSame(
			'yes',
			(string) get_option( WCEmailTemplateSyncTracker::BACKFILL_COMPLETED_TRACKED_OPTION ),
			'One-shot guard option should be stamped after the first record.'
		);
	}

	/**
	 * @testdox Should swallow exceptions thrown inside build_base_payload so callers don't surface failures.
	 */
	public function test_record_swallows_exceptions_from_payload_builder(): void {
		$email_id = 'wc_test_tracker_payload_builder_throws';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Simulate a third-party callback throwing inside the payload-building
		// pipeline by hooking `get_post_metadata` (which `build_base_payload`
		// calls via `get_post_meta`) and throwing from the filter.
		$throw_filter = static function (): void {
			throw new \RuntimeException( 'simulated meta-filter failure' );
		};
		add_filter( 'get_post_metadata', $throw_filter );

		try {
			// Should not bubble up despite the inner filter throwing.
			WCEmailTemplateSyncTracker::record_selective_applied( $post_id );
		} finally {
			remove_filter( 'get_post_metadata', $throw_filter );
		}

		$this->assertSame(
			array(),
			$this->captured_events,
			'A throw from build_base_payload must result in zero events, not a propagated exception.'
		);
	}

	/**
	 * @testdox Should swallow exceptions from the event recorder so callers don't surface failures.
	 */
	public function test_record_swallows_exceptions_from_recorder(): void {
		$email_id = 'wc_test_tracker_swallow_throws';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ): void {
				unset( $event_name, $payload );
				throw new \RuntimeException( 'simulated tracker failure' );
			}
		);

		// Should not bubble up despite the recorder throwing.
		WCEmailTemplateSyncTracker::record_selective_applied( $post_id );

		// `captured_events` is empty because we replaced the spy with a thrower —
		// the test passes simply by not propagating the exception.
		$this->assertTrue( true, 'A thrown recorder must not propagate to the caller.' );
	}

	/**
	 * @testdox Should silently no-op when the post is not in the sync registry.
	 */
	public function test_record_update_available_noop_for_unregistered_post(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'woo_email',
				'post_status' => 'publish',
			)
		);

		WCEmailTemplateSyncTracker::record_update_available( $post_id );

		$this->assertSame( array(), $this->captured_events, 'Unregistered posts should not produce events.' );
	}

	// ------------------------------------------------------------------
	// Helpers (mirror the detector test's fixture flow).
	// ------------------------------------------------------------------

	/**
	 * Drive the real generator flow to produce a stamped woo_email post for the given fixture.
	 *
	 * @param string $email_id Email ID to generate a post for.
	 * @return int The generated post ID.
	 */
	private function generate_stamped_post( string $email_id ): int {
		$this->register_fixture_email( $email_id );

		$generator = new WCTransactionalEmailPostsGenerator();
		$generator->init_default_transactional_emails();
		$this->posts_manager->delete_email_template( $email_id );

		$post_id = $generator->generate_email_template_if_not_exists( $email_id );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		return $post_id;
	}

	/**
	 * Inject a stub WC_Email into the global container and register it for sync.
	 *
	 * @param string $email_id Email ID to register.
	 * @return \WC_Email
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for tracker tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover tracker scenarios.' );
		$stub->id             = $email_id;
		$stub->template_base  = $this->fixtures_base;
		$stub->template_block = 'block/third-party-with-version.php';
		$stub->template_plain = null;

		$class_key = 'WC_Test_Email_Tracker_' . $email_id;

		$emails_container = \WC_Emails::instance();
		$reflection       = new \ReflectionClass( $emails_container );
		$property         = $reflection->getProperty( 'emails' );
		$property->setAccessible( true );
		$current               = $property->getValue( $emails_container );
		$current[ $class_key ] = $stub;
		$property->setValue( $emails_container, $current );

		$this->injected_email_keys[] = $class_key;

		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			static function ( array $emails ) use ( $email_id ): array {
				if ( ! in_array( $email_id, $emails, true ) ) {
					$emails[] = $email_id;
				}
				return $emails;
			}
		);

		WCEmailTemplateSyncRegistry::reset_cache();

		return $stub;
	}

	/**
	 * Remove every fixture email this test injected.
	 */
	private function cleanup_injected_emails(): void {
		if ( empty( $this->injected_email_keys ) ) {
			return;
		}
		$emails_container = \WC_Emails::instance();
		$reflection       = new \ReflectionClass( $emails_container );
		$property         = $reflection->getProperty( 'emails' );
		$property->setAccessible( true );
		$current = $property->getValue( $emails_container );
		foreach ( $this->injected_email_keys as $key ) {
			unset( $current[ $key ] );
		}
		$property->setValue( $emails_container, $current );
		$this->injected_email_keys = array();
	}

	/**
	 * Toggle the static backfill-running flag via reflection so suppress-during-backfill paths can be exercised.
	 *
	 * @param bool $running Whether the backfill is currently running.
	 */
	private function set_backfill_running( bool $running ): void {
		$reflection = new \ReflectionClass( WCEmailTemplateSyncBackfill::class );
		$property   = $reflection->getProperty( 'is_backfilling' );
		$property->setAccessible( true );
		$property->setValue( null, $running );
	}
}

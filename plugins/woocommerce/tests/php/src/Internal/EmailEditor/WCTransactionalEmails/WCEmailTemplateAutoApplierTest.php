<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateAutoApplier;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateAutoApplier class.
 */
class WCEmailTemplateAutoApplierTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory shared with the divergence detector tests.
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
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		update_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION, 'yes' );

		// Reuse the divergence-detector fixture file — same shape, same @version header.
		$this->fixtures_base = dirname( __DIR__ ) . '/WCTransactionalEmails/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		// In integration runtime the email editor's Templates_Registry registers
		// the `wooemailtemplate` slug via register_block_template() during admin
		// bootstrap. Unit tests skip that bootstrap, so wp_update_post( $wp_error = true )
		// would reject the slug as `invalid_page_template`. Whitelist it via the
		// theme_{post_type}_templates filter for the duration of the test.
		add_filter( 'theme_woo_email_templates', array( $this, 'whitelist_email_page_template' ) );
		add_filter( 'theme_templates', array( $this, 'whitelist_email_page_template' ) );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_filter( 'theme_woo_email_templates', array( $this, 'whitelist_email_page_template' ) );
		remove_filter( 'theme_templates', array( $this, 'whitelist_email_page_template' ) );
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateAutoApplier::set_logger( null );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * Filter callback — register the `wooemailtemplate` slug so wp_update_post
	 * with `$wp_error = true` does not bail with `invalid_page_template`.
	 *
	 * @param array $templates Existing page templates keyed by slug.
	 * @return array
	 */
	public function whitelist_email_page_template( $templates ): array {
		$templates                     = is_array( $templates ) ? $templates : array();
		$templates['wooemailtemplate'] = 'Woo Email Template';
		return $templates;
	}

	/**
	 * apply_to_post() on a sync-enabled post that still matches its stamp must
	 * rewrite content to the canonical render and stamp all four sync meta keys.
	 */
	public function test_apply_to_post_writes_canonical_content_and_stamps_meta(): void {
		$email_id = 'wc_test_auto_apply_happy_path';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Simulate a core-template change by mutating the canonical content via the
		// woocommerce_email_content_post_data filter for the duration of this test —
		// keeps the stored hash from RSM-137 stamping intact while making the
		// auto-applier's recomputed canonical hash differ.
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ) use ( $email_id ): array {
				if ( ( $post_data['post_name'] ?? '' ) === $email_id ) {
					$post_data['post_content'] = (string) ( $post_data['post_content'] ?? '' ) . "\n<!-- new core release -->";
				}
				return $post_data;
			}
		);

		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$expected_hash      = sha1( $expected_canonical );
		$expected_version   = (string) WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id )['version'];

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertIsArray( $result, 'Atom must return an array on success.' );
		$this->assertArrayHasKey( 'content', $result );
		$this->assertSame( $expected_canonical, $result['content'] );
		$this->assertSame( $expected_version, $result['version'] );
		$this->assertSame( $expected_hash, $result['source_hash'] );
		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $result['status'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $result['synced_at'] );

		$post = get_post( $post_id );
		$this->assertSame( $expected_canonical, (string) $post->post_content, 'Post content must be the new canonical render.' );

		$this->assertSame( $expected_hash, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( $expected_version, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ) );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * @testdox Should stamp _wc_email_template_last_core_render with the new canonical post_content after apply_to_post.
	 */
	public function test_apply_to_post_stamps_last_core_render(): void {
		$email_id = 'wc_test_auto_apply_last_core_render';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Simulate a core-template change so the auto-apply has something to write.
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ) use ( $email_id ): array {
				if ( ( $post_data['post_name'] ?? '' ) === $email_id ) {
					$post_data['post_content'] = (string) ( $post_data['post_content'] ?? '' ) . "\n<!-- new core release -->";
				}
				return $post_data;
			}
		);

		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );
		$this->assertIsArray( $result, 'Atom must return an array on success.' );

		$stored_render = (string) get_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY,
			true
		);

		$this->assertNotSame( '', $stored_render, 'last_core_render must be populated after auto-apply.' );
		$this->assertSame(
			$expected_canonical,
			$stored_render,
			'last_core_render must equal the new canonical post_content (post-filter).'
		);
	}

	/**
	 * @testdox Should stamp STATUS_IN_SYNC via the classifier after a successful apply_to_post.
	 *
	 * The auto-applier always writes full canonical content, so the classifier
	 * naturally returns IN_SYNC. This test pins the contract that the stamped
	 * status comes through the classifier rather than from a hard-coded literal,
	 * so a future regression that introduces a partial-apply path would trip it.
	 */
	public function test_apply_to_post_stamps_status_via_classifier(): void {
		$email_id = 'auto_applier_classifier_path';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];
		$this->assertInstanceOf( \WC_Email::class, $email );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );
		$this->assertIsArray( $result );

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * apply_to_post() with require_uncustomized=true must return a WP_Error and
	 * leave the post untouched when the merchant has edited it since stamping.
	 */
	public function test_apply_to_post_with_require_uncustomized_returns_wp_error_when_post_modified(): void {
		$email_id = 'wc_test_auto_apply_modified_since_stamp';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Simulate a merchant edit: rewrite post_content directly so its hash no longer
		// matches the stored stamp, but leave the meta keys in place.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<p>Merchant-edited content</p>',
			)
		);

		$pre_call_meta = array(
			'source_hash'    => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'version'        => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'last_synced_at' => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
		);

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'post_modified_since_stamp', $result->get_error_code() );

		$post = get_post( $post_id );
		$this->assertSame( '<p>Merchant-edited content</p>', (string) $post->post_content, 'Atom must not rewrite content when hash gate fails.' );

		$this->assertSame( $pre_call_meta['source_hash'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['version'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['last_synced_at'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ) );
	}

	/**
	 * apply_to_post() must return WP_Error('no_stored_hash') when the source-hash
	 * meta is missing, even if the post itself looks valid.
	 */
	public function test_apply_to_post_returns_wp_error_when_no_stored_hash(): void {
		$email_id = 'wc_test_auto_apply_no_stored_hash';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		delete_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY );
		$pre_call_content = (string) get_post( $post_id )->post_content;

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'no_stored_hash', $result->get_error_code() );

		$this->assertSame( $pre_call_content, (string) get_post( $post_id )->post_content );
	}

	/**
	 * apply_to_post() with require_uncustomized=true on a non-sync-enabled email
	 * must return WP_Error('not_sync_enabled') and not write anything.
	 */
	public function test_apply_to_post_for_non_sync_enabled_email_with_require_uncustomized_true(): void {
		$email_id = 'wc_test_auto_apply_non_sync_enabled_strict';

		// Generate a stamped post, then nuke its registry membership so the email is
		// no longer sync-enabled at apply time. Using a registry-cache reset keeps the
		// post itself intact (with all four meta keys) so we can assert no writes.
		$post_id = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Drop the email out of the block-editor opt-in filter for the rest of the test.
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();

		$pre_call_content = (string) get_post( $post_id )->post_content;
		$pre_call_status  = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'not_sync_enabled', $result->get_error_code() );

		$this->assertSame( $pre_call_content, (string) get_post( $post_id )->post_content );
		$this->assertSame( $pre_call_status, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ) );
	}

	/**
	 * apply_to_post() must return WP_Error('post_not_found') when the post ID
	 * doesn't resolve to a woo_email post.
	 */
	public function test_apply_to_post_returns_wp_error_when_post_not_found(): void {
		// Register a sync-enabled fixture email but do NOT generate a post for it.
		$email_id = 'wc_test_auto_apply_post_not_found';
		$email    = $this->register_fixture_email( $email_id );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, 999999999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'post_not_found', $result->get_error_code() );
	}

	/**
	 * apply_to_post() with require_uncustomized=false must overwrite a modified
	 * post unconditionally — no hash gate. This is the reset-endpoint contract.
	 */
	public function test_apply_to_post_without_require_uncustomized_overwrites_modified_post(): void {
		$email_id = 'wc_test_auto_apply_reset_overwrites_modified';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<p>Merchant-edited content that the reset must overwrite</p>',
			)
		);

		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$result = WCEmailTemplateAutoApplier::apply_to_post(
			$email,
			$post_id,
			array( 'require_uncustomized' => false )
		);

		$this->assertIsArray( $result );
		$this->assertSame( $expected_canonical, $result['content'] );
		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $result['status'] );

		$this->assertSame( $expected_canonical, (string) get_post( $post_id )->post_content );
	}

	/**
	 * @testdox Should fire `_update_applied` Tracks event on successful auto-applier write.
	 */
	public function test_apply_to_post_fires_update_applied_in_auto_mode(): void {
		$email_id = 'wc_test_auto_apply_tracks_auto';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		$captured = array();
		WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ) use ( &$captured ): void {
				$captured[] = array( $event_name, $payload );
			}
		);

		try {
			WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );
		} finally {
			WCEmailTemplateSyncTracker::set_event_recorder( null );
		}

		$applied_events = array_values(
			array_filter(
				$captured,
				static fn( array $entry ): bool => WCEmailTemplateSyncTracker::EVENT_UPDATE_APPLIED === $entry[0]
			)
		);

		$this->assertCount( 1, $applied_events, 'Auto-applier path should record exactly one _update_applied event.' );
		$this->assertSame( WCEmailTemplateSyncTracker::APPLIED_FROM_AUTO, $applied_events[0][1]['applied_from'] );
	}

	/**
	 * @testdox Should NOT fire `_update_applied` when apply_to_post is invoked from the reset path.
	 */
	public function test_apply_to_post_does_not_fire_update_applied_on_reset_path(): void {
		$email_id = 'wc_test_auto_apply_tracks_reset_silent';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		$captured = array();
		WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ) use ( &$captured ): void {
				$captured[] = array( $event_name, $payload );
			}
		);

		try {
			$result = WCEmailTemplateAutoApplier::apply_to_post(
				$email,
				$post_id,
				array( 'require_uncustomized' => false )
			);
		} finally {
			WCEmailTemplateSyncTracker::set_event_recorder( null );
		}

		// Assert the reset itself succeeded — otherwise an early bail would
		// produce zero events and false-pass the silence assertion below.
		$this->assertIsArray( $result, 'Reset path setup must succeed before asserting Tracks silence.' );

		$applied_events = array_values(
			array_filter(
				$captured,
				static fn( array $entry ): bool => WCEmailTemplateSyncTracker::EVENT_UPDATE_APPLIED === $entry[0]
			)
		);

		$this->assertSame( array(), $applied_events, 'Reset path must not emit `_update_applied` with applied_from=auto.' );
	}

	/**
	 * apply_to_post() with require_uncustomized=false on a non-sync-enabled email
	 * must rewrite content but stamp NO meta. Return shape carries null for the
	 * four sync fields. BC contract from the pre-RSM-139 reset endpoint.
	 */
	public function test_apply_to_post_for_non_sync_enabled_email_with_require_uncustomized_false(): void {
		$email_id = 'wc_test_auto_apply_reset_non_sync_enabled';
		$email    = $this->register_fixture_email( $email_id );

		// Generate a post via direct post insert (bypass the generator's RSM-137 stamping)
		// so the post has no sync meta to begin with — closest analogue to a non-sync-enabled
		// email's persisted state.
		$post_id = wp_insert_post(
			array(
				'post_type'    => \Automattic\WooCommerce\Internal\EmailEditor\Integration::EMAIL_POST_TYPE,
				'post_status'  => 'publish',
				'post_name'    => $email_id,
				'post_title'   => 'Non-sync-enabled fixture',
				'post_content' => '<p>Initial non-canonical content</p>',
			)
		);
		$this->assertIsInt( $post_id );

		// Drop the email out of the registry so apply_to_post sees null sync_config.
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();

		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$result = WCEmailTemplateAutoApplier::apply_to_post(
			$email,
			$post_id,
			array( 'require_uncustomized' => false )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'content', $result );
		$this->assertSame( $expected_canonical, $result['content'] );
		$this->assertNull( $result['version'] );
		$this->assertNull( $result['source_hash'] );
		$this->assertNull( $result['synced_at'] );
		$this->assertNull( $result['status'] );

		$this->assertSame( $expected_canonical, (string) get_post( $post_id )->post_content );
		$this->assertSame( '', (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( '', (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ) );
	}

	/**
	 * When wp_update_post() fails the atom must short-circuit before the meta
	 * writes, so neither post_content nor any of the four sync meta keys are
	 * modified. Matches the pre-RSM-139 reset endpoint contract.
	 */
	public function test_apply_to_post_returns_wp_error_when_wp_update_post_fails(): void {
		$email_id = 'wc_test_auto_apply_rollback';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		$pre_call_content = (string) get_post( $post_id )->post_content;
		$pre_call_meta    = array(
			'source_hash'    => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'version'        => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'last_synced_at' => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
			'status'         => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
		);

		// Force wp_update_post to fail by short-circuiting it through the
		// 'wp_insert_post_empty_content' filter — when this returns true,
		// wp_update_post returns WP_Error('empty_content').
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		try {
			$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );
		} finally {
			remove_filter( 'wp_insert_post_empty_content', '__return_true' );
		}

		$this->assertInstanceOf( \WP_Error::class, $result );

		clean_post_cache( $post_id );
		$this->assertSame( $pre_call_content, (string) get_post( $post_id )->post_content );
		$this->assertSame( $pre_call_meta['source_hash'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['version'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['last_synced_at'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['status'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ) );
	}

	/**
	 * is_auto_applying() must read true while wp_update_post is running inside the
	 * atom and false again once apply_to_post returns. Verified by hooking save_post
	 * (which fires from inside wp_update_post) and capturing the flag value there.
	 */
	public function test_apply_to_post_sets_is_auto_applying_flag_during_write(): void {
		$email_id = 'wc_test_auto_apply_reentrancy_flag';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		$captured_during_save = null;
		$listener             = static function () use ( &$captured_during_save ): void {
			$captured_during_save = WCEmailTemplateAutoApplier::is_auto_applying();
		};
		add_action( 'save_post', $listener );

		// Drive the auto-applier path via the require_uncustomized=false branch so
		// we don't have to manufacture a core-template change here.
		try {
			WCEmailTemplateAutoApplier::apply_to_post(
				$email,
				$post_id,
				array( 'require_uncustomized' => false )
			);
		} finally {
			remove_action( 'save_post', $listener );
		}

		$this->assertTrue( $captured_during_save, 'is_auto_applying() must read true inside the write block.' );
		$this->assertFalse( WCEmailTemplateAutoApplier::is_auto_applying(), 'is_auto_applying() must read false after apply_to_post returns.' );
	}

	/**
	 * schedule() must enqueue an async Action Scheduler action under the
	 * dedicated email-editor group.
	 */
	public function test_schedule_enqueues_action_scheduler_job(): void {
		// Make sure no leftover action exists from a previous test or session.
		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);

		WCEmailTemplateAutoApplier::schedule();

		$this->assertTrue(
			(bool) as_has_scheduled_action(
				WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
				array(),
				WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
			)
		);

		// Cleanup so subsequent tests start with a clean queue.
		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);
	}

	/**
	 * Calling schedule() twice in the same request (e.g. once from
	 * woocommerce_updated and once from BACKFILL_COMPLETE_ACTION) must not
	 * enqueue two pending actions.
	 */
	public function test_schedule_does_not_double_enqueue(): void {
		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);

		WCEmailTemplateAutoApplier::schedule();
		WCEmailTemplateAutoApplier::schedule();

		$pending = as_get_scheduled_actions(
			array(
				'hook'   => WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
				'group'  => WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);

		$this->assertCount( 1, $pending, 'schedule() must guard against double-enqueueing.' );

		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);
	}

	/**
	 * run() must apply only to posts whose status meta is core_updated_uncustomized,
	 * leaving in_sync and core_updated_customized posts untouched.
	 */
	public function test_run_applies_to_every_uncustomized_post(): void {
		// 3 posts, each with a distinct fixture email so each registers in the registry.
		$uncustomized_post_id = $this->generate_stamped_post( 'wc_test_run_uncustomized' );
		$customized_post_id   = $this->generate_stamped_post( 'wc_test_run_customized' );
		$in_sync_post_id      = $this->generate_stamped_post( 'wc_test_run_in_sync' );

		update_post_meta(
			$uncustomized_post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED
		);
		update_post_meta(
			$customized_post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED
		);
		update_post_meta(
			$in_sync_post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC
		);

		$content_before_customized = (string) get_post( $customized_post_id )->post_content;
		$content_before_in_sync    = (string) get_post( $in_sync_post_id )->post_content;

		WCEmailTemplateAutoApplier::run();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $uncustomized_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Uncustomized post must flip to in_sync after run().'
		);

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			(string) get_post_meta( $customized_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Customized post status must be untouched.'
		);
		$this->assertSame( $content_before_customized, (string) get_post( $customized_post_id )->post_content );

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $in_sync_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
		$this->assertSame( $content_before_in_sync, (string) get_post( $in_sync_post_id )->post_content );
	}

	/**
	 * One bad post must not break the rest of the batch. The atom failure is
	 * logged at error severity with context=email_template_auto_applier.
	 */
	public function test_run_logs_and_continues_when_one_post_fails(): void {
		$failing_post_id = $this->generate_stamped_post( 'wc_test_run_failure_isolation_fail' );
		$good_post_id    = $this->generate_stamped_post( 'wc_test_run_failure_isolation_good' );

		update_post_meta( $failing_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );
		update_post_meta( $good_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		// Force wp_update_post to fail for the FIRST post only by toggling the filter
		// on/off based on which post ID is being saved.
		add_filter(
			'wp_insert_post_empty_content',
			static function ( $maybe_empty, $postarr ) use ( $failing_post_id ) {
				return ( ( (int) ( $postarr['ID'] ?? 0 ) ) === $failing_post_id ) ? true : $maybe_empty;
			},
			10,
			2
		);

		$captured = array();
		WCEmailTemplateAutoApplier::set_logger( $this->build_recording_logger( $captured ) );

		try {
			WCEmailTemplateAutoApplier::run();
		} finally {
			remove_all_filters( 'wp_insert_post_empty_content' );
		}

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $failing_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Failing post status must be untouched (no rewrite happened).'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $good_post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Good post must still be applied despite the prior failure.'
		);

		$error_logs = array_filter(
			$captured,
			static fn( array $entry ) => 'error' === $entry['level']
				&& ( $entry['context']['context'] ?? '' ) === 'email_template_auto_applier'
		);
		$this->assertCount( 1, $error_logs, 'Exactly one error log entry must be emitted for the failing post.' );
	}

	/**
	 * If the post was modified since stamping (race window between sweep and AS job),
	 * run() must skip it at info severity, leave content/meta untouched, and not
	 * roll the status meta back.
	 */
	public function test_run_skips_post_with_post_modified_since_stamp_at_info_severity(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_race_safety' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		// Simulate merchant edit during the AS lag window.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<p>Merchant-edited content during AS lag</p>',
			)
		);

		$captured = array();
		WCEmailTemplateAutoApplier::set_logger( $this->build_recording_logger( $captured ) );

		WCEmailTemplateAutoApplier::run();

		$this->assertSame( '<p>Merchant-edited content during AS lag</p>', (string) get_post( $post_id )->post_content );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Status must remain core_updated_uncustomized so the next sweep can re-classify.'
		);

		$info_logs = array_filter(
			$captured,
			static fn( array $entry ) => 'info' === $entry['level']
				&& ( $entry['context']['context'] ?? '' ) === 'email_template_auto_applier'
		);
		$this->assertCount( 1, $info_logs, 'Race outcome must be logged at info severity.' );

		$error_logs = array_filter( $captured, static fn( array $entry ) => 'error' === $entry['level'] );
		$this->assertCount( 0, $error_logs, 'Race outcome must NOT log at error severity.' );
	}

	/**
	 * run() with no candidates must be a no-op.
	 */
	public function test_run_is_a_no_op_when_no_uncustomized_posts_exist(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_no_op' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC );

		$content_before = (string) get_post( $post_id )->post_content;

		$write_count = 0;
		$counter     = static function ( $check, int $object_id, string $meta_key ) use ( &$write_count ) {
			unset( $object_id, $meta_key );
			++$write_count;
			return $check;
		};
		add_filter( 'update_post_metadata', $counter, 10, 3 );

		try {
			WCEmailTemplateAutoApplier::run();
		} finally {
			remove_filter( 'update_post_metadata', $counter, 10 );
		}

		$this->assertSame( 0, $write_count, 'No meta writes must occur when there are no candidates.' );
		$this->assertSame( $content_before, (string) get_post( $post_id )->post_content );
	}

	/**
	 * Two consecutive run() calls on the same state — the first applies and flips
	 * status to in_sync, the second writes zero rows. (Acceptance criterion.)
	 */
	public function test_run_is_idempotent_across_repeat_invocations(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_idempotency' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		WCEmailTemplateAutoApplier::run();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);

		$write_count = 0;
		$counter     = static function ( $check ) use ( &$write_count ) {
			++$write_count;
			return $check;
		};
		add_filter( 'update_post_metadata', $counter, 10, 1 );
		add_filter( 'wp_insert_post_data', $counter, 10, 1 );

		try {
			WCEmailTemplateAutoApplier::run();
		} finally {
			remove_filter( 'update_post_metadata', $counter, 10 );
			remove_filter( 'wp_insert_post_data', $counter, 10 );
		}

		$this->assertSame( 0, $write_count, 'Second run must write zero rows.' );
	}

	/**
	 * run() must short-circuit when BACKFILL_COMPLETE_OPTION is not 'yes', even if
	 * core_updated_uncustomized posts exist in the DB.
	 */
	public function test_run_respects_backfill_complete_option_gate(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_backfill_gate' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		update_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION, 'no' );

		$content_before = (string) get_post( $post_id )->post_content;

		WCEmailTemplateAutoApplier::run();

		$this->assertSame( $content_before, (string) get_post( $post_id )->post_content );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Posts whose registered email is no longer in the sync registry at run time
	 * must be skipped silently — no log, no write. (Plugin deactivated mid-cycle.)
	 */
	public function test_run_skips_posts_for_deactivated_email_plugins(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_deactivated' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		$content_before = (string) get_post( $post_id )->post_content;

		// Drop the email out of the registry.
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();

		$captured = array();
		WCEmailTemplateAutoApplier::set_logger( $this->build_recording_logger( $captured ) );

		WCEmailTemplateAutoApplier::run();

		$this->assertSame( $content_before, (string) get_post( $post_id )->post_content );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
		$this->assertSame( array(), $captured, 'Deactivated-plugin skip must be silent (no log emission).' );
	}

	/**
	 * The candidate query must accept any non-trash post status — generated
	 * woo_email posts default to `publish` but third-party flows may move them
	 * to `draft` / `private` / `pending` / `future` and those must not be
	 * silently skipped.
	 *
	 * @dataProvider provider_non_trash_post_statuses
	 *
	 * @param string $post_status Post status to stamp on the candidate.
	 */
	public function test_run_applies_to_candidate_regardless_of_non_trash_post_status( string $post_status ): void {
		global $wpdb;

		$post_id = $this->generate_stamped_post( 'wc_test_run_status_' . $post_status );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		// Flip status via a direct UPDATE: wp_update_post calls map_meta_cap, which
		// emits a _doing_it_wrong notice in the unit-test bootstrap because the
		// woo_email post type is not registered there (Integration::initialize() is
		// not run). The candidate query is the surface under test; status flipping
		// is incidental.
		$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );

		WCEmailTemplateAutoApplier::run();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			"Auto-applier must process candidates whose post_status is {$post_status}."
		);
	}

	/**
	 * Data provider for {@see self::test_run_applies_to_candidate_regardless_of_non_trash_post_status()}.
	 *
	 * `pending` is intentionally omitted: WP's `wp_update_post` invokes
	 * `map_meta_cap('publish_post', ...)` for any write touching a pending post,
	 * which emits a _doing_it_wrong notice in the unit-test bootstrap because the
	 * `woo_email` post type is not registered there. Pending isn't a realistic
	 * status for transactional email templates anyway.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function provider_non_trash_post_statuses(): array {
		return array(
			'draft'   => array( 'draft' ),
			'private' => array( 'private' ),
			'future'  => array( 'future' ),
		);
	}

	/**
	 * Trashed posts must NOT be picked up by the candidate query — the WP_Query
	 * `post_status=any` clause already excludes the trash bucket and we want to
	 * lock that in.
	 */
	public function test_run_skips_trashed_posts(): void {
		$post_id = $this->generate_stamped_post( 'wc_test_run_trashed' );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED );

		wp_trash_post( $post_id );
		clean_post_cache( $post_id );

		$content_before = (string) get_post( $post_id )->post_content;

		WCEmailTemplateAutoApplier::run();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Trashed posts must not have their status flipped by the auto-applier.'
		);
		$this->assertSame( $content_before, (string) get_post( $post_id )->post_content );
	}

	/**
	 * Firing the divergence-sweep completion action must trigger the auto-applier
	 * to enqueue an AS job. This locks in the wiring that lives in
	 * Integration::register_hooks().
	 */
	public function test_detector_sweep_complete_action_triggers_schedule(): void {
		// The unit-test bootstrap does not run Integration::initialize() (which calls
		// register_hooks()). Force it for this single test so the wiring under test
		// is actually present on the global hook table.
		wc_get_container()->get( Integration::class )->initialize();

		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);

		/**
		 * Trigger the production hook the auto-applier listens to; the assertions
		 * below confirm `Integration::register_hooks()` routes it to `schedule()`.
		 *
		 * @since 10.8.0
		 */
		do_action( 'woocommerce_email_template_divergence_sweep_complete' );

		$this->assertTrue(
			(bool) as_has_scheduled_action(
				WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
				array(),
				WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
			),
			'Integration::register_hooks() must wire schedule() to the divergence-sweep completion action.'
		);

		as_unschedule_all_actions(
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_HOOK,
			array(),
			WCEmailTemplateAutoApplier::AUTO_APPLY_AS_GROUP
		);
	}

	/**
	 * Build a recording logger that captures every call into a flat array. Each entry
	 * is shaped: [ 'level' => 'info'|'warning'|'error', 'message' => string, 'context' => array ].
	 *
	 * @param array<int, array<string, mixed>> $sink Reference to the array that will receive entries.
	 * @return \Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface
	 */
	private function build_recording_logger( array &$sink ): \Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface {
		return new class( $sink ) implements \Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface {
			/** @var array<int, array<string, mixed>> */
			private array $sink;

			/**
			 * @param array<int, array<string, mixed>> $sink Reference to the array that will receive entries.
			 */
			public function __construct( array &$sink ) {
				$this->sink = &$sink;
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function emergency( string $message, array $context = array() ): void {
				$this->capture( 'emergency', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function alert( string $message, array $context = array() ): void {
				$this->capture( 'alert', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function critical( string $message, array $context = array() ): void {
				$this->capture( 'critical', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function error( string $message, array $context = array() ): void {
				$this->capture( 'error', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function warning( string $message, array $context = array() ): void {
				$this->capture( 'warning', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function notice( string $message, array $context = array() ): void {
				$this->capture( 'notice', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function info( string $message, array $context = array() ): void {
				$this->capture( 'info', $message, $context );
			}

			/**
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function debug( string $message, array $context = array() ): void {
				$this->capture( 'debug', $message, $context );
			}

			/**
			 * @param string               $level   The log level.
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			public function log( string $level, string $message, array $context = array() ): void {
				$this->capture( $level, $message, $context );
			}

			/**
			 * Append a captured log call to the sink.
			 *
			 * @param string               $level   The log level.
			 * @param string               $message The log message.
			 * @param array<string, mixed> $context The log context.
			 */
			private function capture( string $level, string $message, array $context ): void {
				$this->sink[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}

	/**
	 * Build a WC_Email stub backed by the third-party-with-version.php fixture, inject it
	 * into WC_Emails::$emails, and opt the email ID into the block-editor filter so the
	 * sync registry picks it up.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for auto-applier tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover auto-apply scenarios.' );
		$stub->id             = $email_id;
		$stub->template_base  = $this->fixtures_base;
		$stub->template_block = 'block/third-party-with-version.php';
		$stub->template_plain = null;

		$class_key = 'WC_Test_Email_' . $email_id;

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
	 * Remove any stubs we injected into WC_Emails::$emails during the test.
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
}

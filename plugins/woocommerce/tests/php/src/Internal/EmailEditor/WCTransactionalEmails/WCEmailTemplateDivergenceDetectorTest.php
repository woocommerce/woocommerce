<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateDivergenceDetector class.
 */
class WCEmailTemplateDivergenceDetectorTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory.
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

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		// Singleton caches survive test transaction rollback and would otherwise leak
		// stale post_id <-> email_type mappings into subsequent tests.
		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();
	}

	/**
	 * @testdox Should register _wc_email_template_status post meta on woo_email with show_in_rest.
	 */
	public function test_registers_template_status_meta_with_show_in_rest(): void {
		$this->initialize_email_editor_integration();

		$this->assertTrue(
			registered_meta_key_exists( 'post', WCEmailTemplateDivergenceDetector::STATUS_META_KEY, 'woo_email' ),
			'Expected _wc_email_template_status to be registered for woo_email.'
		);

		$args = get_registered_meta_keys( 'post', 'woo_email' )[ WCEmailTemplateDivergenceDetector::STATUS_META_KEY ];

		$this->assertTrue( $args['show_in_rest'], 'Expected show_in_rest = true.' );
		$this->assertTrue( $args['single'], 'Expected single = true.' );
		$this->assertSame( 'string', $args['type'] );
		$this->assertIsCallable( $args['auth_callback'] );
	}

	/**
	 * @testdox Should register _wc_email_template_version post meta on woo_email with show_in_rest.
	 */
	public function test_registers_template_version_meta_with_show_in_rest(): void {
		$this->initialize_email_editor_integration();

		$this->assertTrue(
			registered_meta_key_exists( 'post', WCEmailTemplateDivergenceDetector::VERSION_META_KEY, 'woo_email' ),
			'Expected _wc_email_template_version to be registered for woo_email.'
		);

		$args = get_registered_meta_keys( 'post', 'woo_email' )[ WCEmailTemplateDivergenceDetector::VERSION_META_KEY ];

		$this->assertTrue( $args['show_in_rest'], 'Expected show_in_rest = true.' );
		$this->assertTrue( $args['single'], 'Expected single = true.' );
		$this->assertSame( 'string', $args['type'] );
	}

	/**
	 * @testdox Should register _wc_email_template_source_hash post meta on woo_email with show_in_rest.
	 */
	public function test_registers_template_source_hash_meta_with_show_in_rest(): void {
		$this->initialize_email_editor_integration();

		$this->assertTrue(
			registered_meta_key_exists( 'post', WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, 'woo_email' ),
			'Expected _wc_email_template_source_hash to be registered for woo_email.'
		);

		$args = get_registered_meta_keys( 'post', 'woo_email' )[ WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY ];

		$this->assertTrue( $args['show_in_rest'], 'Expected show_in_rest = true.' );
		$this->assertTrue( $args['single'], 'Expected single = true.' );
		$this->assertSame( 'string', $args['type'] );
		$this->assertIsCallable( $args['auth_callback'] );
	}

	/**
	 * @testdox Should register _wc_email_backfilled post meta on woo_email with show_in_rest.
	 */
	public function test_registers_email_backfilled_meta_with_show_in_rest(): void {
		$this->initialize_email_editor_integration();

		$this->assertTrue(
			registered_meta_key_exists( 'post', WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY, 'woo_email' ),
			'Expected _wc_email_backfilled to be registered for woo_email.'
		);

		$args = get_registered_meta_keys( 'post', 'woo_email' )[ WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY ];

		$this->assertTrue( $args['show_in_rest'], 'Expected show_in_rest = true.' );
		$this->assertTrue( $args['single'], 'Expected single = true.' );
		$this->assertSame( 'boolean', $args['type'] );
		$this->assertIsCallable( $args['auth_callback'] );
	}

	/**
	 * @testdox Should deny REST writes to template meta even for administrators.
	 */
	public function test_meta_auth_callback_denies_write_via_rest(): void {
		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertFalse(
			WCEmailTemplateDivergenceDetector::rest_meta_auth_read_only( true, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, 0, $admin_user, 'edit_post_meta', array() ),
			'Even an administrator must not be able to write _wc_email_template_status via REST.'
		);

		$this->assertFalse(
			WCEmailTemplateDivergenceDetector::rest_meta_auth_read_only( true, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, 0, $admin_user, 'add_post_meta', array() ),
			'add_post_meta must be denied via REST.'
		);

		$this->assertFalse(
			WCEmailTemplateDivergenceDetector::rest_meta_auth_read_only( true, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, 0, $admin_user, 'delete_post_meta', array() ),
			'delete_post_meta must be denied via REST.'
		);
	}

	/**
	 * @testdox Should allow REST reads of template meta for users who can edit the post.
	 */
	public function test_meta_auth_callback_allows_read_for_capable_user(): void {
		// Ensure the woo_email post type is registered so user_can( 'edit_post' ) does not
		// trip a doing-it-wrong notice about the post type being unregistered.
		$this->initialize_email_editor_integration();

		$admin_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'woo_email',
				'post_status' => 'publish',
				'post_author' => $admin_user_id,
			)
		);

		$this->assertTrue(
			WCEmailTemplateDivergenceDetector::rest_meta_auth_read_only( false, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, $post_id, $admin_user_id, 'read_post', array() ),
			'Capable user must be able to read _wc_email_template_status via REST.'
		);
	}

	/**
	 * @testdox Should fire `_update_available` from reclassify() on version-advance transition into customized.
	 */
	public function test_reclassify_fires_update_available_on_version_advance(): void {
		$email_id = 'wc_test_divergence_available_fires';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Stage the divergence: simulate "core has moved AND merchant has drifted"
		// by stamping a different source hash (so neither current_core_hash nor
		// current_post_hash matches the stamp). The classifier returns
		// `core_updated_customized` in that case.
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY,
			sha1( 'stamped-from-an-earlier-core-render' )
		);
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, '1.0.0' );

		$captured = array();
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ) use ( &$captured ): void {
				$captured[] = array( $event_name, $payload );
			}
		);

		$status = WCEmailTemplateDivergenceDetector::reclassify( $post_id );

		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder( null );

		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED, $status );
		$this->assertCount( 1, $captured, 'reclassify must fire one _update_available event on version-advance transition.' );
		$this->assertSame( 'block_email_update_available', $captured[0][0] );
	}

	/**
	 * @testdox Should fire `_update_available` on a cross-release sweep even when status stays customized.
	 *
	 * Regression for the case where a merchant sits on a `core_updated_customized`
	 * divergence across multiple core releases. Status meta does not change between
	 * sweeps (still customized), but `version_to` advances each release — analytics
	 * must see one event per release boundary, not a single lifetime event.
	 */
	public function test_reclassify_fires_update_available_on_subsequent_release_when_status_unchanged(): void {
		$email_id = 'wc_test_divergence_available_cross_release';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Stage the divergence as in the fires-on-version-advance test, but also
		// pre-stamp the status meta to `core_updated_customized` so the
		// idempotency early-return inside reclassify() is the only thing between
		// the classifier verdict and the event-firing block.
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY,
			sha1( 'stamped-from-an-earlier-core-render' )
		);
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, '1.0.0' );
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED
		);

		$captured = array();
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ) use ( &$captured ): void {
				$captured[] = array( $event_name, $payload );
			}
		);

		$status = WCEmailTemplateDivergenceDetector::reclassify( $post_id );

		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder( null );

		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED, $status );
		$this->assertCount(
			1,
			$captured,
			'reclassify must fire _update_available across release boundaries even when status meta is unchanged.'
		);
		$this->assertSame( 'block_email_update_available', $captured[0][0] );
	}

	/**
	 * @testdox Should not fire `_update_available` from reclassify() when version_from equals version_to.
	 */
	public function test_reclassify_skips_update_available_when_version_unchanged(): void {
		$email_id = 'wc_test_divergence_available_no_advance';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Stage the divergence: as above, but leave the version stamp at the
		// fixture's `@version` so the version-advance gate fails.
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY,
			sha1( 'stamped-from-an-earlier-core-render' )
		);
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, '1.2.3' );

		$captured = array();
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ) use ( &$captured ): void {
				$captured[] = array( $event_name, $payload );
			}
		);

		WCEmailTemplateDivergenceDetector::reclassify( $post_id );

		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder( null );

		$this->assertSame( array(), $captured, 'reclassify must not fire _update_available when the merchant has already reviewed this version.' );
	}

	/**
	 * @testdox Should stamp BACKFILL_COMPLETE_OPTION when fresh-install listener fires.
	 */
	public function test_mark_backfill_complete_on_fresh_install_stamps_option(): void {
		// Start from the "option missing" state a fresh 10.9 install would have.
		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		$this->assertFalse( get_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION ) );

		WCEmailTemplateDivergenceDetector::mark_backfill_complete_on_fresh_install();

		$this->assertSame(
			'yes',
			(string) get_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION ),
			'Fresh-install listener must stamp the backfill-complete option.'
		);
	}

	/**
	 * @testdox Should let run_sweep() classify posts after fresh-install listener runs.
	 */
	public function test_run_sweep_proceeds_after_fresh_install_listener(): void {
		$email_id = 'wc_test_divergence_fresh_install_sweep';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Simulate a fresh-install scenario: the migration never ran so the option is missing.
		delete_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY );
		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );

		// Sweep gate trips → no classification work.
		WCEmailTemplateDivergenceDetector::run_sweep();
		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Sweep must early-return when the backfill option is missing.'
		);

		// Listener stamps the option; the next sweep proceeds.
		WCEmailTemplateDivergenceDetector::mark_backfill_complete_on_fresh_install();
		WCEmailTemplateDivergenceDetector::run_sweep();
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Sweep should classify normally once the fresh-install listener has stamped the option.'
		);
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateDivergenceDetector::set_logger( null );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * classify_post() returns each of the three ladder states when fed the corresponding inputs.
	 *
	 * This is the pure unit-level coverage of the classifier; integration coverage is
	 * provided by the remaining tests. Kept as a single method rather than a data provider
	 * because all three scenarios derive from the same fixture-email setup and benefit
	 * from a shared `currentCoreHash`.
	 *
	 * Scenarios exercise the two independent classification axes:
	 *   - has core moved since stamping? (compare currentCoreHash vs. storedSourceHash)
	 *   - has the merchant edited the post since stamping? (compare currentPostHash vs. storedSourceHash)
	 */
	public function test_classification_ladder_covers_all_three_outcomes(): void {
		$email             = $this->register_fixture_email( 'wc_test_divergence_ladder' );
		$canonical_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$current_core_hash = sha1( $canonical_content );

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => $canonical_content,
					'stored_source_hash' => $current_core_hash,
				)
			),
			'Core unchanged and post matches stamp must be in_sync.'
		);

		// Core has moved (stored !== current core) and merchant has not edited the post
		// (post_content still matches the stored stamp) → safe to auto-apply new core.
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => 'stamped-content-from-older-version',
					'stored_source_hash' => sha1( 'stamped-content-from-older-version' ),
				)
			),
			'Core moved but post still matches stamp must be core_updated_uncustomized.'
		);

		// Core has moved AND merchant edited the post (post_content no longer matches
		// the stored stamp) → merchant customisations would be overwritten by auto-apply.
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => 'merchant-edited content that diverges from the stamp',
					'stored_source_hash' => sha1( 'stamped-content-from-older-version' ),
				)
			),
			'Core moved and merchant edited post must be core_updated_customized.'
		);
	}

	/**
	 * Running the sweep twice on unchanged state must write the status meta at most once.
	 *
	 * Guards the equality-gated upsert in run_sweep(): the second sweep observes the status
	 * meta written by the first run and short-circuits, so no second write reaches the DB.
	 */
	public function test_second_sweep_on_unchanged_state_writes_zero_rows(): void {
		$email_id = 'wc_test_divergence_idempotency';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Clear the status the generator stamped at insert so the first
		// sweep has classification work to do; otherwise both sweeps are
		// no-ops and the test loses signal on first-write behaviour.
		delete_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY );

		$write_count = 0;
		$counter     = static function ( $check, int $object_id, string $meta_key ) use ( &$write_count, $post_id ) {
			if ( $object_id === $post_id && WCEmailTemplateDivergenceDetector::STATUS_META_KEY === $meta_key ) {
				++$write_count;
			}
			return $check;
		};
		add_filter( 'update_post_metadata', $counter, 10, 3 );

		WCEmailTemplateDivergenceDetector::run_sweep();
		$writes_after_first_sweep = $write_count;

		WCEmailTemplateDivergenceDetector::run_sweep();
		$writes_after_second_sweep = $write_count;

		remove_filter( 'update_post_metadata', $counter, 10 );

		$this->assertSame( 1, $writes_after_first_sweep, 'First sweep should write the status meta exactly once.' );
		$this->assertSame( 1, $writes_after_second_sweep, 'Second sweep on unchanged state must be a no-op.' );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Posts lacking _wc_email_template_source_hash must be skipped without writing status.
	 */
	public function test_post_with_missing_source_hash_is_skipped(): void {
		$email_id = 'wc_test_divergence_missing_hash';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Simulate a legacy (pre-RSM-137) post by removing both the
		// source-hash and status stamps the modern generator writes.
		delete_post_meta( $post_id, '_wc_email_template_source_hash' );
		delete_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY );

		WCEmailTemplateDivergenceDetector::run_sweep();

		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Legacy posts without a stored source hash must not be classified.'
		);
	}

	/**
	 * When the stamping path captures post-filter content and the same filter stays active
	 * during the sweep, the detector must classify the post as in_sync by construction.
	 *
	 * This is the cross-issue contract between RSM-137 (stamp) and RSM-138 (detect):
	 * both sides route through {@see WCTransactionalEmailPostsGenerator::compute_canonical_post_content()}
	 * so any filter mutation that shifts post_content shifts both hashes identically.
	 */
	public function test_contract_equivalence_under_woocommerce_email_content_post_data_filter(): void {
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ): array {
				$post_data['post_content'] = (string) ( $post_data['post_content'] ?? '' ) . "\n<!-- filter mutation -->";
				return $post_data;
			}
		);

		$email_id = 'wc_test_divergence_filter_contract';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateDivergenceDetector::run_sweep();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Stamping hash and detection hash must agree when the content-post-data filter is active.'
		);
	}

	/**
	 * The sweep must fire `woocommerce_email_template_divergence_sweep_complete`
	 * unconditionally at end of run, so downstream listeners (RSM-139 auto-applier)
	 * can hook the completion event without inspecting detector internals.
	 */
	public function test_run_sweep_fires_completion_action(): void {
		$email_id = 'wc_test_divergence_completion_action';
		$this->generate_stamped_post( $email_id );

		$fired    = 0;
		$listener = static function () use ( &$fired ): void {
			++$fired;
		};
		add_action( 'woocommerce_email_template_divergence_sweep_complete', $listener );

		try {
			WCEmailTemplateDivergenceDetector::run_sweep();
		} finally {
			remove_action( 'woocommerce_email_template_divergence_sweep_complete', $listener );
		}

		$this->assertSame( 1, $fired, 'Completion action must fire exactly once per sweep.' );
	}

	/**
	 * Force-initialize the EmailEditor Integration and Bootstrap so the production
	 * `init`-time hooks (notably `WCEmailTemplateDivergenceDetector::register_meta`)
	 * register on the global hook table, the `woo_email` post type is registered, and
	 * `init` fires so the meta-registration callback runs. Swallows the doing-it-wrong
	 * notices that the full chain triggers when re-registering already-registered
	 * blocks / integrations during a unit-test process; those notices are unrelated
	 * to the meta-registration wiring under test.
	 */
	private function initialize_email_editor_integration(): void {
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry::register' );

		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();

		/**
		 * Fires once WordPress, all plugins, and the theme are fully loaded and instantiated.
		 *
		 * @since 1.5.0
		 */
		do_action( 'init' );
	}

	/**
	 * @testdox Should stamp STATUS_CORE_UPDATED_CUSTOMIZED on a post that differs from canonical core after a stored stamp.
	 */
	public function test_reclassify_stamps_customized_when_post_differs_from_canonical(): void {
		$email_id = 'reclassify_customized';
		$this->register_fixture_email( $email_id );

		$canonical = "<!-- wp:paragraph -->\n<p>Core paragraph.</p>\n<!-- /wp:paragraph -->";
		$post_html = "<!-- wp:paragraph -->\n<p>Merchant paragraph.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $canonical );
		$post_id = $this->create_woo_email_post( $email_id, $post_html );

		// Pre-stamp source_hash to a prior canonical so the classifier sees "core moved".
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( "<!-- wp:paragraph -->\n<p>Old core paragraph.</p>\n<!-- /wp:paragraph -->" ) );

		$status = WCEmailTemplateDivergenceDetector::reclassify( $post_id );

		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED, $status );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * @testdox Should stamp STATUS_IN_SYNC when the post matches the canonical render at the stored stamp.
	 */
	public function test_reclassify_stamps_in_sync_when_post_matches_canonical(): void {
		$email_id = 'reclassify_in_sync';
		$this->register_fixture_email( $email_id );

		$canonical = "<!-- wp:paragraph -->\n<p>Same on both sides.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $canonical );
		$post_id = $this->create_woo_email_post( $email_id, $canonical );

		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( $canonical ) );

		$status = WCEmailTemplateDivergenceDetector::reclassify( $post_id );

		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $status );
	}

	/**
	 * Build a WC_Email stub backed by the third-party-with-version.php fixture, inject it
	 * into WC_Emails::$emails, and opt the email ID into the block-editor filter so the
	 * sync registry picks it up. Resets the registry cache so subsequent reads see the
	 * fixture.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for divergence tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover divergence scenarios.' );
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
	 * Using the live generator ensures RSM-137's stamping logic actually runs — the detector
	 * then observes precisely what the generator would persist in production.
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

		// Sanity check: RSM-137 stamped all three sync meta keys.
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_template_version', true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ) );

		return $post_id;
	}

	/**
	 * Hook the canonical content filter so `compute_canonical_post_content()`
	 * returns the supplied string for the given email_id, bypassing the
	 * file-rendered template body. Lets tests express "what core would render"
	 * directly inline.
	 *
	 * @param string $email_id The email ID to override content for.
	 * @param string $content  The canonical content to inject.
	 */
	private function use_canonical_content( string $email_id, string $content ): void {
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data, string $type ) use ( $email_id, $content ): array {
				if ( $type === $email_id ) {
					$post_data['post_content'] = $content;
				}
				return $post_data;
			},
			10,
			2
		);
	}

	/**
	 * Create a `woo_email` post and associate it with the supplied email_id
	 * via the canonical option key the manager expects.
	 *
	 * @param string $email_id     The email ID to associate.
	 * @param string $post_content Initial post content.
	 * @return int Post ID.
	 */
	private function create_woo_email_post( string $email_id, string $post_content ): int {
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Fixture for ' . $email_id,
				'post_name'    => $email_id,
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => $post_content,
				'post_status'  => 'publish',
			)
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$this->posts_manager->save_email_template_post_id( $email_id, $post_id );

		return (int) $post_id;
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

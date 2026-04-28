<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncBackfill;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the RSM-149 sync-meta backfill.
 */
class WCEmailTemplateSyncBackfillTest extends \WC_Unit_Test_Case {
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

		// Eagerly boot \WC_Emails so the \WC_Email class is autoloaded before any
		// test reflects on it via getMockBuilder() / onlyMethods().
		\WC_Emails::instance();

		$this->fixtures_base = dirname( __DIR__ ) . '/WCTransactionalEmails/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		// The backfill is gated by the completion option being unset — ensure it is.
		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_actions( WCEmailTemplateSyncBackfill::BACKFILL_COMPLETE_ACTION );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateSyncBackfill::set_logger( null );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * Case A: post_content already matches the canonical core render.
	 * Expectation: content untouched, four meta keys stamped, status = in_sync.
	 */
	public function test_case_a_stamps_in_sync_when_content_matches_current_core(): void {
		$email_id = 'wc_test_backfill_case_a';
		$email    = $this->register_fixture_email( $email_id );

		$canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$post_id = $this->create_unstamped_post( $email_id, $canonical, true );

		WCEmailTemplateSyncBackfill::run();

		$post_after = $this->require_post( $post_id );

		$this->assertSame( $canonical, (string) $post_after->post_content, 'Case A must not rewrite post_content.' );
		$this->assertSame(
			sha1( $canonical ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true )
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
		$this->assertNotSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true )
		);
		$this->assertNotSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true )
		);
	}

	/**
	 * Case B: content diverges from canonical but the post has never been edited.
	 * Expectation: post_content rewritten to canonical, status = in_sync.
	 */
	public function test_case_b_rewrites_post_content_when_never_edited(): void {
		$email_id = 'wc_test_backfill_case_b';
		$email    = $this->register_fixture_email( $email_id );

		$canonical   = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$legacy_body = "<!-- wp:paragraph -->\n<p>Legacy content from an older core version.</p>\n<!-- /wp:paragraph -->";

		// "Never edited" is modelled by identical local + GMT timestamps, which `create_unstamped_post( ..., $never_edited = true )` sets up.
		$post_id = $this->create_unstamped_post( $email_id, $legacy_body, true );

		WCEmailTemplateSyncBackfill::run();

		$post_after = $this->require_post( $post_id );

		$this->assertSame( $canonical, (string) $post_after->post_content, 'Case B must rewrite post_content to the canonical render.' );
		$this->assertSame(
			sha1( $canonical ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true )
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Case C: content diverges from canonical AND the post has been edited.
	 * Expectation: content untouched, source_hash = sha1(canonical) (NOT sha1(post_content)),
	 * status seeded to core_updated_customized to match what the divergence detector
	 * communicates for this state.
	 */
	public function test_case_c_seeds_core_updated_customized_when_customized(): void {
		$email_id = 'wc_test_backfill_case_c';
		$email    = $this->register_fixture_email( $email_id );

		$canonical     = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$merchant_body = "<!-- wp:paragraph -->\n<p>Merchant-authored customisations must survive the backfill.</p>\n<!-- /wp:paragraph -->";

		$post_id = $this->create_unstamped_post( $email_id, $merchant_body, false );

		WCEmailTemplateSyncBackfill::run();

		$post_after = $this->require_post( $post_id );

		$this->assertSame(
			$merchant_body,
			(string) $post_after->post_content,
			'Case C must not touch merchant-edited post_content.'
		);
		$this->assertSame(
			sha1( $canonical ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'Case C must stamp sha1(canonical) — never sha1(post_content) — to prevent a catastrophic core_updated_uncustomized classification on the next core bump.'
		);
		$this->assertNotSame(
			sha1( $merchant_body ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true )
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Case B rewrite failure: wp_update_post() returns a WP_Error (silent
	 * failure because `$wp_error = true`). The migration is one-shot — the
	 * `woocommerce_db_version` fence flips on completion and this callback
	 * never runs again — so the post cannot be left unstamped (the detector
	 * would skip it with a recurring warning forever). Instead, the post must
	 * still be stamped, but with Case C semantics so it surfaces for merchant
	 * review rather than being silently marked in_sync over stale content.
	 *
	 * Expectation when the rewrite fails:
	 *   - post_content is preserved (legacy body, because the rewrite failed).
	 *   - source_hash = sha1(canonical) (same stamp a real Case C writes, so
	 *     the detector's "core has not moved" branch returns null and does not
	 *     overwrite the status on the next sweep).
	 *   - status = core_updated_customized (so the post is visible in the
	 *     divergence UI as needing merchant attention).
	 *   - version + last_synced_at stamps are still written (no orphan posts).
	 */
	public function test_case_b_falls_back_to_core_updated_customized_when_rewrite_fails(): void {
		$email_id = 'wc_test_backfill_case_b_rewrite_failure';
		$email    = $this->register_fixture_email( $email_id );

		$canonical   = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$legacy_body = "<!-- wp:paragraph -->\n<p>Legacy content from an older core version.</p>\n<!-- /wp:paragraph -->";

		$post_id = $this->create_unstamped_post( $email_id, $legacy_body, true );

		// Force wp_update_post() to return a WP_Error during the Case B rewrite.
		// `wp_insert_post_empty_content` short-circuits both wp_insert_post and
		// wp_update_post (the latter delegates to the former), and because the
		// backfill passes `$wp_error = true`, we get a WP_Error back rather
		// than an exception — exactly the silent-failure path we're testing.
		add_filter( 'wp_insert_post_empty_content', '__return_true' );
		try {
			WCEmailTemplateSyncBackfill::run();
		} finally {
			remove_filter( 'wp_insert_post_empty_content', '__return_true' );
		}

		$post_after = $this->require_post( $post_id );

		$this->assertSame(
			$legacy_body,
			(string) $post_after->post_content,
			'post_content must be preserved when the Case B rewrite fails.'
		);
		$this->assertSame(
			sha1( $canonical ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'Source hash must still stamp sha1(canonical) so the detector treats it the same as a real Case C on the next sweep.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'A failed Case B rewrite must downgrade status to core_updated_customized so the post surfaces for merchant review.'
		);
		$this->assertNotSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'Version stamp must still be written on rewrite failure — leaving posts unstamped would orphan them permanently.'
		);
		$this->assertNotSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
			'Last-synced stamp must still be written on rewrite failure — leaving posts unstamped would orphan them permanently.'
		);
	}

	/**
	 * Pure-function coverage for `classify()`. Exercises the timestamp truth
	 * table the classifier is contracted to handle — without depending on
	 * wp_insert_post()'s date normalisation or MySQL sql_mode behaviour.
	 *
	 * Note: `was_never_edited()` uses an OR across the GMT pair and the local
	 * pair (see its docblock for the rationale and known limitation), so
	 * scenarios where every timestamp pair is simultaneously corrupt are
	 * explicitly out of scope for this test — the classifier may misclassify
	 * those and we've accepted that trade-off.
	 *
	 * Each scenario pairs a synthetic row with an expected case. Ordering
	 * mirrors the branches in `classify()` / `was_never_edited()` so a
	 * regression points at the specific branch that broke.
	 *
	 * @dataProvider provide_classify_scenarios
	 *
	 * @param string $scenario          Human-readable label surfaced by PHPUnit.
	 * @param array  $row_fields        Fields to seed on the \stdClass row.
	 * @param string $post_content      Post content to hash.
	 * @param string $canonical_content Canonical core content to hash.
	 * @param string $expected_case     One of WCEmailTemplateSyncBackfill::CASE_A|B|C.
	 */
	public function test_classify_truth_table( string $scenario, array $row_fields, string $post_content, string $canonical_content, string $expected_case ): void {
		$row               = (object) array_merge( array( 'post_content' => $post_content ), $row_fields );
		$current_core_hash = sha1( $canonical_content );

		$method = ( new \ReflectionClass( WCEmailTemplateSyncBackfill::class ) )->getMethod( 'classify' );
		$method->setAccessible( true );
		$actual = (string) $method->invoke( null, $row, $current_core_hash );

		$this->assertSame( $expected_case, $actual, sprintf( 'Scenario: %s', $scenario ) );
	}

	/**
	 * Scenarios for {@see self::test_classify_truth_table()}.
	 *
	 * @return array<string, array{0:string,1:array<string,string>,2:string,3:string,4:string}>
	 */
	public function provide_classify_scenarios(): array {
		$canonical     = '<!-- wp:paragraph --><p>Canonical core.</p><!-- /wp:paragraph -->';
		$merchant_edit = '<!-- wp:paragraph --><p>Merchant edit.</p><!-- /wp:paragraph -->';
		$created_at    = '2023-01-01 00:00:00';
		$edited_at     = '2024-06-15 12:34:56';
		$zero_date     = '0000-00-00 00:00:00';
		$case_a        = WCEmailTemplateSyncBackfill::CASE_A;
		$case_b        = WCEmailTemplateSyncBackfill::CASE_B;
		$case_c        = WCEmailTemplateSyncBackfill::CASE_C;

		return array(
			'Case A: content matches core (short-circuits before timestamps)' => array(
				'Case A short-circuit',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $edited_at,
					'post_date_gmt'     => $created_at,
					'post_modified_gmt' => $edited_at,
				),
				$canonical,
				$canonical,
				$case_a,
			),
			'Case B: GMT pair valid and equal (never edited)' => array(
				'GMT valid + equal -> never edited',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $created_at,
					'post_date_gmt'     => $created_at,
					'post_modified_gmt' => $created_at,
				),
				$merchant_edit,
				$canonical,
				$case_b,
			),
			'Case C: GMT pair valid and differs (edited)' => array(
				'GMT valid + differs -> edited',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $edited_at,
					'post_date_gmt'     => $created_at,
					'post_modified_gmt' => $edited_at,
				),
				$merchant_edit,
				$canonical,
				$case_c,
			),
			'Case B via fallback: both GMT zero, local pair equal (never edited)' => array(
				'GMT zero + local equal -> fallback to local -> never edited',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $created_at,
					'post_date_gmt'     => $zero_date,
					'post_modified_gmt' => $zero_date,
				),
				$merchant_edit,
				$canonical,
				$case_b,
			),
			'Case C via local fallback: GMT pair differs (edited), local pair also differs' => array(
				'GMT differs + local differs -> edited',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $edited_at,
					'post_date_gmt'     => $created_at,
					'post_modified_gmt' => $zero_date,
				),
				$merchant_edit,
				$canonical,
				$case_c,
			),
			'Case B via OR: GMT pair differs but local pair is equal (legacy never-edited signal)' => array(
				'GMT differs + local equal -> OR resolves to never edited',
				array(
					'post_date'         => $created_at,
					'post_modified'     => $created_at,
					'post_date_gmt'     => $zero_date,
					'post_modified_gmt' => $created_at,
				),
				$merchant_edit,
				$canonical,
				$case_b,
			),
		);
	}

	/**
	 * finalize() flips the BACKFILL_COMPLETE_OPTION to 'yes' BEFORE firing the
	 * completion action, so listeners (notably the RSM-138 detector) see the
	 * final state when they run.
	 */
	public function test_finalize_flips_option_before_firing_action(): void {
		$this->register_fixture_email( 'wc_test_backfill_finalize_ordering' );

		$option_seen_inside_action = null;
		$action_invocations        = 0;

		add_action(
			WCEmailTemplateSyncBackfill::BACKFILL_COMPLETE_ACTION,
			static function () use ( &$option_seen_inside_action, &$action_invocations ): void {
				$option_seen_inside_action = get_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
				++$action_invocations;
			}
		);

		$result = WCEmailTemplateSyncBackfill::run();

		$this->assertFalse( $result, 'run() must return false (one-shot).' );
		$this->assertSame( 1, $action_invocations, 'Completion action must fire exactly once per run.' );
		$this->assertSame(
			'yes',
			$option_seen_inside_action,
			'BACKFILL_COMPLETE_OPTION must already be "yes" when the completion action fires.'
		);
		$this->assertSame(
			'yes',
			(string) get_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION ),
			'BACKFILL_COMPLETE_OPTION must remain "yes" after run() returns.'
		);
	}

	/**
	 * Build a WC_Email stub backed by the fixture template, inject it into
	 * \WC_Emails::$emails, and opt the email ID into the block-editor filter so
	 * the sync registry picks it up. Mirrors the helper used by the detector
	 * test suite.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_title', 'get_description' ) )
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for backfill tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover RSM-149 backfill scenarios.' );
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
	 * Create a `woo_email` post that is NOT stamped with RSM-137 sync meta,
	 * mirroring a legacy post that the backfill should pick up.
	 *
	 * The GMT timestamps can be overridden independently of the local
	 * timestamps to simulate the real-world anomaly where legacy/broken insert
	 * paths left `post_date_gmt` and/or `post_modified_gmt` as the sentinel
	 * `'0000-00-00 00:00:00'` while local dates remained healthy.
	 *
	 * @param string      $email_id                   Email ID to link the post to via the manager option.
	 * @param string      $post_content               Initial post content.
	 * @param bool        $never_edited               When true, set post_date === post_modified (Case B eligibility); otherwise, set them apart (Case C).
	 * @param string|null $post_date_gmt_override     Optional explicit value for post_date_gmt; defaults to the local post_date.
	 * @param string|null $post_modified_gmt_override Optional explicit value for post_modified_gmt; defaults to the local post_modified.
	 * @return int The created post ID.
	 */
	private function create_unstamped_post(
		string $email_id,
		string $post_content,
		bool $never_edited,
		?string $post_date_gmt_override = null,
		?string $post_modified_gmt_override = null
	): int {
		$created_at  = '2023-01-01 00:00:00';
		$modified_at = $never_edited ? $created_at : '2024-06-15 12:34:56';

		$post_date_gmt     = $post_date_gmt_override ?? $created_at;
		$post_modified_gmt = $post_modified_gmt_override ?? $modified_at;

		$inserted = wp_insert_post(
			array(
				'post_type'         => Integration::EMAIL_POST_TYPE,
				'post_status'       => 'publish',
				'post_title'        => 'Backfill fixture for ' . $email_id,
				'post_content'      => $post_content,
				'post_date'         => $created_at,
				'post_date_gmt'     => $post_date_gmt,
				'post_modified'     => $modified_at,
				'post_modified_gmt' => $post_modified_gmt,
			),
			true
		);

		if ( is_wp_error( $inserted ) ) {
			throw new \RuntimeException( 'wp_insert_post failed: ' . esc_html( $inserted->get_error_message() ) );
		}

		$post_id = (int) $inserted;
		$this->assertGreaterThan( 0, $post_id );

		// wp_insert_post will overwrite post_modified* to `now` when `edit_date` is not set — set it explicitly via the DB.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'         => $created_at,
				'post_date_gmt'     => $post_date_gmt,
				'post_modified'     => $modified_at,
				'post_modified_gmt' => $post_modified_gmt,
			),
			array( 'ID' => $post_id )
		);
		clean_post_cache( $post_id );

		// Link post ID <-> email ID so WCTransactionalEmailPostsManager::get_email_type_from_post_id() resolves.
		$this->posts_manager->save_email_template_post_id( $email_id, $post_id );

		return $post_id;
	}

	/**
	 * Fetch a post by ID and assert/narrow to `WP_Post`. Throws rather than
	 * calling PHPUnit's `$this->fail()` so the type narrowing is visible to
	 * static analysis and failures produce a readable stack trace.
	 *
	 * @param int $post_id The post ID to load.
	 * @return \WP_Post
	 */
	private function require_post( int $post_id ): \WP_Post {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			throw new \RuntimeException( sprintf( 'Expected WP_Post for ID %s, got %s.', esc_html( (string) $post_id ), esc_html( gettype( $post ) ) ) );
		}

		return $post;
	}

	/**
	 * Remove any stubs we injected into \WC_Emails::$emails during the test.
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

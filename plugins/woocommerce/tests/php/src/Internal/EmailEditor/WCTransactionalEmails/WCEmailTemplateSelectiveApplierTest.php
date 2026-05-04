<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateChangeSummary;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSelectiveApplier;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateSelectiveApplier class.
 */
class WCEmailTemplateSelectiveApplierTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the change-summary fixtures directory (reused).
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

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateChangeSummary::reset_cache();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateChangeSummary::reset_cache();
		WCEmailTemplateChangeSummary::set_logger( null );
		WCEmailTemplateSelectiveApplier::set_logger( null );

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * One conflict, no `choices` → default is `keep_yours`. Merged content
	 * keeps the merchant's edited paragraph text.
	 */
	public function test_apply_selectively_keep_yours_default_skips_text_change(): void {
		$email_id = 'sa_keep_yours_default';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Original copy.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Edited copy.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $result );
		$this->assertSame( 'applied', $result['status'] );
		$this->assertStringContainsString( 'Edited copy.', $result['merged_content'] );
		$this->assertStringNotContainsString( 'Original copy.', $result['merged_content'] );
	}

	/**
	 * One conflict with `decision: 'use_core'` → merged paragraph reads core's text.
	 */
	public function test_apply_selectively_use_core_replaces_text(): void {
		$email_id = 'sa_use_core';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core copy.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Merchant copy.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively(
			$post_id,
			array(
				array(
					'path'     => array( 0 ),
					'decision' => 'use_core',
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'applied', $result['status'] );
		$this->assertStringContainsString( 'Core copy.', $result['merged_content'] );
		$this->assertStringNotContainsString( 'Merchant copy.', $result['merged_content'] );
	}

	/**
	 * Core has a paragraph not in post; merged content includes the new
	 * paragraph at the position core has it (auto-resolved Apply core).
	 */
	public function test_apply_selectively_inserts_core_added_blocks(): void {
		$email_id = 'sa_added_block';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>First.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>New from core.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Last.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>First.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Last.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'New from core.', $result['merged_content'] );
		$this->assertStringContainsString( 'First.', $result['merged_content'] );
		$this->assertStringContainsString( 'Last.', $result['merged_content'] );
	}

	/**
	 * Post has a custom block not in core; merged content still contains it
	 * (auto-resolved Keep yours — `removed_blocks` are preserved).
	 */
	public function test_apply_selectively_preserves_merchant_only_blocks(): void {
		$email_id = 'sa_preserve_merchant_block';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core only.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Core only.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"x\"/></figure>\n<!-- /wp:image -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'wp:image', $result['merged_content'], 'Merchant-only image block must be preserved.' );
	}

	/**
	 * Group-wrap of an existing block: merchant has Group [Paragraph], core has
	 * Paragraph at top level. v1 punts structural changes — merchant's Group
	 * stays; the response surfaces `structural_skipped: true`.
	 */
	public function test_apply_selectively_skips_structural_changes_in_v1(): void {
		$email_id = 'sa_structural_skip';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Wrapped.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:group -->\n<div class=\"wp-block-group\">"
			. "<!-- wp:paragraph -->\n<p>Wrapped.</p>\n<!-- /wp:paragraph -->"
			. "</div>\n<!-- /wp:group -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['structural_skipped'], 'Structural delta on a Group wrap must flag structural_skipped.' );
		$this->assertStringContainsString( 'wp:group', $result['merged_content'], 'Merchant Group wrapper must be preserved.' );
	}

	/**
	 * After apply, the snapshot meta exists with the original content and a
	 * UUID-shaped revision_id.
	 */
	public function test_apply_selectively_writes_pre_apply_snapshot(): void {
		$email_id = 'sa_snapshot';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$this->assertIsArray( $result );

		$snapshot = get_post_meta( $post_id, WCEmailTemplateSelectiveApplier::SNAPSHOT_META_KEY, true );
		$this->assertIsArray( $snapshot );
		$this->assertSame( $post_content, $snapshot['content'] );
		$this->assertSame( $result['revision_id'], $snapshot['revision_id'] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f-]{36}$/', $snapshot['revision_id'] );
	}

	/**
	 * Successful apply stamps the four sync meta keys.
	 */
	public function test_apply_selectively_stamps_sync_meta_on_success(): void {
		$email_id = 'sa_sync_meta';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$this->assertIsArray( $result );

		$this->assertSame(
			$result['version_to'],
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true )
		);
		$this->assertSame(
			sha1( $result['merged_content'] ),
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true )
		);
		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true )
		);
		// Status is now classifier-derived; this scenario applies with the
		// default keep_yours decision so merged_content still differs from
		// canonical core. The classifier therefore returns
		// `core_updated_uncustomized` (core moved relative to the freshly
		// written source_hash; post matches that stamp). Previously this
		// test asserted STATUS_IN_SYNC — that was implicitly testing the
		// hard-coded stamp bug fixed by routing through reclassify().
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * @testdox Should restore post_content and consume the snapshot meta on undo.
	 *
	 * Apply → undo round-trip: post_content matches the original and the
	 * snapshot meta is consumed. Status is recomputed by the classifier
	 * after the restore (see `test_undo_reclassifies_status_after_restoring_snapshot`
	 * for the core-moved scenario), so this test only pins the
	 * content-and-snapshot invariants — not a stale "prior status survives"
	 * assertion that the snapshot used to carry.
	 */
	public function test_undo_restores_pre_apply_snapshot(): void {
		$email_id = 'sa_undo_round_trip';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$apply_result = WCEmailTemplateSelectiveApplier::apply_selectively(
			$post_id,
			array(
				array(
					'path'     => array( 0 ),
					'decision' => 'use_core',
				),
			)
		);
		$this->assertIsArray( $apply_result );

		$undo_result = WCEmailTemplateSelectiveApplier::undo( $post_id, $apply_result['revision_id'] );

		$this->assertIsArray( $undo_result );
		$this->assertSame( 'restored', $undo_result['status'] );
		$this->assertSame( $post_content, $undo_result['restored_content'] );

		$persisted = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $persisted );
		$this->assertSame( $post_content, $persisted->post_content );

		// Status meta must be populated after undo; the precise classifier output is
		// pinned by `test_undo_reclassifies_status_after_restoring_snapshot`.
		$this->assertNotEmpty( get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ) );

		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateSelectiveApplier::SNAPSHOT_META_KEY, true ),
			'Snapshot meta must be consumed by undo.'
		);
	}

	/**
	 * @testdox Should reclassify after undo rather than restore the stored prior_status verbatim.
	 */
	public function test_undo_reclassifies_status_after_restoring_snapshot(): void {
		$email_id = 'undo_reclassify';
		$this->register_fixture_email( $email_id );

		$pre_apply_canonical = "<!-- wp:paragraph -->\n<p>Old canonical.</p>\n<!-- /wp:paragraph -->";
		$post_html           = "<!-- wp:paragraph -->\n<p>Old canonical.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $pre_apply_canonical );
		$post_id = $this->create_woo_email_post( $email_id, $post_html );

		// Stamp a matching source_hash so initial state is in_sync.
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( $pre_apply_canonical ) );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC );

		// Apply against the same canonical (no real diff — just exercise the path so a snapshot exists).
		$apply_result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$this->assertIsArray( $apply_result );

		// Now mutate the canonical underneath — simulating a core release between apply and undo.
		$new_canonical = "<!-- wp:paragraph -->\n<p>New canonical from a core release.</p>\n<!-- /wp:paragraph -->";
		$this->use_canonical_content( $email_id, $new_canonical );

		$undo_result = WCEmailTemplateSelectiveApplier::undo( $post_id, $apply_result['revision_id'] );
		$this->assertIsArray( $undo_result );

		// After undo, the post is back to its pre-apply content but core has moved.
		// Classifier output: stored hash != current canonical → core moved; post matches stored hash → uncustomized.
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'undo() must reclassify against current canonical, not blindly restore the snapshot prior_status.'
		);
	}

	/**
	 * Undo without a prior apply (no snapshot) returns 410.
	 */
	public function test_undo_returns_410_when_no_snapshot(): void {
		$email_id = 'sa_undo_no_snapshot';
		$this->register_fixture_email( $email_id );

		$content = "<!-- wp:paragraph -->\n<p>Untouched.</p>\n<!-- /wp:paragraph -->";
		$this->use_canonical_content( $email_id, $content );
		$post_id = $this->create_woo_email_post( $email_id, $content );

		$result = WCEmailTemplateSelectiveApplier::undo( $post_id, 'never-applied' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'undo_unavailable', $result->get_error_code() );
		$this->assertSame( 410, $result->get_error_data()['status'] );
	}

	/**
	 * Undo with a stale revision_id (no longer matches the latest snapshot)
	 * returns 410.
	 */
	public function test_undo_revision_id_mismatch_returns_410(): void {
		$email_id = 'sa_undo_stale';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$apply_result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$this->assertIsArray( $apply_result );

		$result = WCEmailTemplateSelectiveApplier::undo( $post_id, 'a-different-uuid' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'undo_unavailable', $result->get_error_code() );
		$this->assertSame( 410, $result->get_error_data()['status'] );
	}

	/**
	 * `is_fallback: true` from the change-summary (e.g. post not in registry,
	 * inversion guard tripped) refuses to apply with 422.
	 */
	public function test_apply_selectively_returns_422_when_summary_is_fallback(): void {
		// No fixture email registered, so the change-summary returns is_fallback: true.
		$post_id = $this->create_woo_email_post(
			'sa_unregistered',
			"<!-- wp:paragraph -->\n<p>Anything.</p>\n<!-- /wp:paragraph -->"
		);

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$status_code = $result->get_error_data()['status'] ?? null;
		$this->assertContains(
			$status_code,
			array( 404, 422 ),
			'Unregistered post must return either 404 (no email) or 422 (no actionable summary).'
		);
	}

	/**
	 * Apply followed by a second apply: the snapshot is overwritten (single-
	 * step undo), and the second revision_id is what undo requires.
	 */
	public function test_second_apply_overwrites_pre_apply_snapshot(): void {
		$email_id = 'sa_second_apply';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$first  = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$second = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $first );
		$this->assertIsArray( $second );
		$this->assertNotSame( $first['revision_id'], $second['revision_id'] );

		// Undo with the FIRST revision_id should now fail (snapshot was overwritten).
		$undo_first = WCEmailTemplateSelectiveApplier::undo( $post_id, $first['revision_id'] );
		$this->assertInstanceOf( \WP_Error::class, $undo_first );

		// Undo with the SECOND revision_id succeeds.
		$undo_second = WCEmailTemplateSelectiveApplier::undo( $post_id, $second['revision_id'] );
		$this->assertIsArray( $undo_second );
	}

	/**
	 * Apply rewrites the deprecated `wp:woo/email-content` namespace to the
	 * canonical `wp:woocommerce/email-content` form, including the
	 * `wp-block-woo-email-content` CSS class on the inner div. The
	 * migration runs unconditionally during apply (independent of `choices`)
	 * because `woo/email-content` is a known alias of the canonical block,
	 * not a customisation worth preserving. The response surfaces it via
	 * `aliases_migrated`.
	 */
	public function test_apply_selectively_migrates_woo_email_content_to_woocommerce_namespace(): void {
		$email_id = 'sa_alias_migration';
		$this->register_fixture_email( $email_id );

		$core_content = '<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->'
			. '<div class="wp-block-woocommerce-email-content"> ##WOO_CONTENT## </div>'
			. '<!-- /wp:woocommerce/email-content -->';

		$post_content = '<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->'
			. '<div class="wp-block-woo-email-content"> ##WOO_CONTENT## </div>'
			. '<!-- /wp:woo/email-content -->';

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );

		$this->assertIsArray( $result );
		$this->assertSame( 'applied', $result['status'] );

		$merged = (string) $result['merged_content'];
		$this->assertStringContainsString( 'wp:woocommerce/email-content', $merged, 'Block name comment must be migrated to canonical form.' );
		$this->assertStringContainsString( 'wp-block-woocommerce-email-content', $merged, 'CSS class must be migrated to canonical form.' );
		$this->assertStringNotContainsString( 'wp:woo/email-content', $merged, 'Deprecated namespace comment must not survive the apply.' );
		$this->assertStringNotContainsString( 'wp-block-woo-email-content', $merged, 'Deprecated CSS class must not survive the apply.' );

		// Block attrs and inner content are preserved — only the namespace label changes.
		$this->assertStringContainsString( '"lock":{"move":false,"remove":true}', $merged, 'Block attrs must be preserved.' );
		$this->assertStringContainsString( '##WOO_CONTENT##', $merged, 'Placeholder content must be preserved.' );

		$this->assertContains( 'woo/email-content', $result['aliases_migrated'], 'Response must surface the migration via aliases_migrated.' );
		$this->assertCount( 1, $result['aliases_migrated'], 'aliases_migrated must be deduped.' );

		// The persisted post reflects the migrated content too.
		$persisted = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $persisted );
		$this->assertStringContainsString( 'wp:woocommerce/email-content', $persisted->post_content );
		$this->assertStringNotContainsString( 'wp:woo/email-content', $persisted->post_content );
	}

	/**
	 * @testdox Should NOT stamp STATUS_IN_SYNC after an empty-choices apply when the merged content still differs from canonical.
	 */
	public function test_apply_selectively_with_empty_choices_does_not_falsely_stamp_in_sync(): void {
		$email_id = 'apply_keep_yours_status';
		$this->register_fixture_email( $email_id );

		$canonical = "<!-- wp:paragraph -->\n<p>Hi there [woocommerce/customer-username],</p>\n<!-- /wp:paragraph -->";
		$post_html = "<!-- wp:paragraph -->\n<p>Hello, [woocommerce/customer-username]!</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $canonical );
		$post_id = $this->create_woo_email_post( $email_id, $post_html );

		// Pre-stamp a stale source_hash so the classifier sees "core moved".
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( "<!-- wp:paragraph -->\n<p>Old core copy.</p>\n<!-- /wp:paragraph -->" ) );

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, array() );
		$this->assertIsArray( $result );

		// The reviewer-flagged bug stamped in_sync unconditionally. Status
		// is now classifier-derived: with empty choices the merged content
		// equals the merchant's content, the source_hash is rewritten to
		// sha1(merged), and `classify_post` compares the *new* canonical
		// hash (still differs from merged) against that stamp. Because the
		// post matches the just-written stamp, the classifier reports
		// `core_updated_uncustomized` (core moved; post matches stamp).
		// The key invariant the bug-regression test pins is "not in_sync".
		$this->assertNotSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Applying with all keep_yours must not stamp in_sync — the post still differs from canonical.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Classifier output: stored hash != current canonical → core moved; post matches the just-written stamp → uncustomized.'
		);
	}

	/**
	 * Apply on a post outside the sync registry returns 404 (no email
	 * resolved) — same gate as the change-summary endpoint.
	 */
	public function test_apply_selectively_returns_404_for_post_with_no_email_type(): void {
		$unassociated = $this->factory()->post->create_and_get(
			array(
				'post_title'  => 'Unassociated',
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => 'draft',
			)
		);

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( (int) $unassociated->ID, array() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'email_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * Register a fixture email and wire up the sync registry to pick it up.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for selective-applier tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover selective-applier scenarios.' );
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
	 * Hook the canonical content filter so `compute_canonical_post_content()`
	 * returns the supplied string for the given email_id, bypassing the
	 * file-rendered template body.
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
	 * Create a `woo_email` post and associate it with the supplied email_id.
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

		foreach ( $this->injected_email_keys as $class_key ) {
			unset( $current[ $class_key ] );
		}

		$property->setValue( $emails_container, $current );
		$this->injected_email_keys = array();
	}
}

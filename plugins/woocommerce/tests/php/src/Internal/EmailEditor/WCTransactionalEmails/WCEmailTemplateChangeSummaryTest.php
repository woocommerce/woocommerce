<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateChangeSummary;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateChangeSummary class.
 */
class WCEmailTemplateChangeSummaryTest extends \WC_Unit_Test_Case {
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

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		// Singleton caches survive test transaction rollback and would otherwise
		// leak stale post_id <-> email_type mappings into subsequent tests.
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

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * Mixed-changes happy path: covers added blocks, removed blocks, and copy
	 * changes against a single fixture in one assertion pass.
	 *
	 * @testdox Should return a structured summary covering added, removed, and copy-changed blocks in a single pass.
	 */
	public function test_summarize_returns_structured_payload_for_mixed_changes(): void {
		$email_id = 'change_summary_mixed';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello there.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Original line.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Goodbye block.</p>\n<!-- /wp:paragraph -->";

		// Merchant edited the third paragraph and added a custom image; removed the goodbye paragraph.
		$post_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello there.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Edited line.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"x\"/></figure>\n<!-- /wp:image -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertArrayHasKey( 'is_fallback', $result );
		$this->assertFalse( $result['is_fallback'] );

		// Yours → core convention: applying would REMOVE the merchant's image
		// (it's in their post, not in core) and ADD the goodbye paragraph
		// (it's in core, not in their post). Each entry carries a `path`.
		$this->assertArrayHasKey( 'removed_blocks', $result );
		$this->assertContains( 'Image', array_column( $result['removed_blocks'], 'label' ) );

		$this->assertArrayHasKey( 'added_blocks', $result );
		$this->assertContains( 'Paragraph', array_column( $result['added_blocks'], 'label' ) );

		// Path field present on every rich entry.
		foreach ( $result['removed_blocks'] as $entry ) {
			$this->assertArrayHasKey( 'path', $entry );
			$this->assertIsArray( $entry['path'] );
		}
		foreach ( $result['added_blocks'] as $entry ) {
			$this->assertArrayHasKey( 'path', $entry );
			$this->assertIsArray( $entry['path'] );
		}

		$this->assertArrayHasKey( 'copy_changes', $result );
		$this->assertCount( 1, $result['copy_changes'] );
		$this->assertSame( 'Paragraph', $result['copy_changes'][0]['block'] );
		// `before` = merchant's current post; `after` = canonical core text.
		$this->assertSame( 'Edited line.', $result['copy_changes'][0]['before'] );
		$this->assertSame( 'Original line.', $result['copy_changes'][0]['after'] );
		$this->assertArrayHasKey( 'path', $result['copy_changes'][0] );
		$this->assertIsArray( $result['copy_changes'][0]['path'] );

		$this->assertArrayHasKey( 'summary_lines', $result );
		$this->assertNotEmpty( $result['summary_lines'] );
		$this->assertContains( 'Removed Image block', $result['summary_lines'] );
	}

	/**
	 * Namespace-alias normalization: post uses `woo/email-content` while core
	 * uses `woocommerce/email-content`. Should match as the same block, not
	 * surface as add+remove.
	 *
	 * @testdox Should match `woo/email-content` and `woocommerce/email-content` as the same block via namespace-alias normalization.
	 */
	public function test_summarize_normalizes_namespace_aliased_blocks(): void {
		$email_id = 'change_summary_alias';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:woocommerce/email-content -->\n<div class=\"wp-block-woocommerce-email-content\">##WOO_CONTENT##</div>\n<!-- /wp:woocommerce/email-content -->";

		$post_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:woo/email-content -->\n<div class=\"wp-block-woo-email-content\">##WOO_CONTENT##</div>\n<!-- /wp:woo/email-content -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$this->assertEmpty( $result['added_blocks'], 'Aliased block must not appear in added_blocks.' );
		$this->assertEmpty( $result['removed_blocks'], 'Aliased block must not appear in removed_blocks.' );
	}

	/**
	 * Depth-asymmetric input: merchant wraps two paragraphs in a Group while
	 * core stays flat. The flatten-then-LCS pipeline must surface this as a
	 * structural `nest` change, not as a paragraph add/remove cascade.
	 *
	 * @testdox Should surface depth asymmetry as a structural `nest` change rather than a paragraph add/remove cascade.
	 */
	public function test_summarize_handles_depth_asymmetry_via_dfs_flatten(): void {
		$email_id = 'change_summary_nest';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->";

		$post_content = "<!-- wp:group -->\n<div class=\"wp-block-group\">"
			. "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->"
			. "</div>\n<!-- /wp:group -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$this->assertNotContains( 'Paragraph', array_column( $result['added_blocks'], 'label' ) );
		$this->assertNotContains( 'Paragraph', array_column( $result['removed_blocks'], 'label' ) );

		$this->assertNotEmpty( $result['structural_changes'] );
		$kinds = array_map( static fn( $c ): string => (string) ( $c['kind'] ?? '' ), $result['structural_changes'] );
		$this->assertContains( 'nest', $kinds );

		// Wrapper-suppression rule: every matched paragraph emits a "Moved
		// Paragraph into top level" entry; the bare "Removed Group wrapper"
		// entry is suppressed because the matched-pair entries already cover
		// the same physical edit.
		$descriptions = array_map( static fn( $c ): string => (string) ( $c['description'] ?? '' ), $result['structural_changes'] );
		foreach ( $descriptions as $description ) {
			$this->assertStringNotContainsString( 'Group wrapper', $description, 'Group wrapper entry must be suppressed when matched pairs cover the move.' );
		}
	}

	/**
	 * Reorder pairing must group by normalized block name, not by humanized
	 * label. Two distinct namespaces (e.g. `vendor-a/header` and
	 * `vendor-b/header`) both produce the label `Header`; pairing on label
	 * would falsely emit a single `Reordered Header` entry instead of one
	 * add + one remove.
	 *
	 * Fixture: core has a single `vendor-a/header`. Post has a single
	 * `vendor-b/header`. Different blocks under the same humanized label.
	 * Expected: one add + one remove, no reorder entry.
	 *
	 * @testdox Should pair reorder candidates by normalized block name, not by humanized label.
	 */
	public function test_summarize_reorder_pairs_by_normalized_name_not_humanized_label(): void {
		$email_id = 'change_summary_namespace_collision';
		$this->register_fixture_email( $email_id );

		$core_content = '<!-- wp:vendor-a/header --><div class="wp-block-vendor-a-header">Vendor A header.</div><!-- /wp:vendor-a/header -->';
		$post_content = '<!-- wp:vendor-b/header --><div class="wp-block-vendor-b-header">Vendor B header.</div><!-- /wp:vendor-b/header -->';

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$this->assertCount( 1, $result['added_blocks'], 'vendor-a/header is in core but not in post — should land in added_blocks.' );
		$this->assertCount( 1, $result['removed_blocks'], 'vendor-b/header is in post but not in core — should land in removed_blocks.' );

		// Each entry should expose the raw normalized name alongside the label.
		$this->assertSame( 'vendor-a/header', $result['added_blocks'][0]['name'] );
		$this->assertSame( 'Header', $result['added_blocks'][0]['label'] );
		$this->assertSame( 'vendor-b/header', $result['removed_blocks'][0]['name'] );
		$this->assertSame( 'Header', $result['removed_blocks'][0]['label'] );

		// No `reorder` structural change must be emitted — the two blocks
		// share a label but not a name.
		$kinds = array_map( static fn( $c ): string => (string) ( $c['kind'] ?? '' ), $result['structural_changes'] );
		$this->assertNotContains( 'reorder', $kinds, 'Different namespaces sharing a label must NOT collapse into a reorder pairing.' );
	}

	/**
	 * Wrapper suppression must not over-suppress: when the unmatched
	 * structural block has no matched pair pointing at it as a parent, the
	 * wrapper entry is the only place the change shows up and must be kept.
	 *
	 * Fixture: post wraps a Heading (different name from core's Paragraph) in
	 * a Group. LCS finds no matches. Group has no matched-pair child with
	 * parent=Group, so its "Removed Group wrapper" entry must remain.
	 *
	 * @testdox Should keep the wrapper structural entry when no matched pair points at it as a parent.
	 */
	public function test_summarize_keeps_wrapper_entry_when_no_matched_pair_covers_it(): void {
		$email_id = 'change_summary_wrapper_keep';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core paragraph.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:group -->\n<div class=\"wp-block-group\">"
			. "<!-- wp:heading -->\n<h2>Merchant heading.</h2>\n<!-- /wp:heading -->"
			. "</div>\n<!-- /wp:group -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$descriptions = array_map( static fn( $c ): string => (string) ( $c['description'] ?? '' ), $result['structural_changes'] );
		$this->assertContains(
			'Removed Group wrapper',
			$descriptions,
			'Group wrapper entry must NOT be suppressed when no matched pair points at Group as a parent.'
		);
	}

	/**
	 * LCS resists the cascade noise that a positional walk would produce on
	 * uniform paragraph runs: insert a single paragraph at index 1 in a long
	 * run; only one block should be reported, not the whole tail.
	 *
	 * @testdox Should align via LCS so a single inserted block in a uniform run does not cascade into the entire tail.
	 */
	public function test_summarize_lcs_alignment_resists_paragraph_run_cascade(): void {
		$email_id = 'change_summary_cascade';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Three.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Four.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Five.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Six.</p>\n<!-- /wp:paragraph -->";

		// Merchant inserted a new heading after the first paragraph.
		$post_content = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:heading -->\n<h2>Inserted heading</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Three.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Four.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Five.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Six.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		// Yours → core convention: the inserted heading is in the post but not
		// in core, so applying would REMOVE it.
		$this->assertCount( 1, $result['removed_blocks'], 'Only the inserted heading should be reported.' );
		$this->assertSame( 'Heading', $result['removed_blocks'][0]['label'] );
		$this->assertIsArray( $result['removed_blocks'][0]['path'] );
		$this->assertEmpty( $result['added_blocks'] );
		$this->assertEmpty( $result['copy_changes'], 'No spurious copy_changes should cascade through indices 2..6.' );
	}

	/**
	 * Similarity-scored LCS picks the text-similar pairing when names tie.
	 *
	 * Without similarity scoring, name-only LCS over a uniform paragraph run
	 * can pair the merchant's edited paragraph with an unrelated one in the
	 * post, attributing the wrong "before" / "after" to the copy_change. With
	 * the bonus, the LCS prefers the pairing where matched pairs share the
	 * most words.
	 *
	 * @testdox Should prefer the text-similar pairing in uniform block runs so copy_changes carry the right before/after.
	 */
	public function test_summarize_prefers_text_similar_pairing_in_uniform_block_runs(): void {
		$email_id = 'change_summary_similarity';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>You have received a new order from a customer.</p>\n<!-- /wp:paragraph -->";

		// Merchant kept core's paragraph (with a small "Nice." prefix) and
		// added two unrelated paragraphs after it. Without similarity scoring,
		// LCS could pair the matched core Paragraph with any of the three
		// post Paragraphs by name alone — typically the last one.
		$post_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Nice. You have received a new order from a customer.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Random promotional text.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Some other unrelated note.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$this->assertCount( 1, $result['copy_changes'], 'Exactly one paragraph should be matched and flagged as a copy_change.' );
		// The matched paragraph must be the high-similarity pairing — the
		// merchant's "Nice. You have received..." against core's "You have
		// received...". Bare position-based LCS would have paired core's
		// paragraph with the third post paragraph and put "Some other
		// unrelated note." in `before`.
		$this->assertSame( 'Nice. You have received a new order from a customer.', $result['copy_changes'][0]['before'] );
		$this->assertSame( 'You have received a new order from a customer.', $result['copy_changes'][0]['after'] );
		// The two truly unrelated paragraphs should be the unmatched ones.
		$this->assertCount( 2, $result['removed_blocks'] );
	}

	/**
	 * Summary-inversion guard: a heavily one-sided expansion (5+ added, 0
	 * removed, 0 copy, ≥1.5x core size) trips the guard and falls back.
	 *
	 * @testdox Should fall back to the release-notes summary when the inversion guard trips on a heavily one-sided expansion.
	 */
	public function test_summarize_falls_back_on_summary_inversion(): void {
		$email_id = 'change_summary_inversion';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello.</p>\n<!-- /wp:paragraph -->";

		// Merchant added 6 unrelated blocks; nothing on the core side was edited or removed.
		$post_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:gallery --><figure></figure><!-- /wp:gallery -->\n\n"
			. "<!-- wp:list --><ul></ul><!-- /wp:list -->\n\n"
			. '<!-- wp:separator --><hr/><!-- /wp:separator -->';

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertTrue( $result['is_fallback'] );
		$this->assertSame(
			array( __( 'Template updated — see release notes.', 'woocommerce' ) ),
			$result['summary_lines']
		);
		$this->assertEmpty( $result['added_blocks'] );
		$this->assertEmpty( $result['copy_changes'] );
	}

	/**
	 * Generic fallback: post outside the registry returns the release-notes
	 * line and empty structured arrays.
	 *
	 * @testdox Should fall back to the release-notes summary for posts outside the sync registry.
	 */
	public function test_summarize_falls_back_when_post_is_not_in_registry(): void {
		// No fixture email registered for this email_id, so the registry gate fails.
		$post_id = $this->create_woo_email_post(
			'change_summary_unregistered',
			"<!-- wp:paragraph -->\n<p>Anything.</p>\n<!-- /wp:paragraph -->"
		);

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertTrue( $result['is_fallback'] );
		$this->assertSame(
			array( __( 'Template updated — see release notes.', 'woocommerce' ) ),
			$result['summary_lines']
		);
		$this->assertEmpty( $result['added_blocks'] );
		$this->assertEmpty( $result['removed_blocks'] );
		$this->assertEmpty( $result['copy_changes'] );
		$this->assertEmpty( $result['structural_changes'] );
	}

	/**
	 * Identical post and core content returns a successful zero-result: empty
	 * structured arrays, empty summary_lines, is_fallback: false.
	 *
	 * `is_fallback` is reserved for "diff could not be produced." A no-op is a
	 * successful result — consumers detect it by the absence of deltas and
	 * render any "you're up to date" copy themselves.
	 *
	 * @testdox Should return an empty, non-fallback payload when post content equals the canonical core render.
	 */
	public function test_summarize_returns_empty_payload_when_post_equals_core(): void {
		$email_id = 'change_summary_identical';
		$this->register_fixture_email( $email_id );

		$content = "<!-- wp:paragraph -->\n<p>Untouched.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $content );
		$post_id = $this->create_woo_email_post( $email_id, $content );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );
		$this->assertSame( array(), $result['summary_lines'] );
		$this->assertSame( array(), $result['added_blocks'] );
		$this->assertSame( array(), $result['removed_blocks'] );
		$this->assertSame( array(), $result['copy_changes'] );
		$this->assertSame( array(), $result['structural_changes'] );
	}

	/**
	 * The in-sync zero-result is cached like every other path. Second call for
	 * the same content reports cache_hit: true.
	 *
	 * @testdox Should cache the in-sync zero-result and report cache_hit on the second call.
	 */
	public function test_summarize_caches_in_sync_zero_result(): void {
		$email_id = 'change_summary_in_sync_cache';
		$this->register_fixture_email( $email_id );

		$content = "<!-- wp:paragraph -->\n<p>Same on both sides.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $content );
		$post_id = $this->create_woo_email_post( $email_id, $content );

		$first = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertFalse( $first['cache_hit'] );
		$this->assertFalse( $first['is_fallback'] );

		$second = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertTrue( $second['cache_hit'] );
		$this->assertFalse( $second['is_fallback'] );
		$this->assertSame( array(), $second['summary_lines'] );
	}

	/**
	 * `source_hash_to` is the sha1 of the canonical core content for the
	 * email type. It mirrors the post's `_wc_email_template_source_hash`
	 * meta and is consumed by the RSM-145 Tracks instrumentation to identify
	 * which canonical revision a merchant is comparing against.
	 *
	 * @testdox Should expose source_hash_to as a non-empty sha1 hex string on the success-path payload.
	 */
	public function test_summarize_includes_source_hash_to_for_customized_post(): void {
		$email_id = 'change_summary_source_hash';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Original line.</p>\n<!-- /wp:paragraph -->";

		// Merchant edited the paragraph copy.
		$post_content = "<!-- wp:heading -->\n<h2>Welcome</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Edited line.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$summary = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertArrayHasKey( 'source_hash_to', $summary );
		$this->assertIsString( $summary['source_hash_to'] );
		$this->assertNotEmpty( $summary['source_hash_to'] );
		$this->assertSame( 40, strlen( $summary['source_hash_to'] ) );
		$this->assertTrue(
			ctype_xdigit( $summary['source_hash_to'] ),
			'source_hash_to must be a hex-only sha1 (40 hex chars).'
		);
	}

	/**
	 * Cache: first call computes and stores; second call with same inputs hits
	 * the cache; mutating the post invalidates by content hash.
	 *
	 * @testdox Should cache by content hash and invalidate when the post content changes.
	 */
	public function test_summarize_caches_and_invalidates_on_content_change(): void {
		$email_id = 'change_summary_cache';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Original copy.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Edited copy.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		$first = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertFalse( $first['cache_hit'], 'First call should be a cache miss.' );

		$second = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertTrue( $second['cache_hit'], 'Second call with identical inputs should hit the cache.' );
		$this->assertSame( $first['copy_changes'], $second['copy_changes'] );

		// Mutate post content; new content hash → fresh cache key.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => "<!-- wp:paragraph -->\n<p>Different again.</p>\n<!-- /wp:paragraph -->",
			)
		);
		$this->posts_manager->clear_caches();

		$third = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertFalse( $third['cache_hit'], 'After content mutation, the new key should miss the cache.' );
	}

	/**
	 * @testdox Three-way diff: a yours-only edit (yours changed, core unchanged) yields no diff entry — it is a merchant edit, not a conflict.
	 */
	public function test_three_way_yours_only_edit_yields_no_entry(): void {
		$single_paragraph = static fn( string $text ): array => array(
			array(
				'name'       => 'core/paragraph',
				'inner_text' => $text,
			),
		);

		$base = self::records( $single_paragraph( 'Hi.' ) );
		$core = self::records( $single_paragraph( 'Hi.' ) );
		$post = self::records( $single_paragraph( 'Hi friend.' ) );

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertSame( array(), $diff['copy_changes'], 'No copy_change should fire when only yours moved.' );
		$this->assertSame( array(), $diff['added_blocks'] );
		$this->assertSame( array(), $diff['removed_blocks'] );
	}

	/**
	 * @testdox Three-way diff: a core-only edit (core changed, yours unchanged) classifies as a copy_change.
	 */
	public function test_three_way_core_only_edit_classifies_as_copy_change(): void {
		$single_paragraph = static fn( string $text ): array => array(
			array(
				'name'       => 'core/paragraph',
				'inner_text' => $text,
			),
		);

		$base = self::records( $single_paragraph( 'Hi.' ) );
		$core = self::records( $single_paragraph( 'Hello.' ) );
		$post = self::records( $single_paragraph( 'Hi.' ) );

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertCount( 1, $diff['copy_changes'] );
		$this->assertSame( 'Hi.', $diff['copy_changes'][0]['before'] );
		$this->assertSame( 'Hello.', $diff['copy_changes'][0]['after'] );
	}

	/**
	 * @testdox Three-way diff: when both yours and core edited, classifies as a conflict copy_change.
	 */
	public function test_three_way_both_edited_classifies_as_conflict(): void {
		$single_paragraph = static fn( string $text ): array => array(
			array(
				'name'       => 'core/paragraph',
				'inner_text' => $text,
			),
		);

		$base = self::records( $single_paragraph( 'Hi.' ) );
		$core = self::records( $single_paragraph( 'Hello.' ) );
		$post = self::records( $single_paragraph( 'Hey there.' ) );

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertCount( 1, $diff['copy_changes'] );
		$this->assertSame( 'Hey there.', $diff['copy_changes'][0]['before'] );
		$this->assertSame( 'Hello.', $diff['copy_changes'][0]['after'] );
	}

	/**
	 * @testdox Three-way diff: a yours-only added block classifies as a removed_block (preserved on apply).
	 */
	public function test_three_way_yours_only_addition_classifies_as_removed_block(): void {
		$heading_only = array(
			array(
				'name'       => 'core/heading',
				'inner_text' => 'H',
			),
		);

		$base = self::records( $heading_only );
		$core = self::records( $heading_only );
		$post = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'Merchant note.',
				),
			)
		);

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertCount( 1, $diff['removed_blocks'] );
		$this->assertSame( 'core/paragraph', $diff['removed_blocks'][0]['name'] );
	}

	/**
	 * @testdox Three-way diff: a core-only added block classifies as an added_block (auto-applied).
	 */
	public function test_three_way_core_only_addition_classifies_as_added_block(): void {
		$heading_only = array(
			array(
				'name'       => 'core/heading',
				'inner_text' => 'H',
			),
		);

		$base = self::records( $heading_only );
		$core = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'Core PS.',
				),
			)
		);
		$post = self::records( $heading_only );

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertCount( 1, $diff['added_blocks'] );
		$this->assertSame( 'core/paragraph', $diff['added_blocks'][0]['name'] );
	}

	/**
	 * @testdox Bug 04 regression: parallel additions on yours and core classify as separate add+remove, not as a single copy_change.
	 */
	public function test_three_way_parallel_additions_classify_separately(): void {
		$base = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
			)
		);
		$core = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'PS from core.',
				),
			)
		);
		$post = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'Reach out anytime.',
				),
			)
		);

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		// Bug 04 fingerprint: the 2-way LCS would have paired these as one copy_change. The 3-way
		// diff identifies them against base as two independent additions on different sides.
		$this->assertCount( 0, $diff['copy_changes'], 'Parallel additions must not collapse into a single copy_change.' );
		$this->assertCount( 1, $diff['added_blocks'] );
		$this->assertCount( 1, $diff['removed_blocks'] );
		$this->assertSame( 'core/paragraph', $diff['added_blocks'][0]['name'] );
		$this->assertSame( 'core/paragraph', $diff['removed_blocks'][0]['name'] );
	}

	/**
	 * @testdox summarize() takes the three-way path when last_core_render meta is set and yours-only edits don't surface as copy_changes.
	 */
	public function test_summarize_uses_three_way_when_base_meta_present(): void {
		$email_id = 'cs_three_way_yours_only_edit';
		$this->register_fixture_email( $email_id );

		$base_and_core = "<!-- wp:heading -->\n<h2>H</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hi.</p>\n<!-- /wp:paragraph -->";
		$post_content  = "<!-- wp:heading -->\n<h2>H</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hi friend.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $base_and_core );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $base_and_core );

		$payload = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $payload['is_fallback'] );
		$this->assertSame(
			array(),
			$payload['copy_changes'],
			'A yours-only edit (yours diverges from base, core unchanged) should not produce a copy_change in 3-way mode.'
		);
		$this->assertSame( array(), $payload['added_blocks'] );
		$this->assertSame( array(), $payload['removed_blocks'] );
	}

	/**
	 * @testdox summarize() cache invalidates when last_core_render meta changes.
	 */
	public function test_summarize_cache_busts_when_base_render_changes(): void {
		$email_id = 'cs_three_way_cache_bust';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Core.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Yours.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY,
			"<!-- wp:paragraph -->\n<p>Base A.</p>\n<!-- /wp:paragraph -->"
		);
		$first = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertFalse( $first['cache_hit'], 'First call must compute fresh.' );

		// Same call again, base unchanged → cache hit.
		$second = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertTrue( $second['cache_hit'], 'Second call with unchanged base should hit cache.' );

		// Mutate the base — must miss the cache.
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY,
			"<!-- wp:paragraph -->\n<p>Base B.</p>\n<!-- /wp:paragraph -->"
		);
		$third = WCEmailTemplateChangeSummary::summarize( $post_id );
		$this->assertFalse( $third['cache_hit'], 'Changing base_render must bust the cache.' );
	}

	/**
	 * @testdox summarize() falls back to the two-way path when last_core_render meta is missing.
	 */
	public function test_summarize_falls_back_to_two_way_when_base_meta_missing(): void {
		$email_id = 'cs_three_way_fallback';
		$this->register_fixture_email( $email_id );

		$core_content = "<!-- wp:paragraph -->\n<p>Original.</p>\n<!-- /wp:paragraph -->";
		$post_content = "<!-- wp:paragraph -->\n<p>Edited.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		// No last_core_render meta on this post — expect the existing 2-way classification.
		$payload = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $payload['is_fallback'] );
		$this->assertCount(
			1,
			$payload['copy_changes'],
			'Two-way fallback must still surface yours-vs-core text divergence as a copy_change.'
		);
		$this->assertSame( 'Edited.', $payload['copy_changes'][0]['before'] );
		$this->assertSame( 'Original.', $payload['copy_changes'][0]['after'] );
	}

	/**
	 * @testdox Bug 03 regression: three-way diff does not fall back to the release-notes copy on a heavily-customized post when last_core_render meta is present.
	 *
	 * The inversion-guard heuristic (>= 5 unmatched && 0 copy && post >= 1.5x core) fires under
	 * the 2-way fallback for the same post, hiding actionable diffs behind "see release notes".
	 * With base meta set, the 3-way path is deterministic — the guard isn't reached and the
	 * merchant gets per-block detail.
	 */
	public function test_three_way_does_not_fall_back_on_heavily_customized_post(): void {
		$email_id = 'cs_three_way_heavily_customized';
		$this->register_fixture_email( $email_id );

		$base_and_core = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello.</p>\n<!-- /wp:paragraph -->";

		// Merchant added 6 unrelated blocks (above the inversion-guard threshold of 5);
		// nothing on the core side was edited or removed.
		$post_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Hello.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:image --><figure></figure><!-- /wp:image -->\n\n"
			. "<!-- wp:gallery --><figure></figure><!-- /wp:gallery -->\n\n"
			. "<!-- wp:list --><ul></ul><!-- /wp:list -->\n\n"
			. '<!-- wp:separator --><hr/><!-- /wp:separator -->';

		$this->use_canonical_content( $email_id, $base_and_core );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $base_and_core );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse(
			$result['is_fallback'],
			'Three-way must not fall back to the release-notes line on a heavily-customized post.'
		);
		$this->assertGreaterThanOrEqual(
			6,
			count( $result['removed_blocks'] ),
			'All six yours-only additions should appear as removed_blocks (preserved on apply).'
		);
	}

	/**
	 * @testdox Bug 04 regression: parallel additions on yours and core via summarize() classify as separate add+remove, not as a single copy_change.
	 */
	public function test_three_way_parallel_additions_via_summarize(): void {
		$email_id = 'cs_three_way_parallel_summarize';
		$this->register_fixture_email( $email_id );

		$base_render = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->";

		$core_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>PS from core.</p>\n<!-- /wp:paragraph -->";

		$post_content = "<!-- wp:heading -->\n<h2>Hi</h2>\n<!-- /wp:heading -->\n\n"
			. "<!-- wp:paragraph -->\n<p>Reach out anytime.</p>\n<!-- /wp:paragraph -->";

		$this->use_canonical_content( $email_id, $core_content );
		$post_id = $this->create_woo_email_post( $email_id, $post_content );

		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $base_render );

		$result = WCEmailTemplateChangeSummary::summarize( $post_id );

		$this->assertFalse( $result['is_fallback'] );

		$copy_paragraphs = array_values(
			array_filter(
				$result['copy_changes'],
				static fn ( array $cc ): bool => 'Paragraph' === ( $cc['block'] ?? '' )
			)
		);
		$this->assertSame(
			array(),
			$copy_paragraphs,
			'Parallel additions must not collapse into a Paragraph copy_change in 3-way mode.'
		);
		$this->assertCount( 1, $result['added_blocks'], 'Core PS should appear in added_blocks.' );
		$this->assertCount( 1, $result['removed_blocks'], 'Yours\' note should appear in removed_blocks.' );
	}

	/**
	 * @testdox Three-way diff: occurrence ordinal counts every matched pair, not just emitted conflicts (CodeRabbit feedback on PR 64716).
	 *
	 * Earlier same-name blocks that aren't emitted (yours-only edit, both unchanged) must still
	 * advance the occurrence counter so a later conflict's ordinal reflects the block's true
	 * position in the document. Mirrors the 2-way `diff_records()` placement of the counter.
	 */
	public function test_three_way_occurrence_counts_every_matched_pair(): void {
		// Three Paragraph blocks. First yours-only edit (no entry). Second core-only edit
		// (copy_change). Third both unchanged (no entry). The conflict's "occurrence" should
		// be 2, NOT 1 — the second-of-three Paragraph in the document.
		$base = self::records(
			array(
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P1.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P2.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P3.',
				),
			)
		);
		$core = self::records(
			array(
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P1.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P2 changed by core.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P3.',
				),
			)
		);
		$post = self::records(
			array(
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P1 edited by yours.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P2.',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P3.',
				),
			)
		);

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$this->assertCount( 1, $diff['copy_changes'] );
		$this->assertSame(
			2,
			$diff['copy_changes'][0]['occurrence'],
			'The conflict on the second Paragraph should be labeled occurrence 2 of 3, not 1 of 3.'
		);
		$this->assertSame( 3, $diff['copy_changes'][0]['total'] );
	}

	/**
	 * @testdox Three-way diff: structural wrappers route to structural_changes, not added_blocks/removed_blocks (CodeRabbit feedback on PR 64716).
	 *
	 * The selective applier skips structural blocks (`core/group`, `core/columns`, etc.) at
	 * merge time, so surfacing them in `added_blocks` would advertise an "Added Group block"
	 * the apply will never apply. Mirrors 2-way `diff_records()` behavior.
	 */
	public function test_three_way_routes_structural_wrappers_to_structural_changes(): void {
		$base = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
			)
		);
		// Core adds a group wrapper; yours adds a columns wrapper.
		$core = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/group',
					'inner_text' => '',
				),
			)
		);
		$post = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/columns',
					'inner_text' => '',
				),
			)
		);

		$diff = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );

		$structural_names = array_map( static fn( array $e ): string => (string) ( $e['kind'] ?? '' ), $diff['structural_changes'] );
		$this->assertContains( 'nest', $structural_names, 'Structural wrappers must route to structural_changes with kind "nest".' );
		$this->assertCount(
			2,
			$diff['structural_changes'],
			'Both yours-only and core-only structural wrappers should produce structural_changes entries.'
		);
		$this->assertSame( array(), $diff['added_blocks'], 'core/group must not appear in added_blocks.' );
		$this->assertSame( array(), $diff['removed_blocks'], 'core/columns must not appear in removed_blocks.' );
	}

	/**
	 * @testdox Three-way diff: no reorder structural change is emitted for blocks reordered relative to base.
	 *
	 * Pins the docblock claim on `diff_records_three_way()` that the 2-way reorder pass
	 * is structurally unreachable under three-way attribution. The fixture uses three
	 * differently-named blocks so the 2-way LCS (which pairs by `name`) can leave
	 * same-named entries unmatched. Core keeps `[Heading, Paragraph, Image]`; post
	 * moves Image to the front: `[Image, Heading, Paragraph]`. Two-way LCS picks the
	 * length-2 alignment `Heading+Paragraph`, leaves `Image` unmatched on both sides,
	 * and the reorder pass collapses them into a `Reordered Image` entry. Three-way
	 * attributes each block via base indices instead — the unmatched post-side `Image`
	 * becomes a `removed_blocks` entry and the unmatched core-side `Image` becomes a
	 * `merchant_removed` structural entry, neither of which is a `reorder`.
	 */
	public function test_three_way_does_not_emit_reorder_for_blocks_reordered_relative_to_base(): void {
		$base = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P.',
				),
				array(
					'name'       => 'core/image',
					'inner_text' => '',
				),
			)
		);
		$core = self::records(
			array(
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P.',
				),
				array(
					'name'       => 'core/image',
					'inner_text' => '',
				),
			)
		);
		$post = self::records(
			array(
				array(
					'name'       => 'core/image',
					'inner_text' => '',
				),
				array(
					'name'       => 'core/heading',
					'inner_text' => 'H',
				),
				array(
					'name'       => 'core/paragraph',
					'inner_text' => 'P.',
				),
			)
		);

		// Sanity check: the same fixture under the 2-way path emits a `reorder` entry.
		// If this assertion ever stops holding, the fixture has stopped exercising the
		// reorder pass and the three-way assertion below has become a tautology.
		// `diff_records()` is private; reflection here keeps the production API tight.
		$two_way_method = new \ReflectionMethod( WCEmailTemplateChangeSummary::class, 'diff_records' );
		$two_way_method->setAccessible( true );
		$two_way       = $two_way_method->invoke( null, $core, $post );
		$two_way_kinds = array_map( static fn( array $e ): string => (string) ( $e['kind'] ?? '' ), $two_way['structural_changes'] );
		$this->assertContains(
			'reorder',
			$two_way_kinds,
			'Fixture must trigger the 2-way reorder pass; otherwise the three-way assertion is meaningless.'
		);

		$three_way       = WCEmailTemplateChangeSummary::diff_records_three_way( $core, $base, $post );
		$three_way_kinds = array_map( static fn( array $e ): string => (string) ( $e['kind'] ?? '' ), $three_way['structural_changes'] );
		$this->assertNotContains(
			'reorder',
			$three_way_kinds,
			'Three-way must not emit a reorder structural entry — base-anchored matching makes it unreachable.'
		);
	}

	/**
	 * Build a list of flatten_blocks-shaped records from a simple list of name + inner_text pairs.
	 * Each record gets a top-level path (`[$idx]`) and a null parent_name.
	 *
	 * @param array<int, array{name:string, inner_text:string}> $simple Simple record specs.
	 * @return array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}>
	 */
	private static function records( array $simple ): array {
		$out = array();
		foreach ( $simple as $i => $r ) {
			$out[] = array(
				'path'        => array( $i ),
				'parent_name' => null,
				'name'        => $r['name'],
				'inner_text'  => $r['inner_text'],
			);
		}
		return $out;
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
		$stub->method( 'get_title' )->willReturn( 'Fixture email for change-summary tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover change-summary scenarios.' );
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

		foreach ( $this->injected_email_keys as $class_key ) {
			unset( $current[ $class_key ] );
		}

		$property->setValue( $emails_container, $current );
		$this->injected_email_keys = array();
	}
}

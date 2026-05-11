<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateChangeSummary;
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

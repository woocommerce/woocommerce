<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Produces a localized summary of differences between a merchant's `woo_email`
 * post content and the current canonical core render of the same email.
 *
 * Two diff modes, selected per-call by post-meta presence:
 *
 * - **Three-way (since 10.9.0)** — when the post has
 *   {@see WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY} meta
 *   (every post touched by a sync-eligible writer: generator, auto-applier,
 *   selective applier, reset endpoint, RSM-149 backfill), the summary runs
 *   two LCS passes (yours-vs-base, core-vs-base) and classifies each block
 *   by what changed relative to base. The diff is deterministic on any
 *   post; the inversion guard does not fire on this path.
 * - **Two-way (legacy fallback)** — when the meta is absent (legacy posts
 *   not yet touched since the meta was introduced), the summary falls back
 *   to the original `lcs_matches(core, post)` algorithm. The summary-
 *   inversion guard (>= 5 unmatched && 0 copy && post >= 1.5x core) is
 *   active in this path only — it bails to "Template updated — see release
 *   notes." when it can't reliably attribute changes.
 *
 * Both paths normalize known namespace aliases (e.g. `woo/email-content` →
 * `woocommerce/email-content`) and produce the same payload shape (added /
 * removed / copy / structural) so consumers don't need a mode switch.
 *
 * Result is cached in a transient keyed on the post ID, the post + core +
 * base content hashes, and the active locale; any merchant edit, core bump,
 * or base shift invalidates automatically.
 *
 * Hash input parity with {@see WCTransactionalEmailPostsGenerator::compute_canonical_post_content()}
 * is guaranteed by construction — both sides route through the same
 * canonical render, identical to the divergence detector.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 10.9.0
 */
class WCEmailTemplateChangeSummary {
	/**
	 * Transient TTL.
	 *
	 * Long-lived because the cache key is content-hash-bound; production
	 * invalidation is automatic on any post or core change.
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Maximum length of each side of a copy change, in characters.
	 */
	private const COPY_TRUNCATE_CHARS = 120;

	/**
	 * Summary-inversion guard: minimum count of post-side unmatched blocks
	 * (with no copy changes and a heavily larger post) before we refuse to
	 * confidently attribute the diff to "core changed."
	 */
	private const INVERSION_GUARD_THRESHOLD = 5;

	/**
	 * Summary-inversion guard: minimum post-to-core record-count ratio.
	 */
	private const INVERSION_GUARD_RATIO = 1.5;

	/**
	 * Block-name aliases. Used to fold known namespace renames into a single
	 * identity so the diff doesn't surface them as add+remove pairs.
	 *
	 * Keep this map intentionally small — extend only when a real alias is
	 * observed in the wild.
	 *
	 * @var array<string,string>
	 */
	private const BLOCK_NAME_ALIASES = array(
		'woo/email-content' => 'woocommerce/email-content',
	);

	/**
	 * Block names that act purely as structural wrappers in email templates.
	 * When one of these appears unmatched on a single side it is reported as a
	 * `nest` structural change rather than as an `added_blocks`/`removed_blocks`
	 * entry.
	 *
	 * @var array<string,bool>
	 */
	private const STRUCTURAL_BLOCK_NAMES = array(
		'core/group'   => true,
		'core/columns' => true,
		'core/column'  => true,
		'core/row'     => true,
	);

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static $logger = null;

	/**
	 * Produce a structured + localized summary of differences between the
	 * merchant's `woo_email` post and the current canonical core render.
	 *
	 * Returned payload shape (documented for callers; typed as
	 * `array<string, mixed>` so internal helpers can return through it
	 * without expanding every union into the signature):
	 *
	 * All deltas are framed as "what would happen if the merchant applied the
	 * update," i.e. the "yours" → "core" direction:
	 *
	 * - `version_from`        — `string`     — `_wc_email_template_version` meta on the post (may be empty).
	 * - `version_to`          — `string`     — registry-side current version.
	 * - `source_hash_to`      — `string`     — sha1 of the canonical core content for this email type. Mirrors the post's `_wc_email_template_source_hash` meta. Empty string in fallback / no-config paths where the core content can't be computed.
	 * - `added_blocks`        — `array<int, array{name:string, label:string, path:array<int|string>}>` — blocks that would be added to the post by applying (in core, not in post). `name` is the post-alias-normalized block name (e.g. `core/heading`); `label` is its humanized form for display; `path` is the core-side index path.
	 * - `removed_blocks`      — `array<int, array{name:string, label:string, path:array<int|string>}>` — blocks that would be removed from the post by applying (in post, not in core). Same field semantics as `added_blocks`; `path` is the post-side index path.
	 * - `copy_changes`        — `array<int, array{block:string, before:string, after:string, occurrence:int, total:int, path:array<int|string>, auto_resolvable?:bool}>`.
	 *                           `before` is the merchant's current text; `after` is the canonical core text. `path` is the post-side index path.
	 *                           `auto_resolvable` is emitted only on the three-way path: `true` when only core changed since base (safe to auto-apply), `false` when both sides changed (true conflict). Absent on two-way fallback payloads.
	 * - `structural_changes`  — `array<int, array{kind:string, description:string, path?:array<int|string>}>` — `path` is omitted for `kind: 'reorder'` entries.
	 * - `summary_lines`       — `string[]`   — pre-localized one-liners ready to render.
	 * - `is_fallback`         — `bool`       — true when the diff could not be produced.
	 * - `cache_hit`           — `bool`       — diagnostic.
	 *
	 * @param int $post_id The `woo_email` post ID.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 10.9.0
	 */
	public static function summarize( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return self::fallback_payload( '', '' );
		}

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$email_id      = $posts_manager->get_email_type_from_post_id( $post_id );
		if ( ! is_string( $email_id ) || '' === $email_id ) {
			return self::fallback_payload( '', '' );
		}

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id );
		if ( null === $sync_config ) {
			return self::fallback_payload( '', '' );
		}

		$emails = $posts_manager->get_emails_by_id();
		$email  = $emails[ $email_id ] ?? null;
		if ( ! $email instanceof \WC_Email ) {
			return self::fallback_payload( '', (string) $sync_config['version'] );
		}

		$post_content = (string) $post->post_content;

		try {
			$core_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		} catch ( \Throwable $e ) {
			self::get_logger()->error(
				sprintf(
					'Email template change summary failed to compute canonical content for email "%s": %s',
					$email_id,
					$e->getMessage()
				),
				array(
					'email_id' => $email_id,
					'post_id'  => $post_id,
					'context'  => 'email_template_change_summary',
				)
			);
			return self::fallback_payload(
				(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
				(string) $sync_config['version']
			);
		}

		$version_from = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true );
		$version_to   = (string) $sync_config['version'];

		$post_hash = sha1( $post_content );
		$core_hash = sha1( $core_content );

		$base_render = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, true );
		$base_hash   = '' !== $base_render ? sha1( $base_render ) : '';

		$cache_key = self::cache_key( $post_id, $post_hash, $core_hash, $base_hash, self::current_locale() );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			$cached['cache_hit'] = true;
			return $cached;
		}

		// In-sync zero-result: post and core hash to the same content. Successful
		// diff with no deltas. `is_fallback` stays false (the docblock contract:
		// fallback = "diff could not be produced," not "diff produced no
		// changes"). Empty `summary_lines` lets consumers detect the no-op state
		// by emptiness alone — they construct any "you're up to date" copy
		// themselves.
		if ( $post_hash === $core_hash ) {
			$payload                   = self::empty_payload();
			$payload['version_from']   = $version_from;
			$payload['version_to']     = $version_to;
			$payload['source_hash_to'] = $core_hash;
			self::write_cache( $cache_key, $payload );
			return $payload;
		}

		$post_records = self::flatten_blocks( parse_blocks( $post_content ) );
		$core_records = self::flatten_blocks( parse_blocks( $core_content ) );

		if ( empty( $post_records ) || empty( $core_records ) ) {
			return self::fallback_payload( $version_from, $version_to );
		}

		// Branch on base meta availability. When the post has been touched by a
		// sync-eligible writer (generator, auto-applier, selective applier, reset,
		// backfill), it has `last_core_render` and we run the three-way diff: a
		// strict per-block (yours-vs-base, core-vs-base) comparison. The
		// inversion-guard heuristic is not needed in this branch — three-way is
		// deterministic on any post. Posts without the meta fall through to the
		// legacy two-way path, which keeps the inversion guard for safety.
		if ( '' !== $base_render ) {
			$base_records = self::flatten_blocks( parse_blocks( $base_render ) );
			$structured   = self::diff_records_three_way( $core_records, $base_records, $post_records );
		} else {
			$structured = self::diff_records( $core_records, $post_records );

			// Summary-inversion guard: a heavily one-sided expansion on the post
			// side looks like merchant work attributed to core. Without a stored
			// old-core render to disambiguate, fall back to the release-notes line.
			// Under the "yours → core" convention, post-side unmatched blocks land
			// in `removed_blocks` (would be removed by applying), so that's the
			// signal we count here.
			$post_total = count( $post_records );
			$core_total = count( $core_records );
			if (
				0 === count( $structured['added_blocks'] )
				&& 0 === count( $structured['copy_changes'] )
				&& count( $structured['removed_blocks'] ) >= self::INVERSION_GUARD_THRESHOLD
				&& $post_total >= ( self::INVERSION_GUARD_RATIO * $core_total )
			) {
				$payload = self::fallback_payload( $version_from, $version_to );
				self::write_cache( $cache_key, $payload );
				return $payload;
			}
		}

		$summary_lines = self::to_summary_lines( $structured );

		$payload = array(
			'version_from'       => $version_from,
			'version_to'         => $version_to,
			'source_hash_to'     => $core_hash,
			'added_blocks'       => $structured['added_blocks'],
			'removed_blocks'     => $structured['removed_blocks'],
			'copy_changes'       => $structured['copy_changes'],
			'structural_changes' => $structured['structural_changes'],
			'summary_lines'      => $summary_lines,
			'is_fallback'        => false,
			'cache_hit'          => false,
		);

		self::write_cache( $cache_key, $payload );

		return $payload;
	}

	/**
	 * Drop every cached change-summary transient. Test-only — production
	 * invalidation is automatic via the content-hash key.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	public static function reset_cache(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_wc_email_change_summary_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_wc_email_change_summary_' ) . '%'
			)
		);
	}

	/**
	 * Override the logger implementation. Intended for tests only.
	 *
	 * @internal
	 *
	 * @param Email_Editor_Logger_Interface|null $logger The logger implementation, or null to restore the default.
	 */
	public static function set_logger( ?Email_Editor_Logger_Interface $logger ): void {
		self::$logger = $logger;
	}

	/**
	 * Empty payload skeleton.
	 *
	 * @return array<string, mixed>
	 */
	private static function empty_payload(): array {
		return array(
			'version_from'       => '',
			'version_to'         => '',
			'source_hash_to'     => '',
			'added_blocks'       => array(),
			'removed_blocks'     => array(),
			'copy_changes'       => array(),
			'structural_changes' => array(),
			'summary_lines'      => array(),
			'is_fallback'        => false,
			'cache_hit'          => false,
		);
	}

	/**
	 * Build the standard fallback payload (release-notes line).
	 *
	 * @param string $version_from Stored version stamp on the post (may be empty).
	 * @param string $version_to   Registry-side current version.
	 *
	 * @return array<string, mixed>
	 */
	private static function fallback_payload( string $version_from, string $version_to ): array {
		$payload                  = self::empty_payload();
		$payload['version_from']  = $version_from;
		$payload['version_to']    = $version_to;
		$payload['summary_lines'] = array( __( 'Template updated — see release notes.', 'woocommerce' ) );
		$payload['is_fallback']   = true;
		return $payload;
	}

	/**
	 * Write a payload to the transient cache, pre-stamping `cache_hit` so any
	 * subsequent read is honest about its origin.
	 *
	 * @param string               $cache_key Transient key.
	 * @param array<string, mixed> $payload   The payload to cache.
	 */
	private static function write_cache( string $cache_key, array $payload ): void {
		$to_cache              = $payload;
		$to_cache['cache_hit'] = true;
		set_transient( $cache_key, $to_cache, self::CACHE_TTL );
	}

	/**
	 * Resolve the active locale for cache-keying. User locale wins so that
	 * different admins on the same site each see their own translation.
	 */
	private static function current_locale(): string {
		$user_locale = function_exists( 'get_user_locale' ) ? (string) get_user_locale() : '';
		return '' !== $user_locale ? $user_locale : (string) get_locale();
	}

	/**
	 * Compute the transient key.
	 *
	 * Hash composite is md5-wrapped to keep `option_name` (with the
	 * `_transient_` prefix) well under WP's 191-char limit.
	 *
	 * @param int    $post_id   The `woo_email` post ID.
	 * @param string $post_hash sha1 of the persisted post content.
	 * @param string $core_hash sha1 of the canonical core render.
	 * @param string $base_hash sha1 of `_wc_email_template_last_core_render` if set; empty string otherwise. Including this in the key invalidates the cache when base shifts.
	 * @param string $locale    Active locale.
	 */
	private static function cache_key( int $post_id, string $post_hash, string $core_hash, string $base_hash, string $locale ): string {
		return sprintf(
			'wc_email_change_summary_%d_%s',
			$post_id,
			md5( $post_hash . '|' . $core_hash . '|' . $base_hash . '|' . $locale )
		);
	}

	/**
	 * DFS-flatten a `parse_blocks()` result into an ordered sequence of node
	 * descriptors (DFS pre-order: parent emitted before its children).
	 * Structural wrapper blocks (`core/group`, `core/columns`, …) are included
	 * in the output — the diff classifier inspects them via
	 * {@see self::STRUCTURAL_BLOCK_NAMES}. Null-name entries (raw HTML wrappers
	 * between blocks) are skipped.
	 *
	 * Public so the RSM-143 selective-merge engine
	 * ({@see WCEmailTemplateSelectiveApplier}) can reuse this and
	 * {@see self::lcs_matches()} to align matched pairs without duplicating the
	 * algorithm. Internal namespace-bounded; not part of any external contract.
	 *
	 * @internal
	 *
	 * @param array<int|string, array<string, mixed>> $blocks      Output of `parse_blocks()`.
	 * @param array<int|string>                       $path        Current index path from root.
	 * @param string|null                             $parent_name Normalized parent block name, null at root.
	 *
	 * @return array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}>
	 */
	public static function flatten_blocks( array $blocks, array $path = array(), ?string $parent_name = null ): array {
		$records = array();
		foreach ( $blocks as $idx => $block ) {
			if ( ! is_array( $block ) || null === ( $block['blockName'] ?? null ) ) {
				continue;
			}
			$name         = self::normalize_block_name( (string) $block['blockName'] );
			$current_path = array_merge( $path, array( $idx ) );

			$records[] = array(
				'path'        => $current_path,
				'parent_name' => $parent_name,
				'name'        => $name,
				'inner_text'  => self::clean_inner_text( (string) ( $block['innerHTML'] ?? '' ) ),
			);

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$records = array_merge(
					$records,
					self::flatten_blocks( $block['innerBlocks'], $current_path, $name )
				);
			}
		}//end foreach
		return $records;
	}

	/**
	 * Apply the namespace-alias map.
	 *
	 * @param string $name Raw block name from `parse_blocks()`.
	 */
	private static function normalize_block_name( string $name ): string {
		return self::BLOCK_NAME_ALIASES[ $name ] ?? $name;
	}

	/**
	 * Strip tags and collapse whitespace. Used as the basis for copy-change
	 * comparison — semantic content only, no markup-shape noise.
	 *
	 * @param string $html Raw `innerHTML` string from a parsed block.
	 */
	private static function clean_inner_text( string $html ): string {
		$stripped  = wp_strip_all_tags( $html );
		$collapsed = preg_replace( '/\s+/', ' ', (string) $stripped );
		return trim( (string) $collapsed );
	}

	/**
	 * Diff two flattened record sequences.
	 *
	 * Each `added_blocks` / `removed_blocks` / `copy_changes` / `structural_changes`
	 * entry carries a `path` field — the index path through the parsed block
	 * tree on the side where the relevant block exists:
	 *
	 * - `added_blocks[].path`        — core-side path (where it would land if applied).
	 * - `removed_blocks[].path`      — post-side path (where it currently sits).
	 * - `copy_changes[].path`        — post-side path (the merchant's renderable surface).
	 * - `structural_changes[].path`  — post-side for matched-pair moves; the
	 *                                  unmatched side for wrapper additions/removals;
	 *                                  omitted for `kind: 'reorder'` entries (no single block).
	 *
	 * RSM-143's selective-merge UI uses `path` to map per-block "Keep yours /
	 * Use core" choices back to specific blocks during merge.
	 *
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $core_records Core side.
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $post_records Post side.
	 *
	 * @return array{added_blocks:array<int, array<string, mixed>>, removed_blocks:array<int, array<string, mixed>>, copy_changes:array<int, array<string, mixed>>, structural_changes:array<int, array<string, mixed>>}
	 */
	private static function diff_records( array $core_records, array $post_records ): array {
		$core_names = array_map( static fn( array $r ): string => $r['name'], $core_records );

		$matches = self::lcs_matches( $core_records, $post_records );

		$matched_core = array();
		$matched_post = array();
		foreach ( $matches as $pair ) {
			$matched_core[ $pair[0] ] = true;
			$matched_post[ $pair[1] ] = true;
		}

		$added_blocks       = array();
		$removed_blocks     = array();
		$copy_changes       = array();
		$structural_changes = array();

		// `added_blocks` / `removed_blocks` follow the "yours → core" convention:
		// `added_blocks` = blocks the merchant would gain by applying the update
		// (in core, not in post). `removed_blocks` = blocks the merchant would
		// lose by applying the update (in post, not in core). Same direction as
		// `before` (yours) / `after` (core) on copy_changes.
		//
		// Pass order: matched pairs first, then unmatched. The matched-pair
		// pass collects parent-name pairs whose mismatch will already produce
		// a "Moved %1$s into %2$s" entry, so the unmatched-pass can suppress
		// the redundant "Added/Removed %s wrapper" entry that would otherwise
		// describe the same physical edit twice.

		// Pass 1: classify matched pairs.
		$core_name_counts     = array_count_values( $core_names );
		$occurrence_index     = array();
		$matched_core_parents = array();
		$matched_post_parents = array();
		foreach ( $matches as $pair ) {
			$core   = $core_records[ $pair[0] ];
			$post_r = $post_records[ $pair[1] ];
			$name   = $core['name'];
			$label  = self::block_label( $name );

			$occurrence_index[ $name ] = ( $occurrence_index[ $name ] ?? 0 ) + 1;
			$total                     = (int) ( $core_name_counts[ $name ] ?? 1 );

			if ( $core['parent_name'] !== $post_r['parent_name'] ) {
				// Destination is core's parent (where the block would land after
				// applying the update), not post's (where it currently sits).
				$structural_changes[] = array(
					'kind'        => 'nest',
					'description' => sprintf(
						/* translators: 1: block name; 2: parent block name */
						__( 'Moved %1$s into %2$s', 'woocommerce' ),
						$label,
						null === $core['parent_name'] ? __( 'top level', 'woocommerce' ) : self::block_label( $core['parent_name'] )
					),
					'path'        => $post_r['path'],
				);
				if ( null !== $post_r['parent_name'] ) {
					$matched_post_parents[ $post_r['parent_name'] ] = true;
				}
				if ( null !== $core['parent_name'] ) {
					$matched_core_parents[ $core['parent_name'] ] = true;
				}
			}

			if ( $core['inner_text'] !== $post_r['inner_text'] ) {
				// `before` = merchant's current text (what they have now), `after` = canonical
				// core text (what they would get if they applied the update). Matches the
				// design's "yours" → "core" diff convention.
				$copy_changes[] = array(
					'block'      => $label,
					'before'     => self::truncate_text( $post_r['inner_text'] ),
					'after'      => self::truncate_text( $core['inner_text'] ),
					'occurrence' => $occurrence_index[ $name ],
					'total'      => $total,
					'path'       => $post_r['path'],
				);
			}
		}//end foreach

		// Pass 2: classify unmatched core. Skip wrapper entry if a matched
		// pair already names this wrapper as its core-side parent — that
		// matched pair's "Moved into" entry covers the same physical edit.
		foreach ( $core_records as $i => $rec ) {
			if ( isset( $matched_core[ $i ] ) ) {
				continue;
			}
			if ( isset( self::STRUCTURAL_BLOCK_NAMES[ $rec['name'] ] ) ) {
				if ( isset( $matched_core_parents[ $rec['name'] ] ) ) {
					continue;
				}
				$structural_changes[] = array(
					'kind'        => 'nest',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'Added %s wrapper', 'woocommerce' ),
						self::block_label( $rec['name'] )
					),
					'path'        => $rec['path'],
				);
				continue;
			}
			$added_blocks[] = array(
				'name'  => $rec['name'],
				'label' => self::block_label( $rec['name'] ),
				'path'  => $rec['path'],
			);
		}//end foreach

		// Pass 3: classify unmatched post, with the same wrapper suppression.
		foreach ( $post_records as $i => $rec ) {
			if ( isset( $matched_post[ $i ] ) ) {
				continue;
			}
			if ( isset( self::STRUCTURAL_BLOCK_NAMES[ $rec['name'] ] ) ) {
				if ( isset( $matched_post_parents[ $rec['name'] ] ) ) {
					continue;
				}
				$structural_changes[] = array(
					'kind'        => 'nest',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'Removed %s wrapper', 'woocommerce' ),
						self::block_label( $rec['name'] )
					),
					'path'        => $rec['path'],
				);
				continue;
			}
			$removed_blocks[] = array(
				'name'  => $rec['name'],
				'label' => self::block_label( $rec['name'] ),
				'path'  => $rec['path'],
			);
		}//end foreach

		// Reorder pass: pair like-named entries between added and removed
		// and reclassify them as a `reorder` structural change. LCS only
		// matches in-order, so an actual reorder of matched blocks lands here
		// as add+remove pairs. Reorder entries omit `path` because they
		// describe a structural fact, not a single block.
		//
		// Pairing keys on the normalized block name (e.g. `core/heading`),
		// not the humanized label. Two distinct namespaces — say
		// `vendor-a/header` and `vendor-b/header` — both produce the label
		// `Header` after `block_label()` strips the namespace; pairing on
		// label would falsely emit a single `Reordered Header` entry instead
		// of one add + one remove.
		$added_name_indices   = array();
		$removed_name_indices = array();
		foreach ( $added_blocks as $i => $entry ) {
			$added_name_indices[ (string) $entry['name'] ][] = $i;
		}
		foreach ( $removed_blocks as $i => $entry ) {
			$removed_name_indices[ (string) $entry['name'] ][] = $i;
		}

		$dropped_added   = array();
		$dropped_removed = array();
		foreach ( $added_name_indices as $name => $a_indices ) {
			$r_indices = $removed_name_indices[ $name ] ?? array();
			$pairs     = (int) min( count( $a_indices ), count( $r_indices ) );
			if ( 0 === $pairs ) {
				continue;
			}
			$label = self::block_label( (string) $name );
			for ( $i = 0; $i < $pairs; $i++ ) {
				$structural_changes[] = array(
					'kind'        => 'reorder',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'Reordered %s', 'woocommerce' ),
						$label
					),
				);

				$dropped_added[ $a_indices[ $i ] ]   = true;
				$dropped_removed[ $r_indices[ $i ] ] = true;
			}
		}//end foreach
		$added_blocks   = self::reject_indices( $added_blocks, $dropped_added );
		$removed_blocks = self::reject_indices( $removed_blocks, $dropped_removed );

		return array(
			'added_blocks'       => $added_blocks,
			'removed_blocks'     => $removed_blocks,
			'copy_changes'       => $copy_changes,
			'structural_changes' => $structural_changes,
		);
	}

	/**
	 * Compute a three-way block-level diff between core, base, and yours.
	 *
	 * Two LCS passes (yours-vs-base, core-vs-base) build a tripartite alignment
	 * keyed on base. For each base block, we then know whether yours and/or core
	 * has changed it relative to base — yielding a four-case classification:
	 *
	 * - !yours_changed && !core_changed → no entry (block unchanged on both sides)
	 * - !yours_changed &&  core_changed → copy_change (auto-resolvable to use_core)
	 * -  yours_changed && !core_changed → no entry (merchant edit; preserve silently)
	 * -  yours_changed &&  core_changed → copy_change (true conflict)
	 *
	 * Additions (in yours OR core but not in base) and removals (in base but not in
	 * one side) are handled in dedicated passes after the matched-pair classification.
	 * Yours-only adds become `removed_blocks` ("would be removed by wholesale apply,
	 * preserved by selective apply"). Core-only adds become `added_blocks`. Yours-removed
	 * but core-kept becomes a `merchant_removed` structural entry — apply does NOT re-add.
	 *
	 * Compared to {@see self::diff_records()}, this method removes the inversion-guard
	 * heuristic entirely: with a known base, the diff is deterministic on any post,
	 * including heavily-customized ones. The reorder pass is also dropped: the LCS
	 * tail-pairing bug it compensated for cannot fire under three-way attribution.
	 *
	 * Structural relocations (a matched pair whose `parent_name` differs between
	 * core and post) are intentionally not surfaced here, unlike the 2-way path's
	 * `Moved %1$s into %2$s` entry. Selective apply preserves the merchant's
	 * structure either way, so the move is something the merchant cannot act on.
	 *
	 * @internal
	 *
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $core_records Core side (current canonical).
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $base_records Base side (canonical at last system write).
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $post_records Post side (merchant's current post_content).
	 *
	 * @return array{added_blocks:array<int, array<string, mixed>>, removed_blocks:array<int, array<string, mixed>>, copy_changes:array<int, array<string, mixed>>, structural_changes:array<int, array<string, mixed>>}
	 *
	 * @since 10.9.0
	 */
	public static function diff_records_three_way( array $core_records, array $base_records, array $post_records ): array {
		$core_to_base = self::lcs_matches( $core_records, $base_records );
		$post_to_base = self::lcs_matches( $post_records, $base_records );

		// Invert into base-keyed lookups so a single iteration over base records
		// can decide each block's fate against both sides.
		$base_to_core = array();
		foreach ( $core_to_base as $pair ) {
			$base_to_core[ $pair[1] ] = $pair[0];
		}
		$base_to_post = array();
		foreach ( $post_to_base as $pair ) {
			$base_to_post[ $pair[1] ] = $pair[0];
		}

		$matched_core_indices = array();
		$matched_post_indices = array();

		$copy_changes       = array();
		$added_blocks       = array();
		$removed_blocks     = array();
		$structural_changes = array();

		$core_name_counts = array_count_values( array_map( static fn( array $r ): string => $r['name'], $core_records ) );
		$occurrence_index = array();

		// Pass 1: classify each base-anchored block by what changed relative to base.
		foreach ( $base_records as $b_idx => $base ) {
			$core_idx = $base_to_core[ $b_idx ] ?? null;
			$post_idx = $base_to_post[ $b_idx ] ?? null;

			if ( null !== $core_idx ) {
				$matched_core_indices[ $core_idx ] = true;
			}
			if ( null !== $post_idx ) {
				$matched_post_indices[ $post_idx ] = true;
			}

			if ( null === $core_idx && null === $post_idx ) {
				// Both sides removed it — no-op.
				continue;
			}

			if ( null === $core_idx ) {
				// Core removed it; yours kept it. Preserve on apply.
				$removed_blocks[] = array(
					'name'  => $post_records[ $post_idx ]['name'],
					'label' => self::block_label( $post_records[ $post_idx ]['name'] ),
					'path'  => $post_records[ $post_idx ]['path'],
				);
				continue;
			}

			if ( null === $post_idx ) {
				// Yours removed it; core kept it. Don't re-add — respect merchant intent.
				$structural_changes[] = array(
					'kind'        => 'merchant_removed',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'You removed %s; core still has it.', 'woocommerce' ),
						self::block_label( $core_records[ $core_idx ]['name'] )
					),
					'path'        => $base['path'],
				);
				continue;
			}

			// Both sides have the block — increment occurrence ordinal for every
			// matched pair, regardless of whether a copy_change is emitted, so
			// "Paragraph N of M" reflects the block's true ordinal across the run.
			// Mirrors the 2-way `diff_records()` placement of the counter.
			$core                      = $core_records[ $core_idx ];
			$post                      = $post_records[ $post_idx ];
			$name                      = $core['name'];
			$occurrence_index[ $name ] = ( $occurrence_index[ $name ] ?? 0 ) + 1;

			// Known limitation: comparison is `inner_text` only; block `attrs` (colors,
			// alignment, etc.) don't register as changes. With `auto_resolvable: true`
			// the drawer can silently overwrite an attr-only merchant edit. Follow-up
			// to extend the comparison to a stable hash of `attrs`.
			$yours_changed = ( $base['inner_text'] !== $post['inner_text'] );
			$core_changed  = ( $base['inner_text'] !== $core['inner_text'] );

			if ( ! $yours_changed && ! $core_changed ) {
				continue;
			}
			if ( ! $core_changed ) {
				// Yours edited, core didn't — merchant-only edit, preserve silently.
				continue;
			}

			// Core changed (with or without yours also changing).
			$copy_changes[] = array(
				'block'           => self::block_label( $name ),
				'before'          => self::truncate_text( $post['inner_text'] ),
				'after'           => self::truncate_text( $core['inner_text'] ),
				'occurrence'      => $occurrence_index[ $name ],
				'total'           => (int) ( $core_name_counts[ $name ] ?? 1 ),
				'path'            => $post['path'],
				'auto_resolvable' => ! $yours_changed,
			);
		}//end foreach

		// Pass 2: unmatched core records → added_blocks. Structural wrappers
		// (`core/group`, `core/columns`, `core/column`, `core/row`) route to
		// `structural_changes` instead — the selective applier skips them at
		// merge time, and surfacing them as `added_blocks` would advertise an
		// "Added Group block" the apply will never apply. Mirrors the 2-way
		// `diff_records()` handling of structural wrappers.
		foreach ( $core_records as $c_idx => $rec ) {
			if ( isset( $matched_core_indices[ $c_idx ] ) ) {
				continue;
			}
			if ( isset( self::STRUCTURAL_BLOCK_NAMES[ $rec['name'] ] ) ) {
				$structural_changes[] = array(
					'kind'        => 'nest',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'Added %s wrapper', 'woocommerce' ),
						self::block_label( $rec['name'] )
					),
					'path'        => $rec['path'],
				);
				continue;
			}
			$added_blocks[] = array(
				'name'  => $rec['name'],
				'label' => self::block_label( $rec['name'] ),
				'path'  => $rec['path'],
			);
		}

		// Pass 3: unmatched post records → removed_blocks (yours-only additions, preserved by default).
		// Same structural-wrapper handling as Pass 2 — yours-only structural blocks land in
		// `structural_changes` rather than `removed_blocks`.
		foreach ( $post_records as $p_idx => $rec ) {
			if ( isset( $matched_post_indices[ $p_idx ] ) ) {
				continue;
			}
			if ( isset( self::STRUCTURAL_BLOCK_NAMES[ $rec['name'] ] ) ) {
				$structural_changes[] = array(
					'kind'        => 'nest',
					'description' => sprintf(
						/* translators: %s: block name */
						__( 'Removed %s wrapper', 'woocommerce' ),
						self::block_label( $rec['name'] )
					),
					'path'        => $rec['path'],
				);
				continue;
			}
			$removed_blocks[] = array(
				'name'  => $rec['name'],
				'label' => self::block_label( $rec['name'] ),
				'path'  => $rec['path'],
			);
		}

		return array(
			'added_blocks'       => $added_blocks,
			'removed_blocks'     => $removed_blocks,
			'copy_changes'       => $copy_changes,
			'structural_changes' => $structural_changes,
		);
	}

	/**
	 * Drop entries at the given indices and return a re-indexed list. Used by
	 * the reorder pass to remove paired entries from added/removed without
	 * losing the rich shape of the survivors.
	 *
	 * @param array<int, array<string, mixed>> $entries Source list.
	 * @param array<int, true>                 $drop    Indices to drop.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function reject_indices( array $entries, array $drop ): array {
		$out = array();
		foreach ( $entries as $i => $entry ) {
			if ( ! isset( $drop[ $i ] ) ) {
				$out[] = $entry;
			}
		}
		return $out;
	}

	/**
	 * Bonus weight per name match used to tiebreak by text similarity. Must be
	 * small enough that cardinality always dominates: with sequences up to ~100
	 * blocks long, the total accumulated bonus is bounded by 0.1 — well under
	 * the +1.0 contribution of an extra name match.
	 */
	private const LCS_SIMILARITY_BONUS = 0.001;

	/**
	 * Compute LCS over two flattened record sequences with text similarity as
	 * a tiebreaker. Returns matched pairs as `(core_index, post_index)` tuples
	 * in increasing order on both axes.
	 *
	 * Cardinality (number of name matches) is the primary criterion. When two
	 * alignments tie on cardinality — common on uniform block-name runs like
	 * `paragraph × N` — the diagonal score adds a tiny bonus proportional to
	 * the Jaccard word similarity of the two records' inner text. The bonus is
	 * bounded so it can never trade a name match for a similarity gain. Net
	 * effect: when the merchant edits an existing paragraph, the LCS pairs
	 * their edited version with core's original (high word overlap) instead of
	 * with an unrelated paragraph that happens to be in the right position.
	 *
	 * Public so {@see WCEmailTemplateSelectiveApplier} can reuse the same
	 * matched-pair alignment when applying merchant choices. Internal-namespace
	 * bounded; not part of any external contract.
	 *
	 * @internal
	 *
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $a Core records.
	 * @param array<int, array{path:array<int|string>, parent_name:?string, name:string, inner_text:string}> $b Post records.
	 *
	 * @return array<int, array{0:int, 1:int}>
	 */
	public static function lcs_matches( array $a, array $b ): array {
		$n = count( $a );
		$m = count( $b );
		if ( 0 === $n || 0 === $m ) {
			return array();
		}

		$dp = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0.0 ) );
		for ( $i = 1; $i <= $n; $i++ ) {
			for ( $j = 1; $j <= $m; $j++ ) {
				$up   = $dp[ $i - 1 ][ $j ];
				$left = $dp[ $i ][ $j - 1 ];
				if ( $a[ $i - 1 ]['name'] === $b[ $j - 1 ]['name'] ) {
					$bonus = self::LCS_SIMILARITY_BONUS * self::similarity_score(
						$a[ $i - 1 ]['inner_text'],
						$b[ $j - 1 ]['inner_text']
					);
					// Must compare against `up` and `left` even when names
					// match: the bonus on the diagonal can be smaller than
					// bonuses already accumulated in `up` / `left`. Taking the
					// diagonal unconditionally would discard a higher-scoring
					// alignment found via a different path. Cardinality is
					// preserved because the max bonus per match is far below
					// 1.0, so the diagonal still wins whenever it adds a new
					// name match.
					$diagonal       = $dp[ $i - 1 ][ $j - 1 ] + 1.0 + $bonus;
					$dp[ $i ][ $j ] = max( $diagonal, $up, $left );
				} else {
					$dp[ $i ][ $j ] = max( $up, $left );
				}
			}//end for
		}//end for

		$pairs = array();
		$i     = $n;
		$j     = $m;
		while ( $i > 0 && $j > 0 ) {
			if ( $a[ $i - 1 ]['name'] === $b[ $j - 1 ]['name'] ) {
				$bonus          = self::LCS_SIMILARITY_BONUS * self::similarity_score(
					$a[ $i - 1 ]['inner_text'],
					$b[ $j - 1 ]['inner_text']
				);
				$diagonal_score = $dp[ $i - 1 ][ $j - 1 ] + 1.0 + $bonus;
				if ( abs( $dp[ $i ][ $j ] - $diagonal_score ) < 1e-9 ) {
					$pairs[] = array( $i - 1, $j - 1 );
					--$i;
					--$j;
					continue;
				}
			}
			if ( $dp[ $i - 1 ][ $j ] >= $dp[ $i ][ $j - 1 ] ) {
				--$i;
			} else {
				--$j;
			}
		}

		return array_reverse( $pairs );
	}

	/**
	 * Jaccard word-set similarity in [0.0, 1.0]. Used purely as an LCS
	 * tiebreaker, so robustness is more important than linguistic accuracy:
	 * lowercase + split on whitespace + intersect-over-union of the resulting
	 * word sets. Two empty strings score 1.0 (treated as identical).
	 *
	 * Lowercasing goes through `wc_strtolower()` (mb-aware with an ASCII
	 * fallback), not `strtolower()` — the latter is ASCII-only and would
	 * leave accented / Cyrillic / Greek characters uppercase, killing
	 * word-overlap matches on translated email templates.
	 *
	 * @param string $a First text.
	 * @param string $b Second text.
	 */
	private static function similarity_score( string $a, string $b ): float {
		$a = trim( $a );
		$b = trim( $b );
		if ( '' === $a && '' === $b ) {
			return 1.0;
		}
		if ( '' === $a || '' === $b ) {
			return 0.0;
		}

		$split_a = preg_split( '/\s+/', wc_strtolower( $a ), -1, PREG_SPLIT_NO_EMPTY );
		$split_b = preg_split( '/\s+/', wc_strtolower( $b ), -1, PREG_SPLIT_NO_EMPTY );
		$words_a = array_unique( false === $split_a ? array() : $split_a );
		$words_b = array_unique( false === $split_b ? array() : $split_b );
		if ( empty( $words_a ) && empty( $words_b ) ) {
			return 1.0;
		}

		$intersect = count( array_intersect( $words_a, $words_b ) );
		$union     = count( array_unique( array_merge( $words_a, $words_b ) ) );
		return $union > 0 ? $intersect / $union : 0.0;
	}

	/**
	 * Convert a normalized block name into a human-readable label. Used for
	 * both structured payload entries and the localized catalog.
	 *
	 * `core/heading` → `Heading`; `woocommerce/email-content` → `Email content`.
	 *
	 * @param string $normalized_name Normalized block name.
	 */
	private static function block_label( string $normalized_name ): string {
		$bare = preg_replace( '#^[a-z0-9\-]+/#', '', $normalized_name );
		$bare = (string) $bare;
		$bare = str_replace( array( '-', '_' ), ' ', $bare );
		$bare = trim( $bare );
		if ( '' === $bare ) {
			return $normalized_name;
		}
		return ucfirst( $bare );
	}

	/**
	 * UTF-8-safe truncation to {@see self::COPY_TRUNCATE_CHARS}.
	 *
	 * @param string $text Cleaned inner-text candidate.
	 */
	private static function truncate_text( string $text ): string {
		$limit = self::COPY_TRUNCATE_CHARS;
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text ) <= $limit ) {
				return $text;
			}
			return rtrim( mb_substr( $text, 0, $limit ) ) . '…';
		}
		if ( strlen( $text ) <= $limit ) {
			return $text;
		}
		return rtrim( substr( $text, 0, $limit ) ) . '…';
	}

	/**
	 * Render the structured payload into pre-localized one-liners, using a
	 * fixed string catalog. The "N of M" position-and-total disambiguator
	 * (e.g. "Updated wording in Paragraph 1 of 2") only fires when a block
	 * label appears more than once on the matched side.
	 *
	 * @param array{added_blocks:array<int, array<string, mixed>>, removed_blocks:array<int, array<string, mixed>>, copy_changes:array<int, array<string, mixed>>, structural_changes:array<int, array<string, mixed>>} $structured Diff output.
	 * @return string[]
	 */
	private static function to_summary_lines( array $structured ): array {
		$lines = array();

		// Singular form drops the indefinite article ("Added Image block" rather
		// than "Added a Image block") because English a/an depends on phonetics
		// the catalog can't infer at format time, and many target locales have no
		// equivalent article at all. Plural already reads "Added 3 Image blocks"
		// without an article, so dropping it in singular keeps both forms
		// stylistically consistent.
		$added_labels = array_map( static fn( array $e ): string => (string) ( $e['label'] ?? '' ), $structured['added_blocks'] );
		$added_counts = array_count_values( array_filter( $added_labels, static fn( string $l ): bool => '' !== $l ) );
		foreach ( $added_counts as $label => $count ) {
			$count = (int) $count;
			if ( 1 === $count ) {
				$lines[] = sprintf(
					/* translators: %s: block name */
					__( 'Added %s block', 'woocommerce' ),
					(string) $label
				);
			} else {
				$lines[] = sprintf(
					/* translators: 1: number of blocks added; 2: block name */
					__( 'Added %1$d %2$s blocks', 'woocommerce' ),
					$count,
					(string) $label
				);
			}
		}

		$removed_labels = array_map( static fn( array $e ): string => (string) ( $e['label'] ?? '' ), $structured['removed_blocks'] );
		$removed_counts = array_count_values( array_filter( $removed_labels, static fn( string $l ): bool => '' !== $l ) );
		foreach ( $removed_counts as $label => $count ) {
			$count = (int) $count;
			if ( 1 === $count ) {
				$lines[] = sprintf(
					/* translators: %s: block name */
					__( 'Removed %s block', 'woocommerce' ),
					(string) $label
				);
			} else {
				$lines[] = sprintf(
					/* translators: 1: number of blocks removed; 2: block name */
					__( 'Removed %1$d %2$s blocks', 'woocommerce' ),
					$count,
					(string) $label
				);
			}
		}

		foreach ( $structured['copy_changes'] as $change ) {
			$label      = (string) ( $change['block'] ?? '' );
			$occurrence = (int) ( $change['occurrence'] ?? 1 );
			$total      = (int) ( $change['total'] ?? 1 );

			if ( $total > 1 ) {
				$lines[] = sprintf(
					/* translators: 1: block name (e.g. "Paragraph"); 2: position of the edited block (e.g. 1); 3: total blocks of that type in the template (e.g. 2). Reads as "Updated wording in Paragraph 1 of 2". */
					__( 'Updated wording in %1$s %2$d of %3$d', 'woocommerce' ),
					$label,
					$occurrence,
					$total
				);
			} else {
				$lines[] = sprintf(
					/* translators: %s: block name */
					__( 'Updated wording in %s', 'woocommerce' ),
					$label
				);
			}
		}//end foreach

		foreach ( $structured['structural_changes'] as $change ) {
			$desc = (string) ( $change['description'] ?? '' );
			if ( '' !== $desc ) {
				$lines[] = $desc;
			}
		}

		return $lines;
	}

	/**
	 * Return the logger instance, lazily creating it the first time.
	 */
	private static function get_logger(): Email_Editor_Logger_Interface {
		if ( null === self::$logger ) {
			self::$logger = new Logger( wc_get_logger() );
		}
		return self::$logger;
	}
}

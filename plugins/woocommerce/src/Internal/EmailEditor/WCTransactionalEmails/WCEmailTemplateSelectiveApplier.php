<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Applies a partial set of core template changes to a customised `woo_email`
 * post, driven by per-conflict merchant choices. Pairs with
 * {@see WCEmailTemplateChangeSummary} (the diff data source) and
 * {@see WCEmailTemplateAutoApplier} (the wholesale-apply primitive) to power
 * the Review drawer's "Keep yours / Use core" workflow.
 *
 * V1 algorithm (spine = the merchant's post):
 *
 * - **`copy_changes`** (matched pair, different inner_text): the default is
 *   `use_core` for a block only core changed and `keep_yours` for one the
 *   merchant also touched. An explicit choice overrides either. Applying
 *   `use_core` replaces the matched block's `innerHTML` / `innerContent` with
 *   core's version, and takes core's `attrs` too on the auto-resolved path,
 *   where the merchant's still match the base. An explicit `use_core` over the
 *   merchant's own styling keeps their `attrs`.
 * - **`added_blocks`** (in core, not in post): always applied. Inserted at
 *   the equivalent position from core's path; if the path can't be navigated
 *   in the post tree, falls back to appending at the closest level.
 * - **`removed_blocks`** (in post, not in core): always preserved (Keep
 *   yours).
 * - **`structural_changes`** (`nest` / `reorder`): not applied in v1. The
 *   merchant's structure is preserved; the response carries
 *   `structural_skipped: true` if any structural delta was observed.
 *
 * Undo: each apply writes a single-step snapshot of the prior `post_content`
 * to {@see self::SNAPSHOT_META_KEY}, keyed by a UUID `revision_id`. A
 * subsequent apply overwrites the snapshot. {@see self::undo()} restores from
 * the snapshot when the supplied `revision_id` matches.
 *
 * Three-way merge (since 10.9.0): when the post has
 * {@see WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY} meta,
 * `apply_selectively()` hands that base render to `merge()`:
 *
 * - Core and post blocks are paired through the base, exactly as the change
 *   summary pairs them, so the merge writes what the merchant reviewed. Blocks
 *   the merchant deleted are not re-inserted.
 * - Each pair is classified with the summary's own rule
 *   ({@see WCEmailTemplateChangeSummary::merchant_changed()}) rather than read
 *   back out of the cached summary payload, so an apply can't act on a
 *   classification the merchant was never shown.
 * - A block only the merchant changed is preserved silently — it isn't a
 *   conflict, and a `use_core` decision for it is ignored.
 *
 * Posts without the meta keep the legacy two-way behavior — `merge()` runs
 * its own LCS, treats every text-divergent matched pair as a candidate for
 * `use_core`, and defaults each to `keep_yours`.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since   10.9.0
 */
class WCEmailTemplateSelectiveApplier {
	/**
	 * Post meta key for the single-step pre-apply snapshot. Stores an array
	 * with `revision_id`, `content`, `last_core_render`, `version`,
	 * `source_hash`, `last_synced_at`, and `snapshot_at` (UTC `Y-m-d H:i:s`).
	 * The status is **not** recorded — on undo it is recomputed via
	 * {@see WCEmailTemplateDivergenceDetector::reclassify()} against current
	 * state.
	 *
	 * @var string
	 */
	public const SNAPSHOT_META_KEY = '_wc_email_template_pre_apply_snapshot';

	/**
	 * Re-entrancy flag set while the applier rewrites a post. Mirrors
	 * {@see WCEmailTemplateAutoApplier::is_auto_applying()}; future
	 * `save_post` listeners (RSM-145 Tracks event firing) should consult both
	 * before treating a write as a merchant edit.
	 *
	 * @var bool
	 */
	private static bool $is_applying = false;

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static ?Email_Editor_Logger_Interface $logger = null;

	/**
	 * Apply the selected set of core template changes to a `woo_email` post.
	 *
	 * @param int                                                        $post_id The `woo_email` post ID.
	 * @param array<int, array{path:array<int|string>, decision:string}> $choices Per-conflict choices keyed implicitly by `path`. `decision` is `'keep_yours'` or `'use_core'`. A path with no choice falls back to the summary's classification: `use_core` when only core changed the block, `keep_yours` otherwise. Choices for paths that don't correspond to a `copy_changes` entry are ignored — added, removed and structural entries cannot be overridden.
	 *
	 * @return array<string, mixed>|\WP_Error On success, an array with keys
	 *                                        `merged_content`, `revision_id`,
	 *                                        `version_to`, `status` ('applied'),
	 *                                        `structural_skipped`, and
	 *                                        `aliases_migrated` (a list of
	 *                                        deprecated block-name aliases
	 *                                        rewritten to canonical form
	 *                                        during the apply, e.g.
	 *                                        `['woo/email-content']`).
	 *
	 * @since 10.9.0
	 */
	public static function apply_selectively( int $post_id, array $choices ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || Integration::EMAIL_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: post ID */
					__( 'No woo_email post found for ID %d.', 'woocommerce' ),
					$post_id
				),
				array( 'status' => 404 )
			);
		}

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$email_id      = $posts_manager->get_email_type_from_post_id( $post_id );
		if ( ! is_string( $email_id ) || '' === $email_id ) {
			return new \WP_Error(
				'email_not_found',
				__( 'No email type associated with the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id );
		if ( null === $sync_config ) {
			return new \WP_Error(
				'not_sync_enabled',
				sprintf(
					/* translators: %s: email ID */
					__( 'Email "%s" is not registered for template sync; selective apply is unavailable.', 'woocommerce' ),
					$email_id
				),
				array( 'status' => 422 )
			);
		}

		$emails = $posts_manager->get_emails_by_id();
		$email  = $emails[ $email_id ] ?? null;
		if ( ! $email instanceof \WC_Email ) {
			return new \WP_Error(
				'email_not_found',
				sprintf(
					/* translators: %s: email ID */
					__( 'Email instance for "%s" is unavailable.', 'woocommerce' ),
					$email_id
				),
				array( 'status' => 404 )
			);
		}

		$summary = WCEmailTemplateChangeSummary::summarize( $post_id );
		if ( ! empty( $summary['is_fallback'] ) ) {
			return new \WP_Error(
				'no_actionable_summary',
				__( 'No actionable diff is available for this post; refusing to apply.', 'woocommerce' ),
				array( 'status' => 422 )
			);
		}

		$post_content = (string) $post->post_content;

		try {
			$core_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		} catch ( \Throwable $e ) {
			self::get_logger()->error(
				sprintf(
					'Selective apply failed to compute canonical content for email "%s": %s',
					$email_id,
					$e->getMessage()
				),
				array(
					'email_id' => $email_id,
					'post_id'  => $post_id,
					'context'  => 'email_template_selective_applier',
				)
			);
			return new \WP_Error(
				'canonical_render_failed',
				__( 'Failed to compute the canonical core render.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}//end try

		// When the post has `last_core_render` meta, the merge pairs blocks through that base
		// and classifies each pair with the same rule the change summary used, so it applies
		// what the merchant reviewed rather than reading a cached payload back.
		$base_render_for_merge = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, true );

		$merged_result      = self::merge( $post_content, $core_content, $choices, $base_render_for_merge );
		$merged_content     = $merged_result['content'];
		$structural_skipped = $merged_result['structural_skipped'];
		$aliases_migrated   = $merged_result['aliases_migrated'];

		// Alignment is decided on whitespace-normalized content: serialize_blocks()
		// can't reproduce the PHP template's literal whitespace byte-for-byte, so a
		// strict === would never match even when the trees are semantically equal.
		// When aligned, persist canonical verbatim so source_hash, classify_post,
		// and downstream byte comparisons hold without further normalization.
		$is_aligned_with_canonical = (
			self::normalize_for_comparison( $merged_content )
			=== self::normalize_for_comparison( $core_content )
		);
		if ( $is_aligned_with_canonical ) {
			$merged_content = $core_content;
		}

		// Snapshot every meta apply is about to overwrite. Restoring only a
		// subset would leave the banner / email-list gates reading stale
		// post-apply `_wc_email_template_version`, hiding the pending update.
		$prior_last_core_render = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, true );
		$prior_version          = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true );
		$prior_source_hash      = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
		$prior_last_synced_at   = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true );

		$revision_id = wp_generate_uuid4();
		$snapshot    = array(
			'revision_id'      => $revision_id,
			'content'          => $post_content,
			'last_core_render' => $prior_last_core_render,
			'version'          => $prior_version,
			'source_hash'      => $prior_source_hash,
			'last_synced_at'   => $prior_last_synced_at,
			'snapshot_at'      => gmdate( 'Y-m-d H:i:s' ),
		);
		update_post_meta( $post_id, self::SNAPSHOT_META_KEY, $snapshot );

		self::$is_applying = true;
		try {
			$updated = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $merged_content,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				delete_post_meta( $post_id, self::SNAPSHOT_META_KEY );
				return $updated;
			}

			$saved_post = get_post( $post_id );
			$saved_body = $saved_post instanceof \WP_Post ? (string) $saved_post->post_content : $merged_content;

			$source_hash = $is_aligned_with_canonical
				? sha1( $saved_body )
				: sha1( $core_content );
			$synced_at   = gmdate( 'Y-m-d H:i:s' );
			$version_to  = (string) $sync_config['version'];

			update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $version_to );
			update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, $source_hash );
			update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, $synced_at );
			// Three-way diff base reference: "what core looked like the last time we synced."
			// Always stamps the current canonical (NOT the merged content) — selective apply IS
			// a sync against the new canonical even if the merchant kept some yours-blocks.
			update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $core_content );

			if ( $is_aligned_with_canonical ) {
				WCEmailTemplateDivergenceDetector::reclassify( $post_id );
			} else {
				// reclassify() returns null in this branch (current_core ===
				// stored, current_post !== stored) and would leave prior status
				// untouched, so stamp directly.
				update_post_meta(
					$post_id,
					WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
					WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED
				);
			}
		} finally {
			self::$is_applying = false;
		}//end try

		// Invalidate the change-summary cache so the next read reflects the merged state.
		WCEmailTemplateChangeSummary::reset_cache();

		// Fire `_update_applied` for the selective-applier path. Static extensions:
		// the selective applier only acts on `core_updated_customized` posts, so
		// `had_customizations` is always true and `auto_resolved` is always false.
		WCEmailTemplateSyncTracker::record_selective_applied( $post_id );

		return array(
			'merged_content'     => $merged_content,
			'revision_id'        => $revision_id,
			'version_to'         => $version_to,
			'status'             => 'applied',
			'structural_skipped' => $structural_skipped,
			'aliases_migrated'   => $aliases_migrated,
		);
	}

	/**
	 * Restore the pre-apply snapshot for a post. Single-step undo only: the
	 * snapshot meta is consumed (deleted) on success, so a second undo
	 * without an intervening apply returns 410 Gone.
	 *
	 * @param int    $post_id     The `woo_email` post ID.
	 * @param string $revision_id The UUID returned by the prior `apply_selectively()` call.
	 *
	 * @return array<string, mixed>|\WP_Error On success, an array with keys
	 *                                        `restored_content` and `status`
	 *                                        ('restored').
	 *
	 * @since 10.9.0
	 */
	public static function undo( int $post_id, string $revision_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || Integration::EMAIL_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: post ID */
					__( 'No woo_email post found for ID %d.', 'woocommerce' ),
					$post_id
				),
				array( 'status' => 404 )
			);
		}

		$snapshot = get_post_meta( $post_id, self::SNAPSHOT_META_KEY, true );
		if ( ! is_array( $snapshot ) || ! isset( $snapshot['revision_id'], $snapshot['content'] ) ) {
			return new \WP_Error(
				'undo_unavailable',
				__( 'No pre-apply snapshot is available for this post.', 'woocommerce' ),
				array( 'status' => 410 )
			);
		}

		if ( (string) $snapshot['revision_id'] !== $revision_id ) {
			return new \WP_Error(
				'undo_unavailable',
				__( 'The supplied revision ID does not match the latest snapshot for this post.', 'woocommerce' ),
				array( 'status' => 410 )
			);
		}

		$restored_content = (string) $snapshot['content'];

		self::$is_applying = true;
		try {
			$updated = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $restored_content,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			// Restore every meta apply stamped. `restore_meta_from_snapshot`
			// no-ops on keys missing from older snapshot formats.
			self::restore_meta_from_snapshot(
				$post_id,
				$snapshot,
				'last_core_render',
				WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY
			);
			self::restore_meta_from_snapshot(
				$post_id,
				$snapshot,
				'version',
				WCEmailTemplateDivergenceDetector::VERSION_META_KEY
			);
			self::restore_meta_from_snapshot(
				$post_id,
				$snapshot,
				'source_hash',
				WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY
			);
			self::restore_meta_from_snapshot(
				$post_id,
				$snapshot,
				'last_synced_at',
				WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY
			);

			// The snapshot's prior_status was correct at snapshot time, but
			// the world may have moved since (core released, canonical
			// changed). Ask the classifier for the truth against current
			// state instead of stamping a stale value.
			WCEmailTemplateDivergenceDetector::reclassify( $post_id );

			delete_post_meta( $post_id, self::SNAPSHOT_META_KEY );
		} finally {
			self::$is_applying = false;
		}//end try

		WCEmailTemplateChangeSummary::reset_cache();

		return array(
			'restored_content' => $restored_content,
			'status'           => 'restored',
		);
	}

	/**
	 * Whether the applier is currently rewriting a post. Mirrors the
	 * auto-applier's flag so downstream listeners can ignore system writes.
	 *
	 * @since 10.9.0
	 */
	public static function is_applying(): bool {
		return self::$is_applying;
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
	 * Restore one post meta from a snapshot entry. Empty prior values delete
	 * the meta rather than writing an empty string; missing keys (older
	 * snapshot format) no-op.
	 *
	 * @param int                  $post_id       The post ID.
	 * @param array<string, mixed> $snapshot      The snapshot array stored in SNAPSHOT_META_KEY.
	 * @param string               $snapshot_key  The key inside the snapshot array.
	 * @param string               $post_meta_key The post meta key to write back to.
	 */
	private static function restore_meta_from_snapshot( int $post_id, array $snapshot, string $snapshot_key, string $post_meta_key ): void {
		if ( ! array_key_exists( $snapshot_key, $snapshot ) ) {
			return;
		}
		$value = (string) $snapshot[ $snapshot_key ];
		if ( '' !== $value ) {
			update_post_meta( $post_id, $post_meta_key, $value );
		} else {
			delete_post_meta( $post_id, $post_meta_key );
		}
	}

	/**
	 * Compute the merged block tree, starting from the merchant's post and
	 * layering on core's changes per the v1 algorithm.
	 *
	 * With `$base_content` (the caller's `last_core_render` meta was set), core
	 * and post blocks are paired through the base with
	 * {@see WCEmailTemplateChangeSummary::align_to_base()}, the same pairing the
	 * summary shown to the merchant uses, and each pair is classified with
	 * {@see WCEmailTemplateChangeSummary::merchant_changed()}, the same rule:
	 *
	 * - A block only core changed defaults to `use_core` — the drawer lists it as
	 *   auto-resolved and states it will apply — and takes core's `attrs` too,
	 *   since the merchant's match the base by definition. This holds for a
	 *   styling or markup change the drawer cannot describe as a wording change:
	 *   the merchant never touched the block, so it follows core rather than
	 *   drifting out of step with the base for good.
	 * - A block the merchant also changed defaults to `keep_yours` and keeps its
	 *   `attrs` when an explicit `use_core` is passed for it.
	 * - Core blocks the merchant deleted are not re-inserted.
	 *
	 * Without `$base_content` (legacy two-way fallback), core and post are paired
	 * with a direct LCS, every matched pair with differing inner_text is eligible
	 * for `use_core`, and the default stays `keep_yours`.
	 *
	 * @param string                                                     $post_content Merchant's current `post_content`.
	 * @param string                                                     $core_content Canonical core render.
	 * @param array<int, array{path:array<int|string>, decision:string}> $choices      Per-conflict choices.
	 * @param string                                                     $base_content The `last_core_render`, or `''` for the legacy two-way merge.
	 *
	 * @return array{content:string, structural_skipped:bool, aliases_migrated:string[]}
	 */
	private static function merge( string $post_content, string $core_content, array $choices, string $base_content = '' ): array {
		$post_blocks = parse_blocks( $post_content );
		$core_blocks = parse_blocks( $core_content );

		if ( empty( $post_blocks ) || empty( $core_blocks ) ) {
			return array(
				'content'            => $post_content,
				'structural_skipped' => false,
				'aliases_migrated'   => array(),
			);
		}

		$post_records = WCEmailTemplateChangeSummary::flatten_blocks( $post_blocks );
		$core_records = WCEmailTemplateChangeSummary::flatten_blocks( $core_blocks );

		$three_way                 = '' !== $base_content;
		$merchant_removed_core_set = array();
		$base_of_post              = array();
		if ( $three_way ) {
			$base_records = WCEmailTemplateChangeSummary::flatten_blocks( parse_blocks( $base_content ) );
			$matches      = array();
			foreach ( WCEmailTemplateChangeSummary::align_to_base( $core_records, $base_records, $post_records ) as $base_idx => $sides ) {
				if ( null === $sides['core'] ) {
					continue;
				}
				if ( null === $sides['post'] ) {
					$merchant_removed_core_set[ $sides['core'] ] = true;
					continue;
				}
				$matches[]                      = array( $sides['core'], $sides['post'] );
				$base_of_post[ $sides['post'] ] = $base_records[ $base_idx ];
			}
		} else {
			$matches = WCEmailTemplateChangeSummary::lcs_matches( $core_records, $post_records );
		}

		$choice_map = array();
		foreach ( $choices as $choice ) {
			if ( ! is_array( $choice ) || ! isset( $choice['path'] ) || ! is_array( $choice['path'] ) ) {
				continue;
			}
			$decision = (string) ( $choice['decision'] ?? 'keep_yours' );
			if ( 'use_core' !== $decision && 'keep_yours' !== $decision ) {
				continue;
			}
			$choice_map[ self::path_key( $choice['path'] ) ] = $decision;
		}

		// Pass 1: matched pairs. Apply use_core decisions on copy changes;
		// detect parent-name diffs (structural punted, but we still flag
		// `structural_skipped` so the caller can surface it).
		$structural_skipped = false;
		$matched_core_set   = array();
		$matched_post_set   = array();
		foreach ( $matches as $pair ) {
			$core_rec = $core_records[ $pair[0] ];
			$post_rec = $post_records[ $pair[1] ];
			$post_key = self::path_key( $post_rec['path'] );

			$matched_core_set[ $pair[0] ] = true;
			$matched_post_set[ $pair[1] ] = true;

			if ( $core_rec['parent_name'] !== $post_rec['parent_name'] ) {
				$structural_skipped = true;
			}

			$core_block = self::block_at_path( $core_blocks, $core_rec['path'] );
			$post_block = self::block_at_path( $post_blocks, $post_rec['path'] );
			if ( null === $core_block || null === $post_block ) {
				continue;
			}

			// A block holding other blocks never takes core's content: that would
			// replace `innerBlocks` wholesale and discard merchant edits this same
			// pass is preserving, since the children are paired separately and each
			// gets its own turn. The quote in the customer-note template is one.
			// Its own `attrs` are still safe to follow — they say nothing about
			// what is inside — so a restyled container is handled below.
			$holds_blocks = ! empty( $core_block['innerBlocks'] ) || ! empty( $post_block['innerBlocks'] );

			$auto_resolvable = false;
			if ( $three_way ) {
				// Markup and attributes, not stripped text: a merchant who arrived
				// at core's wording but linked or emphasised part of it still has
				// something to replace.
				if ( $core_rec['inner_html_hash'] === $post_rec['inner_html_hash']
					&& $core_rec['attrs_hash'] === $post_rec['attrs_hash']
				) {
					continue;
				}

				$base_rec = $base_of_post[ $pair[1] ];

				// A block the merchant never touched follows core, whatever moved
				// in it. Styling and markup changes never reach the drawer — their
				// before and after text read identically — so leaving them would
				// strand the block: the stored base advances to core's version on
				// the next apply, and from then on the block looks merchant-edited
				// and can never auto-resolve again.
				$auto_resolvable = ! WCEmailTemplateChangeSummary::merchant_changed( $base_rec, $post_rec );

				if ( ! $auto_resolvable && $base_rec['inner_text'] === $core_rec['inner_text'] ) {
					// Core never reworded it, so the difference is the merchant's
					// own edit. Not a conflict, nothing to offer.
					continue;
				}

				if ( $holds_blocks ) {
					// Follow core's styling on a container the merchant left alone,
					// and leave its children to their own turns. Skipping it instead
					// would strand it: the stored base advances to core's version
					// regardless, so the block would look merchant-edited for good.
					if ( $auto_resolvable ) {
						$post_blocks = self::replace_block_attrs_at_path( $post_blocks, $post_rec['path'], $core_block );
					}
					continue;
				}
			} elseif ( $core_rec['inner_text'] === $post_rec['inner_text'] || $holds_blocks ) {
				continue;
			}

			// A block only core changed is listed in the drawer as auto-resolved,
			// stating the update will apply, and no choice is sent for it — by the
			// drawer or by a bulk or CLI caller — so the default has to carry it.
			// A choice, when one is sent, still wins.
			$decision = $choice_map[ $post_key ] ?? ( $auto_resolvable ? 'use_core' : 'keep_yours' );
			if ( 'use_core' !== $decision ) {
				continue;
			}

			// Core's `attrs` come along only on the auto-resolved path, where the
			// merchant's match the base and there is nothing of theirs to lose.
			// An explicit `use_core` over their own styling keeps their `attrs`,
			// at the cost of the block reading as merchant-edited from then on.
			$post_blocks = self::replace_block_content_at_path( $post_blocks, $post_rec['path'], $core_block, $auto_resolvable );
		}//end foreach

		// Pass 2: unmatched core records. Insert non-structural blocks at
		// the equivalent path; flag structural wrappers as skipped.
		$insertions = array();
		foreach ( $core_records as $i => $rec ) {
			if ( isset( $matched_core_set[ $i ] ) ) {
				continue;
			}
			if ( self::is_structural_block( $rec['name'] ) ) {
				$structural_skipped = true;
				continue;
			}
			if ( isset( $merchant_removed_core_set[ $i ] ) ) {
				continue;
			}
			$core_block = self::block_at_path( $core_blocks, $rec['path'] );
			if ( null === $core_block ) {
				continue;
			}
			$insertions[] = array(
				'path'  => $rec['path'],
				'block' => $core_block,
			);
		}

		// Insert in order of decreasing path-depth+index so each insert's
		// target index isn't shifted by a prior insert at the same level.
		usort(
			$insertions,
			static function ( array $a, array $b ): int {
				$path_a    = $a['path'];
				$path_b    = $b['path'];
				$depth_cmp = count( $path_b ) - count( $path_a );
				if ( 0 !== $depth_cmp ) {
					return $depth_cmp;
				}
				$last_a = end( $path_a );
				$last_b = end( $path_b );
				return ( (int) $last_b ) - ( (int) $last_a );
			}
		);
		foreach ( $insertions as $insertion ) {
			$post_blocks = self::insert_block_at_path( $post_blocks, $insertion['path'], $insertion['block'] );
		}

		// Pass 3: unmatched post records (`removed_blocks`). Auto-resolved
		// as Keep yours — no change. Detect structural wrappers solely so
		// we can flag `structural_skipped` honestly.
		foreach ( $post_records as $i => $rec ) {
			if ( isset( $matched_post_set[ $i ] ) ) {
				continue;
			}
			if ( self::is_structural_block( $rec['name'] ) ) {
				$structural_skipped = true;
			}
		}

		// Final pass: explicit deprecated-namespace migration. Whenever a
		// `wp:woo/email-content` block is found in the merged tree, rewrite
		// it to the canonical `wp:woocommerce/email-content` form, including
		// the `wp-block-{old}` CSS class on the inner div so the comment and
		// class stay consistent. The block's `attrs` and inner content are
		// preserved — only the namespace label changes. This is unconditional
		// (independent of `choices`) because `woo/email-content` is a known
		// alias of the canonical core block, not a customisation worth
		// preserving.
		$aliases_migrated = array();
		$post_blocks      = self::migrate_woo_email_content_namespace( $post_blocks, $aliases_migrated );

		return array(
			// $post_blocks originates from parse_blocks() and our mutations only
			// rewrite well-typed fields; serialize_blocks accepts the same shape.
			// PHPStan can't follow the mutation chain, so the explicit ignore.
			// @phpstan-ignore-next-line argument.type
			'content'            => serialize_blocks( $post_blocks ),
			'structural_skipped' => $structural_skipped,
			'aliases_migrated'   => array_values( array_unique( $aliases_migrated ) ),
		);
	}

	/**
	 * Walk the merged tree and rewrite every `wp:woo/email-content` block to
	 * the canonical `wp:woocommerce/email-content` form. Touches the
	 * `blockName` and the `wp-block-woo-email-content` CSS class in the
	 * block's `innerHTML` and each `innerContent` segment. The block's
	 * `attrs` and inner content are otherwise preserved.
	 *
	 * Targeted to a single known alias by design — this is not a general
	 * alias-migration framework. Add new entries here only when a real
	 * deprecated→canonical rename ships and we want it auto-migrated on
	 * apply.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks   Mutable block tree.
	 * @param string[]                                $migrated Names of aliases that were rewritten (out param, appended to).
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function migrate_woo_email_content_namespace( array $blocks, array &$migrated ): array {
		$out = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				$out[] = $block;
				continue;
			}

			if ( 'woo/email-content' === ( $block['blockName'] ?? null ) ) {
				$block['blockName'] = 'woocommerce/email-content';

				if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
					$block['innerHTML'] = str_replace(
						'wp-block-woo-email-content',
						'wp-block-woocommerce-email-content',
						$block['innerHTML']
					);
				}

				if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
					foreach ( $block['innerContent'] as $i => $segment ) {
						if ( is_string( $segment ) ) {
							$block['innerContent'][ $i ] = str_replace(
								'wp-block-woo-email-content',
								'wp-block-woocommerce-email-content',
								$segment
							);
						}
					}
				}

				$migrated[] = 'woo/email-content';
			}//end if

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::migrate_woo_email_content_namespace( $block['innerBlocks'], $migrated );
			}

			$out[] = $block;
		}//end foreach
		return $out;
	}

	/**
	 * Replace the block at the given path with another block's content: the
	 * source block's `innerHTML`, `innerContent` and `innerBlocks`, plus its
	 * `attrs` when `$copy_attrs` is set.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks       Mutable block tree.
	 * @param array<int|string>                       $path         Index path through `parse_blocks` output.
	 * @param array<string, mixed>                    $source_block The block whose content to copy in.
	 * @param bool                                    $copy_attrs   Whether to take the source block's `attrs` as well.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function replace_block_content_at_path( array $blocks, array $path, array $source_block, bool $copy_attrs = false ): array {
		if ( empty( $path ) ) {
			return $blocks;
		}
		return self::replace_recursive( $blocks, array_values( $path ), 0, $source_block, $copy_attrs );
	}

	/**
	 * Replace only the `attrs` of the block at the given path, leaving its
	 * content and children untouched. Used for a block that holds other blocks,
	 * where taking core's content would discard what the merchant put inside.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks       Mutable block tree.
	 * @param array<int|string>                       $path         Index path through `parse_blocks` output.
	 * @param array<string, mixed>                    $source_block The block whose `attrs` to copy in.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function replace_block_attrs_at_path( array $blocks, array $path, array $source_block ): array {
		if ( empty( $path ) ) {
			return $blocks;
		}
		return self::replace_attrs_recursive( $blocks, array_values( $path ), 0, $source_block['attrs'] ?? array() );
	}

	/**
	 * Recursive worker for {@see self::replace_block_attrs_at_path()}.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Current level of the tree.
	 * @param array<int|string>                       $path   Path indices.
	 * @param int                                     $depth  Current depth.
	 * @param array<string, mixed>                    $attrs  Attributes to write.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function replace_attrs_recursive( array $blocks, array $path, int $depth, array $attrs ): array {
		$idx = (int) $path[ $depth ];
		if ( ! isset( $blocks[ $idx ] ) ) {
			return $blocks;
		}

		if ( count( $path ) - 1 === $depth ) {
			$blocks[ $idx ]['attrs'] = $attrs;
			return $blocks;
		}

		$inner                         = $blocks[ $idx ]['innerBlocks'] ?? array();
		$blocks[ $idx ]['innerBlocks'] = self::replace_attrs_recursive( is_array( $inner ) ? $inner : array(), $path, $depth + 1, $attrs );
		return $blocks;
	}

	/**
	 * Recursive worker for {@see self::replace_block_content_at_path()}.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks       Current level of the tree.
	 * @param array<int|string>                       $path         Path indices.
	 * @param int                                     $depth        Current depth.
	 * @param array<string, mixed>                    $source_block Source block to copy content from.
	 * @param bool                                    $copy_attrs   Whether to take the source block's `attrs` as well.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function replace_recursive( array $blocks, array $path, int $depth, array $source_block, bool $copy_attrs = false ): array {
		$idx = (int) $path[ $depth ];
		if ( ! isset( $blocks[ $idx ] ) ) {
			return $blocks;
		}

		if ( count( $path ) - 1 === $depth ) {
			// Callers keep blocks holding other blocks away from here: this
			// replaces `innerBlocks` wholesale, which would discard whatever the
			// merchant had inside.
			$blocks[ $idx ]['innerHTML']    = $source_block['innerHTML'] ?? '';
			$blocks[ $idx ]['innerContent'] = $source_block['innerContent'] ?? array();
			$blocks[ $idx ]['innerBlocks']  = $source_block['innerBlocks'] ?? array();
			if ( $copy_attrs ) {
				$blocks[ $idx ]['attrs'] = $source_block['attrs'] ?? array();
			}
			return $blocks;
		}

		$inner                         = $blocks[ $idx ]['innerBlocks'] ?? array();
		$blocks[ $idx ]['innerBlocks'] = self::replace_recursive( is_array( $inner ) ? $inner : array(), $path, $depth + 1, $source_block, $copy_attrs );
		return $blocks;
	}

	/**
	 * Insert a block at the equivalent position in the merged tree using
	 * core's path as a guide. Best-effort: if a parent in the path doesn't
	 * exist in the post tree, append at the closest level.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks    Mutable block tree.
	 * @param array<int|string>                       $path      Core-side index path of the block to insert.
	 * @param array<string, mixed>                    $new_block The block to insert.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function insert_block_at_path( array $blocks, array $path, array $new_block ): array {
		if ( empty( $path ) ) {
			return $blocks;
		}
		return self::insert_recursive( $blocks, array_values( $path ), 0, $new_block );
	}

	/**
	 * Recursive worker for {@see self::insert_block_at_path()}.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks    Current level.
	 * @param array<int|string>                       $path      Path indices.
	 * @param int                                     $depth     Current depth.
	 * @param array<string, mixed>                    $new_block Block to insert.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function insert_recursive( array $blocks, array $path, int $depth, array $new_block ): array {
		$idx = (int) $path[ $depth ];

		if ( count( $path ) - 1 === $depth ) {
			$insert_at = max( 0, min( $idx, count( $blocks ) ) );
			array_splice( $blocks, $insert_at, 0, array( $new_block ) );
			return $blocks;
		}

		if ( ! isset( $blocks[ $idx ] ) ) {
			// The parent on the core side doesn't exist in the post tree —
			// fall back to appending at this level so the block isn't lost.
			$blocks[] = $new_block;
			return $blocks;
		}

		$inner                         = $blocks[ $idx ]['innerBlocks'] ?? array();
		$blocks[ $idx ]['innerBlocks'] = self::insert_recursive( is_array( $inner ) ? $inner : array(), $path, $depth + 1, $new_block );
		return $blocks;
	}

	/**
	 * Walk a parsed block tree along a path and return the block at that
	 * path, or null if any segment is missing.
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Parsed block tree.
	 * @param array<int|string>                       $path   Index path.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function block_at_path( array $blocks, array $path ): ?array {
		if ( empty( $path ) ) {
			return null;
		}
		$current = $blocks;
		$last    = count( $path ) - 1;
		foreach ( array_values( $path ) as $depth => $idx ) {
			$idx = (int) $idx;
			if ( ! isset( $current[ $idx ] ) || ! is_array( $current[ $idx ] ) ) {
				return null;
			}
			if ( $depth === $last ) {
				return $current[ $idx ];
			}
			$inner   = $current[ $idx ]['innerBlocks'] ?? array();
			$current = is_array( $inner ) ? $inner : array();
		}
		return null;
	}

	/**
	 * Whether the given post-alias-normalized block name is a structural
	 * wrapper (matches the same set RSM-142 uses for nest detection).
	 *
	 * @param string $name Normalized block name (e.g. `core/group`).
	 */
	private static function is_structural_block( string $name ): bool {
		return in_array(
			$name,
			array( 'core/group', 'core/columns', 'core/column', 'core/row' ),
			true
		);
	}

	/**
	 * Whitespace-normalize block markup for semantic comparison. Trims and
	 * collapses runs of whitespace adjacent to tag boundaries — covers the
	 * leading/trailing newlines and the spaces inside `<div> ##WOO_CONTENT## </div>`
	 * that `serialize_blocks()` can't reproduce from a hand-authored PHP template.
	 *
	 * @param string $content Block markup.
	 */
	private static function normalize_for_comparison( string $content ): string {
		$content = trim( $content );
		$content = (string) preg_replace( '/>\s+/', '>', $content );
		$content = (string) preg_replace( '/\s+</', '<', $content );
		return $content;
	}

	/**
	 * Stable string key for a path array, used as the choice-map key.
	 *
	 * @param array<int|string> $path Path indices.
	 */
	private static function path_key( array $path ): string {
		$encoded = wp_json_encode( array_values( $path ) );
		return false === $encoded ? '[]' : $encoded;
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

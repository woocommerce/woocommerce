<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use WC_Tracks;

/**
 * Centralises the Tracks instrumentation for the block-email update flow (RSM-145).
 *
 * Every event funnels through this class so the shared payload stays in one place
 * and we cannot silently drift between callers. The class is stateless: every
 * method is `public static` and reads the per-post values it needs from meta and
 * the sync registry at call time.
 *
 * Events shipped here (server-side):
 *
 * - `block_email_update_available`           — fired from
 *   {@see WCEmailTemplateDivergenceDetector::reclassify()} when a post transitions
 *   into `core_updated_customized` at a newer `template_version_to` than the
 *   merchant last reviewed. Deduplicated per `(post_id, template_version_to)` via
 *   a 30-day transient so repeat sweeps at the same core version do not refire.
 * - `block_email_update_applied` (`applied_from: 'auto'`) — fired
 *   from {@see WCEmailTemplateAutoApplier::apply_to_post()} on a successful write.
 * - `block_email_update_applied` (`applied_from: 'selective_rest'`) —
 *   fired from {@see WCEmailTemplateSelectiveApplier::apply_selectively()} on a
 *   successful write.
 * - `block_email_sync_backfill_completed` — fired exactly once per
 *   site, from the listener on
 *   {@see WCEmailTemplateSyncBackfill::BACKFILL_COMPLETE_ACTION}.
 *
 * The JS-side `_viewed`, `_applied` (`applied_from: 'editor_banner'`), and
 * `_dismissed` events are fired from RSM-141's banner code and the RSM-140
 * list cell directly via `recordEvent` from `@woocommerce/tracks`. The shared
 * payload shape is mirrored in `tracks/build-shared-payload.ts`.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 10.9.0
 */
class WCEmailTemplateSyncTracker {
	/**
	 * Tracks event name fired when a sync-eligible post first becomes available
	 * for review at a new core template version.
	 *
	 * @var string
	 */
	public const EVENT_UPDATE_AVAILABLE = 'block_email_update_available';

	/**
	 * Tracks event name fired on every successful template apply.
	 *
	 * @var string
	 */
	public const EVENT_UPDATE_APPLIED = 'block_email_update_applied';

	/**
	 * Tracks event name fired once when the RSM-149 sync-meta backfill finalises.
	 *
	 * @var string
	 */
	public const EVENT_BACKFILL_COMPLETED = 'block_email_sync_backfill_completed';

	/**
	 * Persistent guard option recording that `_backfill_completed` has already
	 * been recorded for this site. Once set, repeat invocations of
	 * {@see self::on_backfill_complete()} short-circuit — protects against
	 * manual re-runs of {@see WCEmailTemplateSyncBackfill::run()} (e.g. via
	 * wp-cli or tests) double-counting the migration in analytics.
	 *
	 * @var string
	 */
	public const BACKFILL_COMPLETED_TRACKED_OPTION = 'wc_email_sync_backfill_completed_tracked';

	/**
	 * Prefix for the per-`(post_id, template_version_to)` dedup transients used by
	 * `_update_available`. The composite suffix is md5-hashed so the resulting
	 * option name stays comfortably under WordPress's 191-char limit even for
	 * long locale-aware version strings.
	 *
	 * @var string
	 */
	private const AVAILABLE_DEDUP_TRANSIENT_PREFIX = 'wc_email_update_available_fired_';

	/**
	 * `applied_from` value identifying the {@see WCEmailTemplateAutoApplier} call site.
	 *
	 * @var string
	 */
	public const APPLIED_FROM_AUTO = 'auto';

	/**
	 * `applied_from` value identifying the {@see WCEmailTemplateSelectiveApplier} call site.
	 *
	 * @var string
	 */
	public const APPLIED_FROM_SELECTIVE_REST = 'selective_rest';

	/**
	 * Optional event-recorder override. Defaults to
	 * {@see WC_Tracks::record_event()} when null. Tests can inject a spy here
	 * because `WC_Tracks::record_event()` short-circuits early under the
	 * PHPUnit `wptests_capabilities` user (see `class-wc-tracks.php:130-132`).
	 *
	 * @var callable|null
	 */
	private static $event_recorder = null;

	/**
	 * Override the event recorder. Intended for tests only.
	 *
	 * @internal
	 *
	 * @param callable|null $recorder Receives `(string $event_name, array $payload)`. Pass null to restore default.
	 * @return void
	 */
	public static function set_event_recorder( ?callable $recorder ): void {
		self::$event_recorder = $recorder;
	}

	/**
	 * Fire one Tracks event, routed through the injected recorder when present.
	 *
	 * Wrapped in a try/catch so a throw from the Tracks pipeline (a third-party
	 * `woocommerce_tracks_event_properties` filter that mishandles the payload,
	 * a misconfigured Tracks client, etc.) cannot bubble up into the caller's
	 * code path. Every consumer of this method is a fire-and-forget telemetry
	 * surface — a logging failure must never turn a successful apply or
	 * reclassify into an error response. Silent-swallow is intentional: a
	 * failed Tracks event is not actionable for the merchant or the operator,
	 * and logging the failure per event would generate noise out of proportion
	 * to its diagnostic value.
	 *
	 * @param string              $event_name The event name (without the `wcadmin_` prefix).
	 * @param array<string,mixed> $payload    Event properties.
	 * @return void
	 */
	private static function record( string $event_name, array $payload ): void {
		try {
			if ( null !== self::$event_recorder ) {
				( self::$event_recorder )( $event_name, $payload );
				return;
			}
			WC_Tracks::record_event( $event_name, $payload );
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	/**
	 * Record `_update_available` for a post that just transitioned into
	 * `core_updated_customized` at a newer `template_version_to` than was last
	 * stamped on the post.
	 *
	 * Callers must already have confirmed that the new status is
	 * `core_updated_customized` and that the version-meta advance has happened
	 * (or is about to). This method only owns the suppress-during-backfill check
	 * and the per-`(post_id, template_version_to)` dedup transient.
	 *
	 * @param int $post_id The `woo_email` post that just became eligible.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function record_update_available( int $post_id ): void {
		if ( self::should_suppress() ) {
			return;
		}

		$payload = self::build_base_payload( $post_id );
		if ( null === $payload ) {
			return;
		}

		$version_to = (string) $payload['template_version_to'];
		if ( '' === $version_to ) {
			return;
		}

		$transient_key = self::available_dedup_transient_key( $post_id, $version_to );
		if ( false !== get_transient( $transient_key ) ) {
			return;
		}

		self::record( self::EVENT_UPDATE_AVAILABLE, $payload );

		// Set the dedup transient after the record() call. record() swallows
		// throws from the Tracks pipeline in production, so in steady state
		// either ordering would dedup identically. The post-record ordering is
		// intentional for the testing path: when set_event_recorder() injects a
		// throwing spy, the transient stays unwritten and a retry remains
		// possible without manually clearing it. 30-day TTL outlasts the gap
		// between core releases without leaving stale dedup keys forever.
		set_transient( $transient_key, 1, MONTH_IN_SECONDS );
	}

	/**
	 * Record `_update_applied` for the auto-applier success path.
	 *
	 * The auto-applier only runs against posts classified as
	 * `core_updated_uncustomized`, so `had_customizations` is statically `false`
	 * and `auto_resolved` is statically `true`. Documented at the call site
	 * rather than computed.
	 *
	 * @param int $post_id The `woo_email` post that was just rewritten with canonical core content.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function record_auto_applied( int $post_id ): void {
		if ( self::should_suppress() ) {
			return;
		}

		$payload = self::build_base_payload( $post_id );
		if ( null === $payload ) {
			return;
		}

		$payload['applied_from']       = self::APPLIED_FROM_AUTO;
		$payload['auto_resolved']      = true;
		$payload['had_customizations'] = false;

		self::record( self::EVENT_UPDATE_APPLIED, $payload );
	}

	/**
	 * Record `_update_applied` for the selective-applier success path (the REST
	 * `/apply` endpoint behind RSM-143's Review drawer).
	 *
	 * The selective applier only runs against `core_updated_customized` posts,
	 * so `had_customizations` is statically `true` and `auto_resolved` is
	 * statically `false`.
	 *
	 * @param int $post_id The `woo_email` post that was just rewritten via selective merge.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function record_selective_applied( int $post_id ): void {
		if ( self::should_suppress() ) {
			return;
		}

		$payload = self::build_base_payload( $post_id );
		if ( null === $payload ) {
			return;
		}

		$payload['applied_from']       = self::APPLIED_FROM_SELECTIVE_REST;
		$payload['auto_resolved']      = false;
		$payload['had_customizations'] = true;

		self::record( self::EVENT_UPDATE_APPLIED, $payload );
	}

	/**
	 * Listener body for `woocommerce_email_template_sync_backfill_complete`.
	 *
	 * Fires `_backfill_completed` once per site. Payload is intentionally
	 * minimal (per-case counters and elapsed_ms would require touching the
	 * 10.8-frozen {@see WCEmailTemplateSyncBackfill} class):
	 *
	 * - `posts_backfilled`: count of `woo_email` posts whose
	 *   {@see WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY} flag is true.
	 * - `wc_version`: current WooCommerce version string.
	 *
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function on_backfill_complete(): void {
		// Persistent one-shot guard: `WCEmailTemplateSyncBackfill::run()`
		// fires `finalize()` on every invocation, and finalize() always
		// fires `BACKFILL_COMPLETE_ACTION`. In production `run()` only
		// runs once (10.8 db-update callback), but manual re-runs via
		// wp-cli or tests can re-fire the action — this gate ensures the
		// Tracks event still lands at most once per site.
		if ( 'yes' === get_option( self::BACKFILL_COMPLETED_TRACKED_OPTION, 'no' ) ) {
			return;
		}

		$posts_backfilled = self::count_backfilled_posts();

		self::record(
			self::EVENT_BACKFILL_COMPLETED,
			array(
				'posts_backfilled' => $posts_backfilled,
				'wc_version'       => function_exists( 'WC' ) && WC() ? (string) WC()->version : '',
			)
		);

		update_option( self::BACKFILL_COMPLETED_TRACKED_OPTION, 'yes', false );
	}

	/**
	 * Assemble the shared base payload for one post.
	 *
	 * Returns `null` when the post is not a sync-eligible `woo_email` or when
	 * any inner call throws — every `record_*` caller treats `null` as "skip
	 * this event," keeping the Tracks surface fire-and-forget.
	 *
	 * @param int $post_id The `woo_email` post ID.
	 * @return array<string,mixed>|null
	 */
	private static function build_base_payload( int $post_id ): ?array {
		try {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return null;
			}

			$posts_manager = WCTransactionalEmailPostsManager::get_instance();
			$email_id      = (string) $posts_manager->get_email_type_from_post_id( $post_id );
			if ( '' === $email_id ) {
				return null;
			}

			$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id );
			if ( null === $sync_config ) {
				return null;
			}

			$emails = $posts_manager->get_emails_by_id();
			$email  = $emails[ $email_id ] ?? null;

			$source_hash_to = '';
			if ( $email instanceof \WC_Email ) {
				try {
					$source_hash_to = sha1( WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ) );
				} catch ( \Throwable $e ) {
					// Canonical compute can throw on misconfigured templates. The event
					// is still useful with an empty `source_hash_to` — analytics will
					// see "site with broken canonical render" as a distinct cohort.
					$source_hash_to = '';
				}
			}

			$version_from   = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true );
			$status         = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true );
			$was_backfilled = (bool) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY, true );

			return array(
				'email_id'              => $email_id,
				'template_version_from' => $version_from,
				'template_version_to'   => (string) ( $sync_config['version'] ?? '' ),
				'source_hash_to'        => $source_hash_to,
				'classification'        => $status,
				'was_backfilled'        => $was_backfilled,
			);
		} catch ( \Throwable $e ) {
			unset( $e );
			return null;
		}
	}

	/**
	 * Whether per-post telemetry should be suppressed in the current request.
	 *
	 * `true` while the one-time RSM-149 backfill is rewriting post content so the
	 * `_available` / `_applied` events do not storm during the migration. The
	 * single `_backfill_completed` event covers that surface instead.
	 *
	 * @return bool
	 */
	private static function should_suppress(): bool {
		return WCEmailTemplateSyncBackfill::is_backfilling();
	}

	/**
	 * Build the transient option key used by the `_update_available` dedup gate.
	 *
	 * @param int    $post_id    The post ID.
	 * @param string $version_to The current registry version for the email.
	 * @return string
	 */
	private static function available_dedup_transient_key( int $post_id, string $version_to ): string {
		return self::AVAILABLE_DEDUP_TRANSIENT_PREFIX . $post_id . '_' . md5( $version_to );
	}

	/**
	 * Count `woo_email` posts flagged as backfilled by {@see WCEmailTemplateSyncBackfill}.
	 *
	 * @return int
	 */
	private static function count_backfilled_posts(): int {
		$ids = get_posts(
			array(
				'post_type'      => 'woo_email',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array(  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded set; sync-registered emails only.
					array(
						'key'     => WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY,
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		return is_array( $ids ) ? count( $ids ) : 0;
	}
}

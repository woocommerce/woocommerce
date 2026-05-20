/**
 * Shared constants for the update-propagation E2E suite.
 *
 * Mirror the PHP-side meta keys and status values from
 * Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector
 * and the Tracks event names from RSM-145 (PR #64759).
 *
 * Event-name conventions (post-#64759 rename):
 *
 * Client-side events (fired via @woocommerce/tracks recordEvent, captured by
 * the window.wcTracks.recordEvent spy as-is — no prefix added by the package):
 *   block_email_update_viewed
 *   block_email_update_applied
 *   block_email_update_dismissed
 *
 * Server-side events (fired via WC_Tracks::record_event(), captured by the
 * Tracks_Recorder woocommerce_tracks_event_properties filter which receives the
 * name already prefixed with "wcadmin_" by WC_Tracks::PREFIX):
 *   wcadmin_block_email_update_available
 *   wcadmin_block_email_update_applied
 *   wcadmin_block_email_sync_backfill_completed
 */

export const STATUS = {
	IN_SYNC: 'in_sync',
	CORE_UPDATED_UNCUSTOMIZED: 'core_updated_uncustomized',
	CORE_UPDATED_CUSTOMIZED: 'core_updated_customized',
} as const;

export type Status = ( typeof STATUS )[ keyof typeof STATUS ];

export const META_KEYS = {
	STATUS: '_wc_email_template_status',
	SOURCE_HASH: '_wc_email_template_source_hash',
	SOURCE_VERSION: '_wc_email_template_version',
	LAST_SYNCED_AT: '_wc_email_last_synced_at',
	BACKFILLED: '_wc_email_backfilled',
} as const;

export const TRACKS_EVENTS = {
	// Server-side: WC_Tracks::record_event() adds "wcadmin_" prefix before
	// the woocommerce_tracks_event_properties filter fires, so the recorder
	// captures these with the prefix already applied.
	AVAILABLE: 'wcadmin_block_email_update_available',
	BACKFILL_COMPLETED: 'wcadmin_block_email_sync_backfill_completed',
	// Client-side: @woocommerce/tracks recordEvent() passes the name as-is
	// to window.wcTracks.recordEvent; the spy captures it without any prefix.
	VIEWED: 'block_email_update_viewed',
	APPLIED: 'block_email_update_applied',
	DISMISSED: 'block_email_update_dismissed',
} as const;

export const BACKFILL_CASES = {
	A: 'A',
	B: 'B',
	C: 'C',
} as const;

export const TEST_HELPER_API_BASE = 'wc-email-test-helper/v1';

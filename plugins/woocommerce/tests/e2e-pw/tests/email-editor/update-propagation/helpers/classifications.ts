/**
 * Shared constants for the update-propagation E2E suite.
 *
 * Mirror the PHP-side meta keys and status values from
 * Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector
 * and the Tracks event names from RSM-145 (PR #64759).
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
	AVAILABLE: 'woocommerce_block_email_update_available',
	VIEWED: 'woocommerce_block_email_update_viewed',
	APPLIED: 'woocommerce_block_email_update_applied',
	DISMISSED: 'woocommerce_block_email_update_dismissed',
	BACKFILL_COMPLETED: 'woocommerce_block_email_sync_backfill_completed',
} as const;

export const BACKFILL_CASES = {
	A: 'A',
	B: 'B',
	C: 'C',
} as const;

export const TEST_HELPER_API_BASE = '/wp-json/wc-email-test-helper/v1';

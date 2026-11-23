/**
 * Shared constants for the update-propagation E2E suite.
 *
 * Mirror the PHP-side meta keys and status values from
 * Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector.
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

export const BACKFILL_CASES = {
	A: 'A',
	B: 'B',
	C: 'C',
} as const;

export const TEST_HELPER_API_BASE = 'wc-email-test-helper/v1';

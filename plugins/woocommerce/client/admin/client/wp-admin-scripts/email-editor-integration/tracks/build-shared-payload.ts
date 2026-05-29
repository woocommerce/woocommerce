/**
 * Internal dependencies
 */
import type { ChangeSummary } from '../hooks/use-change-summary';

/**
 * Per-surface identifiers for the `applied_from` and `viewed_from` extension
 * keys on the block-email update Tracks events (RSM-145 §3.2). Mirror the PHP
 * constants on `WCEmailTemplateSyncTracker` (`APPLIED_FROM_AUTO`,
 * `APPLIED_FROM_SELECTIVE_REST`) so dashboard queries that segment by surface
 * stay aligned across the server- and client-emitted events.
 */
export const APPLIED_FROM_EDITOR_BANNER = 'editor_banner' as const;
export const APPLIED_FROM_AUTO = 'auto' as const;
export const APPLIED_FROM_SELECTIVE_REST = 'selective_rest' as const;

export const VIEWED_FROM_EDITOR_BANNER = 'editor_banner' as const;
export const VIEWED_FROM_EMAIL_LIST = 'email_list' as const;

export type AppliedFrom =
	| typeof APPLIED_FROM_EDITOR_BANNER
	| typeof APPLIED_FROM_AUTO
	| typeof APPLIED_FROM_SELECTIVE_REST;

export type ViewedFrom =
	| typeof VIEWED_FROM_EDITOR_BANNER
	| typeof VIEWED_FROM_EMAIL_LIST;

/**
 * Shape of the base payload shared by every block-email update Tracks event
 * (RSM-145 §3). The same six keys are produced server-side by
 * `WCEmailTemplateSyncTracker::build_base_payload()`; consumer dashboards
 * should be able to query by any of these regardless of which surface fired
 * the event.
 *
 * Each event adds its own extension fields on top. See the individual call
 * sites for the per-event keys.
 */
export interface SharedTracksPayload {
	email_id: string;
	template_version_from: string;
	template_version_to: string | null;
	source_hash_to: string | null;
	classification: string;
	was_backfilled: boolean;
	// `recordEvent` types its payload as `{ [k: string]: unknown }`; the index
	// signature lets callers spread `SharedTracksPayload` directly.
	[ key: string ]: unknown;
}

export interface SharedPayloadInputs {
	record: {
		slug?: unknown;
		meta?: Record< string, unknown >;
	} | null;
	summary: ChangeSummary | null;
}

/**
 * Build the shared Tracks payload from the current entity record + summary.
 *
 * Returns `null` when there's no record yet (eligibility hasn't fired, so no
 * event should fire either). Falsy `summary` is permitted: in that case the
 * `template_version_to` and `source_hash_to` fields are `null`.
 *
 * Mirrors `WCEmailTemplateSyncTracker::build_base_payload()` on the PHP side
 * so server- and client-emitted events for the same logical transition land
 * on identical keys.
 */
export function buildSharedTracksPayload( {
	record,
	summary,
}: SharedPayloadInputs ): SharedTracksPayload | null {
	const meta = record?.meta;
	if ( ! meta ) {
		return null;
	}
	const slug = typeof record?.slug === 'string' ? record.slug : '';
	const versionFrom =
		typeof meta._wc_email_template_version === 'string'
			? ( meta._wc_email_template_version as string )
			: '';
	const wasBackfilled =
		meta._wc_email_backfilled === true ||
		meta._wc_email_backfilled === '1' ||
		meta._wc_email_backfilled === 1;
	const classification =
		typeof meta._wc_email_template_status === 'string'
			? ( meta._wc_email_template_status as string )
			: '';

	return {
		email_id: slug,
		template_version_from: versionFrom,
		template_version_to: summary?.version_to ?? null,
		source_hash_to: summary?.source_hash_to ?? null,
		classification,
		was_backfilled: wasBackfilled,
	};
}

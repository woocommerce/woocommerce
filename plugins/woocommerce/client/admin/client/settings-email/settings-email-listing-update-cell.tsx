/**
 * <UpdatesCell> — RSM-140
 *
 * Renders one of two visual states inside the email list's "Updates" column:
 *
 *  - core_updated_customized → "Review update" Button (variant="secondary").
 *    Click navigates to the email post's editor with `?wc_email_review_drawer=1`,
 *    a stable param contract consumed by RSM-141 (editor banner) to auto-open
 *    the review drawer.
 *
 *  - any other status (in_sync, core_updated_uncustomized, null/missing meta,
 *    unexpected string) → em-dash placeholder with `aria-label="Up to date"`.
 *
 * The click handler is intentionally minimal and self-contained. RSM-144
 * may layer a no-conflict /apply fast-path on top later.
 */

/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { EmailType } from './settings-email-listing-slotfill';
import { buildEmailEditorReviewUrl } from './build-email-editor-review-url';
import { shouldShowReviewUpdate } from './settings-email-listing-update-state';

interface UpdatesCellProps {
	post: EmailType;
}

/**
 * Storage key for the per-(post_id, version_to) dedup of the list-page
 * `_viewed` event. Stored in `sessionStorage` so a refresh re-fires the
 * event, but re-renders or mount/unmount cycles within the same session
 * do not.
 */
const VIEWED_DEDUP_SESSION_KEY = 'wc_email_update_viewed_list';

/**
 * Returns the parsed `(post_id, version_to)` dedup set from sessionStorage.
 * Tolerates a missing or malformed payload — returns an empty set.
 */
function getViewedDedupSet(): Set< string > {
	try {
		const raw = window.sessionStorage.getItem( VIEWED_DEDUP_SESSION_KEY );
		if ( ! raw ) {
			return new Set();
		}
		const parsed = JSON.parse( raw );
		return Array.isArray( parsed ) ? new Set( parsed ) : new Set();
	} catch {
		return new Set();
	}
}

/**
 * Persists the dedup set back to sessionStorage. Failures are intentionally
 * swallowed — storage quota or privacy-mode browsers shouldn't break the
 * settings page.
 */
function persistViewedDedupSet( set: Set< string > ): void {
	try {
		window.sessionStorage.setItem(
			VIEWED_DEDUP_SESSION_KEY,
			JSON.stringify( Array.from( set ) )
		);
	} catch {
		// no-op
	}
}

export const UpdatesCell = ( { post }: UpdatesCellProps ) => {
	const eligible = shouldShowReviewUpdate( post );

	// Fire `_viewed` once per (post_id, version_to) per browser session.
	// `source_hash_to` is intentionally `null` on the list-side event: the
	// list payload is built from server-rendered slotfill data and doesn't
	// run a `/change-summary` round-trip per row. The corresponding banner
	// `_viewed` (which a merchant triggers by opening the editor) carries
	// the hash for third-party-filter cohort analysis.
	useEffect( () => {
		if ( ! eligible || ! post.post_id || ! post.currentVersion ) {
			return;
		}
		const key = `${ post.post_id }:${ post.currentVersion }`;
		const seen = getViewedDedupSet();
		if ( seen.has( key ) ) {
			return;
		}
		seen.add( key );
		persistViewedDedupSet( seen );

		recordEvent( 'woocommerce_block_email_update_viewed', {
			email_id: post.id,
			template_version_from: post.templateVersion ?? '',
			template_version_to: post.currentVersion,
			source_hash_to: null,
			classification: post.templateStatus ?? '',
			was_backfilled: post.wasBackfilled,
			viewed_from: 'email_list',
		} );
	}, [
		eligible,
		post.id,
		post.post_id,
		post.templateStatus,
		post.templateVersion,
		post.currentVersion,
		post.wasBackfilled,
	] );

	if ( ! eligible ) {
		return <span aria-label={ __( 'Up to date', 'woocommerce' ) }>—</span>;
	}

	const onReviewUpdate = () => {
		// Defensive guard: EmailType.post_id is typed as string and may be
		// empty for third-party emails without a generated woo_email post.
		// The detector should never stamp _wc_email_template_status on such
		// rows, but mirror the existing `edit` row-action pattern in the
		// listview rather than rely on that invariant.
		if ( ! post.post_id ) {
			return;
		}
		window.location.href = getAdminLink(
			buildEmailEditorReviewUrl( parseInt( post.post_id, 10 ) )
		);
	};

	return (
		<Button variant="secondary" onClick={ onReviewUpdate }>
			{ __( 'Review update', 'woocommerce' ) }
		</Button>
	);
};

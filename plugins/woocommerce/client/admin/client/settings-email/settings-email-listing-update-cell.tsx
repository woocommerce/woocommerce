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
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { EmailType } from './settings-email-listing-slotfill';
import { buildEmailEditorReviewUrl } from './build-email-editor-review-url';
import { shouldShowReviewUpdate } from './settings-email-listing-update-state';

export interface UpdatesCellProps {
	post: EmailType;
}

export const UpdatesCell = ( { post }: UpdatesCellProps ) => {
	const eligible = shouldShowReviewUpdate( post );

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

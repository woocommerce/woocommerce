/**
 * <UpdatesCell> — RSM-140
 *
 * Renders one of two visual states inside the email list's "Updates" column:
 *
 *  - core_updated_customized → outlined "Review update" button with a leading
 *    dot indicator. Click navigates to the email editor with
 *    ?wc_email_review_drawer=1, which RSM-141 / PR #64497 consumes to
 *    auto-open the review drawer.
 *
 *  - any other status (in_sync, core_updated_uncustomized, null/missing meta,
 *    unexpected string) → em-dash placeholder with `aria-label="Up to date"`.
 *
 * The click handler is deliberately minimal and self-contained. RSM-144 will
 * replace its body wholesale to add a no-conflict /apply fast-path; do not
 * thread callback props through the listview field config.
 *
 * Visual treatment matches design_handoff_email_update_flow Option B —
 * 1px blueberry border, 26px tall, blueberry text, white bg (resting),
 * tinted blueberry bg on hover. Styles live in `./style.scss` under
 * `.woocommerce-email-listing-update-cell-btn`.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { EmailType } from './settings-email-listing-slotfill';
import { buildEmailEditorReviewUrl } from './build-email-editor-review-url';

interface UpdatesCellProps {
	post: EmailType;
}

export const UpdatesCell = ( { post }: UpdatesCellProps ) => {
	if ( post.templateStatus !== 'core_updated_customized' ) {
		return <span aria-label={ __( 'Up to date', 'woocommerce' ) }>—</span>;
	}

	const onReviewUpdate = () => {
		window.location.href = getAdminLink(
			buildEmailEditorReviewUrl( parseInt( post.post_id, 10 ) )
		);
	};

	return (
		<button
			type="button"
			className="woocommerce-email-listing-update-cell-btn"
			onClick={ onReviewUpdate }
		>
			<span
				className="woocommerce-email-listing-update-cell-btn__dot"
				aria-hidden="true"
			/>
			{ __( 'Review update', 'woocommerce' ) }
		</button>
	);
};

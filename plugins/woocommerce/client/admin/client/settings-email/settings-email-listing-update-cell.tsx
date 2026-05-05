/**
 * <UpdatesCell> — RSM-140
 *
 * Renders one of two visual states inside the email list's "Updates" column:
 *
 *  - core_updated_customized → "Review update" Button (variant="secondary").
 *    Click navigates to the email post's editor; the editor will detect
 *    the post's templateStatus on mount (RSM-141) and surface its own banner.
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

interface UpdatesCellProps {
	post: EmailType;
}

export const UpdatesCell = ( { post }: UpdatesCellProps ) => {
	if ( post.templateStatus !== 'core_updated_customized' ) {
		return <span aria-label={ __( 'Up to date', 'woocommerce' ) }>—</span>;
	}

	const onReviewUpdate = () => {
		window.location.href = getAdminLink(
			`post.php?post=${ encodeURIComponent( post.post_id ) }&action=edit`
		);
	};

	return (
		<Button variant="secondary" onClick={ onReviewUpdate }>
			{ __( 'Review update', 'woocommerce' ) }
		</Button>
	);
};

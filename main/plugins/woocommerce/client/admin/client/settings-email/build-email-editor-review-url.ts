/**
 * Build the relative editor URL fragment that opens an email post's editor with
 * the review drawer auto-opened.
 *
 * Pair with `getAdminLink` from `@woocommerce/settings` to produce a full admin URL.
 *
 *   import { getAdminLink } from '@woocommerce/settings';
 *   import { buildEmailEditorReviewUrl } from './build-email-editor-review-url';
 *
 *   window.location.href = getAdminLink( buildEmailEditorReviewUrl( postId ) );
 *
 * Public param contract — see RSM-140 spec § 5.4 (Linear: RSM-140, RSM-141; PR #64497).
 * The consumer side that reads the param and dispatches `openReviewDrawer()` is
 * owned by RSM-141 (editor banner) — out of scope for this PR.
 *
 * @param postId Positive integer post id of the woo_email post to open.
 * @throws Error when postId is not a positive integer.
 */
export const REVIEW_DRAWER_PARAM = 'wc_email_review_drawer';

export function buildEmailEditorReviewUrl( postId: number ): string {
	if ( ! Number.isInteger( postId ) || postId <= 0 ) {
		throw new Error(
			`buildEmailEditorReviewUrl: postId must be a positive integer (got ${ String(
				postId
			) }).`
		);
	}

	const params = new URLSearchParams( {
		post: String( postId ),
		action: 'edit',
		[ REVIEW_DRAWER_PARAM ]: '1',
	} );

	return `post.php?${ params.toString() }`;
}

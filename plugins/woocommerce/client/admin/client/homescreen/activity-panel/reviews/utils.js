/**
 * External dependencies
 */
import { reviewsStore } from '@woocommerce/data';

export const REVIEW_PAGE_LIMIT = 5;

export const unapprovedReviewsQuery = {
	page: 1,
	per_page: 1,
	status: 'hold',
	_embed: 1,
	_fields: [ 'id' ],
};
export function getUnapprovedReviews( select ) {
	const { getReviewsTotalCount, getReviewsError, isResolving } =
		select( reviewsStore );

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const totalReviews = getReviewsTotalCount( unapprovedReviewsQuery );
	const isError = Boolean( getReviewsError( unapprovedReviewsQuery ) );
	const isRequesting = isResolving( 'getReviewsTotalCount', [
		unapprovedReviewsQuery,
	] );

	if ( isError || ( isRequesting && totalReviews === undefined ) ) {
		return null;
	}

	return totalReviews;
}

/** @format */

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES, DEFAULT_REVIEW_STATUSES } from 'wc-api/constants';

export function getUnreadNotes( select ) {
	const { getCurrentUserData, getNotes, getNotesError, isGetNotesRequesting } = select( 'wc-api' );
	const userData = getCurrentUserData();
	const notesQuery = {
		page: 1,
		per_page: 1,
		type: 'info,warning',
		orderby: 'date',
		order: 'desc',
	};

	const latestNote = getNotes( notesQuery );
	const isError = Boolean( getNotesError( notesQuery ) );
	const isRequesting = isGetNotesRequesting( notesQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	return (
		latestNote[ 0 ] &&
		new Date( latestNote[ 0 ].date_created_gmt + 'Z' ).getTime() >
			userData.activity_panel_inbox_last_read
	);
}

export function getUnreadOrders( select ) {
	const { getReportItems, getReportItemsError, isReportItemsRequesting } = select( 'wc-api' );
	const orderStatuses =
		wcSettings.wcAdminSettings.woocommerce_actionable_order_statuses || DEFAULT_ACTIONABLE_STATUSES;

	if ( ! orderStatuses.length ) {
		return false;
	}

	const ordersQuery = {
		page: 1,
		per_page: 0,
		status_is: orderStatuses,
	};

	const totalOrders = getReportItems( 'orders', ordersQuery ).totalResults;
	const isError = Boolean( getReportItemsError( 'orders', ordersQuery ) );
	const isRequesting = isReportItemsRequesting( 'orders', ordersQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalOrders > 0;
}

export function getUnreadReviews( select ) {
	const {
		getCurrentUserData,
		getReviews,
		getReviewsTotalCount,
		getReviewsError,
		isGetReviewsRequesting,
	} = select( 'wc-api' );
	const userData = getCurrentUserData();
	let numberOfReviews = null;
	let hasUnreadReviews = false;

	if ( 'yes' === wcSettings.reviewsEnabled ) {
		const reviewsQuery = {
			order: 'desc',
			orderby: 'date_gmt',
			page: 1,
			per_page: 1,
			status: DEFAULT_REVIEW_STATUSES,
		};
		const reviews = getReviews( reviewsQuery );
		const totalReviews = getReviewsTotalCount( reviewsQuery );
		const isReviewsError = Boolean( getReviewsError( reviewsQuery ) );
		const isReviewsRequesting = isGetReviewsRequesting( reviewsQuery );

		if ( ! isReviewsError && ! isReviewsRequesting ) {
			numberOfReviews = totalReviews;
			hasUnreadReviews = Boolean(
				reviews.length &&
					reviews[ 0 ].date_created_gmt &&
					new Date( reviews[ 0 ].date_created_gmt + 'Z' ).getTime() >
						userData.activity_panel_reviews_last_read
			);
		}

		if ( ! hasUnreadReviews && '1' === wcSettings.commentModeration ) {
			const actionableReviewsQuery = {
				page: 1,
				// @todo we are not using this review, so when the endpoint supports it,
				// it could be replaced with `per_page: 0`
				per_page: 1,
				status: 'hold',
			};
			const totalActionableReviews = getReviewsTotalCount( actionableReviewsQuery );
			const isActionableReviewsError = Boolean( getReviewsError( actionableReviewsQuery ) );
			const isActionableReviewsRequesting = isGetReviewsRequesting( actionableReviewsQuery );

			if ( ! isActionableReviewsError && ! isActionableReviewsRequesting ) {
				hasUnreadReviews = totalActionableReviews > 0;
			}
		}
	}

	return { numberOfReviews, hasUnreadReviews };
}

export function getUnreadStock( select ) {
	const { getItems, getItemsError, getItemsTotalCount, isGetItemsRequesting } = select( 'wc-api' );
	const productsQuery = {
		page: 1,
		per_page: 1,
		low_in_stock: true,
		status: 'publish',
	};
	getItems( 'products', productsQuery );
	const lowInStockCount = getItemsTotalCount( 'products', productsQuery );
	const isError = Boolean( getItemsError( 'products', productsQuery ) );
	const isRequesting = isGetItemsRequesting( 'products', productsQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	return lowInStockCount > 0;
}

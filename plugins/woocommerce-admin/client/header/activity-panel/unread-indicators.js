/** @format */

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from 'wc-api/constants';
import { getSetting } from '@woocommerce/wc-admin-settings';

export function getUnreadNotes( select ) {
	const { getCurrentUserData, getNotes, getNotesError, isGetNotesRequesting } = select( 'wc-api' );
	const userData = getCurrentUserData();
	if ( ! userData ) {
		return null;
	}
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
	const { getItems, getItemsTotalCount, getItemsError, isGetItemsRequesting } = select( 'wc-api' );
	const {
		woocommerce_actionable_order_statuses: orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
	} = getSetting( 'wcAdminSettings', {} );

	if ( ! orderStatuses.length ) {
		return false;
	}

	const ordersQuery = {
		page: 1,
		per_page: 1, // Core endpoint requires per_page > 0.
		status: orderStatuses,
		_fields: [ 'id' ],
	};

	getItems( 'orders', ordersQuery );
	const totalOrders = getItemsTotalCount( 'orders', ordersQuery );
	const isError = Boolean( getItemsError( 'orders', ordersQuery ) );
	const isRequesting = isGetItemsRequesting( 'orders', ordersQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalOrders > 0;
}

export function getUnapprovedReviews( select ) {
	const { getReviewsTotalCount, getReviewsError, isGetReviewsRequesting } = select( 'wc-api' );
	const reviewsEnabled = getSetting( 'reviewsEnabled' );
	if ( 'yes' === reviewsEnabled ) {
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
			return totalActionableReviews > 0;
		}
	}

	return false;
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

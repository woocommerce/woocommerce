/**
 * WooCommerce dependencies
 */
import {
	SETTINGS_STORE_NAME,
	USER_STORE_NAME,
	REVIEWS_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from 'analytics/settings/config';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import { getUnreadNotesCount } from './panels/inbox/utils';

export function getUnreadNotes( select ) {
	const { getNotes, getNotesError, isGetNotesRequesting } = select(
		'wc-api'
	);

	const { getCurrentUser } = select( USER_STORE_NAME );
	const userData = getCurrentUser();
	const lastRead = parseInt(
		userData &&
			userData.woocommerce_meta &&
			userData.woocommerce_meta.activity_panel_inbox_last_read,
		10
	);

	if ( ! lastRead ) {
		return null;
	}

	// @todo This method would be more performant if we ask only for 1 item per page with status "unactioned".
	// This change should be applied after having pagination implemented.
	const notesQuery = {
		page: 1,
		per_page: QUERY_DEFAULTS.pageSize,
		status: 'unactioned',
		type: QUERY_DEFAULTS.noteTypes,
		orderby: 'date',
		order: 'desc',
	};

	// Disable eslint rule requiring `latestNotes` to be defined below because the next two statements
	// depend on `getNotes` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const latestNotes = getNotes( notesQuery );
	const isError = Boolean( getNotesError( notesQuery ) );
	const isRequesting = isGetNotesRequesting( notesQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	const unreadNotesCount = getUnreadNotesCount( latestNotes, lastRead );

	return unreadNotesCount > 0;
}

export function getUnreadOrders( select ) {
	const {
		getItems,
		getItemsTotalCount,
		getItemsError,
		isGetItemsRequesting,
	} = select( 'wc-api' );
	const { getSetting: getMutableSetting } = select( SETTINGS_STORE_NAME );
	const {
		woocommerce_actionable_order_statuses: orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
	} = getMutableSetting( 'wc_admin', 'wcAdminSettings', {} );

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

	// Disable eslint rule requiring `latestNote` to be defined below because the next two statements
	// depend on `getItemsTotalCount` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const totalOrders = getItemsTotalCount( 'orders', ordersQuery );
	const isError = Boolean( getItemsError( 'orders', ordersQuery ) );
	const isRequesting = isGetItemsRequesting( 'orders', ordersQuery );

	if ( isError || isRequesting ) {
		return null;
	}

	return totalOrders > 0;
}

export function getUnapprovedReviews( select ) {
	const { getReviewsTotalCount, getReviewsError, isResolving } = select(
		REVIEWS_STORE_NAME
	);
	const reviewsEnabled = getSetting( 'reviewsEnabled' );
	if ( reviewsEnabled === 'yes' ) {
		const actionableReviewsQuery = {
			page: 1,
			// @todo we are not using this review, so when the endpoint supports it,
			// it could be replaced with `per_page: 0`
			per_page: 1,
			status: 'hold',
		};
		const totalActionableReviews = getReviewsTotalCount(
			actionableReviewsQuery
		);

		const isActionableReviewsError = Boolean(
			getReviewsError( actionableReviewsQuery )
		);

		const isActionableReviewsRequesting = isResolving(
			'getReviewsTotalCount',
			[ actionableReviewsQuery ]
		);

		if ( ! isActionableReviewsError && ! isActionableReviewsRequesting ) {
			return totalActionableReviews > 0;
		}
	}

	return false;
}

export function getUnreadStock() {
	return getSetting( 'hasLowStock', false );
}

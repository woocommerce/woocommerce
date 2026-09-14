/**
 * External dependencies
 */
import { createReduxStore, register, select } from '@wordpress/data';
import type {
	ReduxStoreConfig,
	StoreDescriptor as GenericStoreDescriptor,
} from '@wordpress/data/build-types/types';

/**
 * Integration-layer UI state for the WooCommerce email editor.
 *
 * Internal — consumers in this directory open/close the review drawer
 * (and any future integration-level UI state) via this store rather
 * than passing props across siblings, since the trigger and the drawer
 * live in different React subtrees mounted by `registerPlugin`.
 *
 * RSM-141 extends this store with two additional pieces of session-only
 * state for the "update available" banner:
 *   - `dismissedPostIds`: which posts the user has dismissed the banner
 *     for during this editor session, so we don't re-show it after they
 *     explicitly close it.
 *   - `viewedPostVersionPairs`: which (postId, versionTo) pairs have
 *     already fired the "viewed" Tracks event, so re-renders don't
 *     double-count impressions.
 *
 * @internal
 */
export const STORE_NAME = 'woocommerce/email-editor-integration';

interface State {
	isReviewDrawerOpen: boolean;
	dismissedPostIds: Set< number >;
	viewedPostVersionPairs: Set< string >;
}

const initialState: State = {
	isReviewDrawerOpen: false,
	dismissedPostIds: new Set< number >(),
	viewedPostVersionPairs: new Set< string >(),
};

type Action =
	| { type: 'SET_REVIEW_DRAWER_OPEN'; open: boolean }
	| { type: 'DISMISS_UPDATE_BANNER'; postId: number }
	| { type: 'CLEAR_DISMISSED_FOR_POST'; postId: number }
	| { type: 'MARK_UPDATE_BANNER_VIEWED'; postId: number; versionTo: string };

const reducer = ( state: State = initialState, action: Action ): State => {
	switch ( action.type ) {
		case 'SET_REVIEW_DRAWER_OPEN':
			return { ...state, isReviewDrawerOpen: action.open };
		case 'DISMISS_UPDATE_BANNER': {
			const nextDismissed = new Set( state.dismissedPostIds );
			nextDismissed.add( action.postId );
			return { ...state, dismissedPostIds: nextDismissed };
		}
		case 'CLEAR_DISMISSED_FOR_POST': {
			if ( ! state.dismissedPostIds.has( action.postId ) ) {
				return state;
			}
			const nextDismissed = new Set( state.dismissedPostIds );
			nextDismissed.delete( action.postId );
			return { ...state, dismissedPostIds: nextDismissed };
		}
		case 'MARK_UPDATE_BANNER_VIEWED': {
			const key = `${ action.postId }:${ action.versionTo }`;
			if ( state.viewedPostVersionPairs.has( key ) ) {
				return state;
			}
			const nextViewed = new Set( state.viewedPostVersionPairs );
			nextViewed.add( key );
			return { ...state, viewedPostVersionPairs: nextViewed };
		}
		default:
			return state;
	}
};

const actions = {
	setReviewDrawerOpen: ( open: boolean ) =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open } ) as const,
	openReviewDrawer: () =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open: true } ) as const,
	closeReviewDrawer: () =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open: false } ) as const,
	dismissUpdateBanner: ( postId: number ) =>
		( { type: 'DISMISS_UPDATE_BANNER', postId } ) as const,
	clearDismissedForPost: ( postId: number ) =>
		( { type: 'CLEAR_DISMISSED_FOR_POST', postId } ) as const,
	markUpdateBannerViewed: ( postId: number, versionTo: string ) =>
		( { type: 'MARK_UPDATE_BANNER_VIEWED', postId, versionTo } ) as const,
};

const selectors = {
	isReviewDrawerOpen: ( state: State ): boolean => state.isReviewDrawerOpen,
	isUpdateBannerDismissedFor: ( state: State, postId: number ): boolean =>
		state.dismissedPostIds.has( postId ),
	wasUpdateBannerViewedFor: (
		state: State,
		postId: number,
		versionTo: string
	): boolean =>
		state.viewedPostVersionPairs.has( `${ postId }:${ versionTo }` ),
};

const config = { reducer, actions, selectors };

/**
 * Register the integration store. Called once from the entrypoint;
 * guarded against double-registration so HMR / repeated boots are
 * safe (mirrors the pattern in the upstream `@woocommerce/email-editor`
 * store).
 */
export function registerStore(): void {
	if ( select( STORE_NAME ) !== undefined ) {
		return;
	}
	register( createReduxStore( STORE_NAME, config ) );
}

declare module '@wordpress/data' {
	interface StoreRegistry {
		[ STORE_NAME ]: GenericStoreDescriptor<
			ReduxStoreConfig< State, typeof actions, typeof selectors >
		>;
	}
}

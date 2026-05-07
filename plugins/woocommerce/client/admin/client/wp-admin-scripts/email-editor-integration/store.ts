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
 * @internal
 */
export const STORE_NAME = 'woocommerce/email-editor-integration';

interface State {
	isReviewDrawerOpen: boolean;
}

const initialState: State = {
	isReviewDrawerOpen: false,
};

type Action = { type: 'SET_REVIEW_DRAWER_OPEN'; open: boolean };

const reducer = ( state: State = initialState, action: Action ): State => {
	switch ( action.type ) {
		case 'SET_REVIEW_DRAWER_OPEN':
			return { ...state, isReviewDrawerOpen: action.open };
		default:
			return state;
	}
};

const actions = {
	setReviewDrawerOpen: ( open: boolean ) =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open } as const ),
	openReviewDrawer: () =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open: true } as const ),
	closeReviewDrawer: () =>
		( { type: 'SET_REVIEW_DRAWER_OPEN', open: false } as const ),
};

const selectors = {
	isReviewDrawerOpen: ( state: State ): boolean => state.isReviewDrawerOpen,
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

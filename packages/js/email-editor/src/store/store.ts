/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import {
	ReduxStoreConfig,
	StoreDescriptor as GenericStoreDescriptor,
} from '@wordpress/data/build-types/types';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import { storeName } from './constants';
import { getInitialState } from './initial-state';
import { reducer } from './reducer';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

const getConfig = (postId?: string, postType?: string) =>
	( {
		actions,
		controls,
		selectors,
		resolvers,
		reducer,
		initialState: getInitialState(postId, postType),
	} as const );

export type EditorStoreConfig = ReturnType< typeof getConfig >;

export const createStore = (postId?: string, postType?: string) => {
	const store = createReduxStore( storeName, getConfig(postId, postType) );
	register( store );
	return store;
};

export interface EmailEditorStore {
	getActions: () => EditorStoreConfig[ 'actions' ];
	getSelectors: () => EditorStoreConfig[ 'selectors' ];
}

declare module '@wordpress/data' {
	interface StoreMap {
		[ storeName ]: GenericStoreDescriptor<
			ReduxStoreConfig<
				unknown,
				ReturnType< EmailEditorStore[ 'getActions' ] >,
				ReturnType< EmailEditorStore[ 'getSelectors' ] >
			>
		>;
	}
}

export { actions, selectors };

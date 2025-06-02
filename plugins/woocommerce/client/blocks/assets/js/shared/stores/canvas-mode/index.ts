/**
 * External dependencies
 */
import { createReduxStore, register, select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ACTION_SET_CANVAS_MODE, STORE_NAME } from './constants';
import type { CanvasModeState } from './types';

type StoreState = CanvasModeState;

type Actions = {
	type: typeof ACTION_SET_CANVAS_MODE;
	isEditMode: boolean;
};

const DEFAULT_STATE: StoreState = {
	isEditMode: false,
};

const actions = {
	setCanvasMode( isEditMode: boolean ) {
		return {
			type: ACTION_SET_CANVAS_MODE,
			isEditMode,
		};
	},
};

const selectors = {
	isEditMode() {
		// Always check the current URL for the most up-to-date state.
		const url = new URL( window.location.href );
		const canvas = url.searchParams.get( 'canvas' );
		return canvas === 'edit';
	},
};

if ( typeof window !== 'undefined' ) {
	const handleUrlChange = async () => {
		const url = new URL( window.location.href );
		const canvas = url.searchParams.get( 'canvas' );
		const isEditMode = canvas === 'edit';

		// Update store state to trigger any subscribed components.
		const storeDispatch = dispatch( STORE_NAME ) as {
			setCanvasMode: ( isEditMode: boolean ) => void;
		};

		await Promise.resolve();

		// Update store state - this triggers re-renders.
		storeDispatch.setCanvasMode( isEditMode );
	};

	window.addEventListener( 'popstate', () => handleUrlChange() );
	window.addEventListener( 'pushstate', () => handleUrlChange() );

	handleUrlChange();
}

const reducer = ( state: StoreState = DEFAULT_STATE, action: Actions ) => {
	switch ( action.type ) {
		case ACTION_SET_CANVAS_MODE:
			return {
				...state,
				isEditMode: action.isEditMode,
			};

		default:
			return state;
	}
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

if ( ! select( STORE_NAME ) ) {
	register( store );
}

export { default as useCanvasMode } from './use-canvas-mode';
export type { CanvasModeState } from './types';

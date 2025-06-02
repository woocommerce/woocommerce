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
		// Always check the current URL for the most up-to-date state
		const url = new URL( window.location.href );
		const canvas = url.searchParams.get( 'canvas' );
		return canvas === 'edit';
	},
};

// Add URL change listeners to trigger store updates for block registration
if ( typeof window !== 'undefined' ) {
	const handleUrlChange = async () => {
		const url = new URL( window.location.href );
		const canvas = url.searchParams.get( 'canvas' );
		const isEditMode = canvas === 'edit';

		// Update store state to trigger any subscribed components/systems
		const storeDispatch = dispatch( STORE_NAME ) as {
			setCanvasMode: ( isEditMode: boolean ) => void;
		};

		// Wait for the next tick to ensure other state updates have completed
		await Promise.resolve();

		// Update store state - this triggers re-renders and block registration updates
		storeDispatch.setCanvasMode( isEditMode );
	};

	// Listen for both popstate (browser back/forward) and pushstate (programmatic navigation)
	window.addEventListener( 'popstate', () => handleUrlChange() );
	window.addEventListener( 'pushstate', () => handleUrlChange() );

	// Initial state setup
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

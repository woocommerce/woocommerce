/* global JSX */

/**
 * External dependencies
 */
import { act } from 'react';
import { createRoot } from 'react-dom/client';

// Mount harness shared by the suites that render without a testing
// library. Callers unmount via cleanup, or through root for suites that
// manage unmounting themselves.
export const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	// React's createRoot API requires the initial render to be wrapped in act.
	// eslint-disable-next-line testing-library/no-unnecessary-act
	act( () => {
		root.render( element );
	} );

	return {
		container,
		root,
		cleanup: () => {
			act( () => root.unmount() );
			container.remove();
		},
	};
};

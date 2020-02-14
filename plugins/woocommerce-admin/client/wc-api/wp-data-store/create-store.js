/**
 * External dependencies
 */
import { createStore } from 'redux';

/**
 * Internal dependencies
 */
import reducer from './reducer';

export default ( name ) => {
	const devTools = window.__REDUX_DEVTOOLS_EXTENSION__;

	return createStore(
		reducer,
		devTools && devTools( { name, instanceId: name } )
	);
};

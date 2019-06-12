/** @format */
/**
 * External dependencies
 */
import { ApiClient } from '@fresh-data/framework';
import { createStore as createReduxStore } from 'redux';

/**
 * Internal dependencies
 */
import reducer from './reducer';

function createStore( name ) {
	const devTools = window.__REDUX_DEVTOOLS_EXTENSION__;

	return createReduxStore( reducer, devTools && devTools( { name: name, instanceId: name } ) );
}

function createDataHandlers( store ) {
	return {
		dataRequested: resourceNames => {
			// This is a temporary fix until it can be resolved upstream in fresh-data.
			// See: https://github.com/woocommerce/woocommerce-admin/pull/2387/files#r292355276
			if ( document.hidden ) {
				return;
			}

			store.dispatch( {
				type: 'FRESH_DATA_REQUESTED',
				resourceNames,
				time: new Date(),
			} );
		},
		dataReceived: resources => {
			store.dispatch( {
				type: 'FRESH_DATA_RECEIVED',
				resources,
				time: new Date(),
			} );
		},
	};
}

function createApiClient( name, apiSpec ) {
	const store = createStore( name );
	const dataHandlers = createDataHandlers( store );
	const apiClient = new ApiClient( apiSpec );
	apiClient.setDataHandlers( dataHandlers );

	const storeChanged = () => {
		apiClient.setState( store.getState() );
	};
	store.subscribe( storeChanged );

	return apiClient;
}

export default createApiClient;

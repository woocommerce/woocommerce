/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { createSelectors } from './selectors';
import * as actions from './actions';
import controls from '../controls';
import { createResolvers } from './resolvers';
import { createReducer, ResourceState } from './reducer';

type CrudDataStore = {
	storeName: string;
	resourceName: string;
	namespace: string;
};

export const createCrudDataStore = ( args: CrudDataStore ) => {
	const { storeName, resourceName, namespace } = args;
	const reducer = createReducer( resourceName );
	const resolvers = createResolvers( resourceName, namespace );
	const selectors = createSelectors( resourceName );

	registerStore( storeName, {
		reducer: reducer as Reducer< ResourceState >,
		actions,
		selectors,
		resolvers,
		controls,
	} );
};

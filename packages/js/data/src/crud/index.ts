/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { createSelectors } from './selectors';
import { createDispatchActions } from './actions';
import controls from '../controls';
import { createResolvers } from './resolvers';
import { createReducer, ResourceState } from './reducer';

type CrudDataStore = {
	storeName: string;
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
};

export const createCrudDataStore = ( {
	storeName,
	resourceName,
	namespace,
	pluralResourceName,
}: CrudDataStore ) => {
	const reducer = createReducer();
	const actions = createDispatchActions( {
		resourceName,
		namespace,
	} );
	const resolvers = createResolvers( {
		resourceName,
		pluralResourceName,
		namespace,
	} );
	const selectors = createSelectors( { resourceName, pluralResourceName } );

	registerStore( storeName, {
		reducer: reducer as Reducer< ResourceState >,
		actions,
		selectors,
		resolvers,
		controls,
	} );
};

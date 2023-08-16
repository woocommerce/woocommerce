/**
 * External dependencies
 */
import { Reducer } from 'redux';
import { createReduxStore, register } from '@wordpress/data';
import { Resolver } from '@wordpress/data/build-types/types';

/**
 * Internal dependencies
 */
import { createSelectors } from './selectors';
import { createDispatchActions } from './actions';
import defaultControls from '../controls';
import { createResolvers } from './resolvers';
import { createReducer, ResourceState } from './reducer';

type ReduxStoreConfig< ResourceState > = {
	reducer: Reducer< ResourceState >;
	actions: Record< string, unknown >;
	selectors: Record< string, unknown >;
	resolvers: Record< string, Resolver >;
	controls: Record< string, unknown >;
};

type CrudDataStore = {
	storeName: string;
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
	storeConfig?: Partial< ReduxStoreConfig< ResourceState > >;
};

export const createCrudDataStore = ( {
	storeName,
	resourceName,
	namespace,
	pluralResourceName,
	storeConfig = {},
}: CrudDataStore ) => {
	const crudActions = createDispatchActions( {
		resourceName,
		namespace,
	} );
	const crudResolvers = createResolvers( {
		storeName,
		resourceName,
		pluralResourceName,
		namespace,
	} );
	const crudSelectors = createSelectors( {
		resourceName,
		pluralResourceName,
		namespace,
	} );

	const {
		reducer,
		actions = {},
		selectors = {},
		resolvers = {},
		controls = {},
	} = storeConfig;

	const crudReducer = createReducer( reducer );

	const store = createReduxStore( storeName, {
		reducer: crudReducer as Reducer< ResourceState >,
		actions: { ...crudActions, ...actions },
		selectors: { ...crudSelectors, ...selectors },
		resolvers: { ...crudResolvers, ...resolvers },
		controls: { ...defaultControls, ...controls },
	} );
	register( store );
};

/**
 * External dependencies
 */
import { combineReducers, registerStore } from '@wordpress/data';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import { createSelectors } from './selectors';
import { createDispatchActions } from './actions';
import defaultControls from '../controls';
import { createResolvers } from './resolvers';
import { createReducer, ResourceState } from './reducer';

// Import types from @wordpress/data that are not exposed in the current dependent version.
type MapOf< T > = { [ name: string ]: T };

// eslint-disable-next-line
export type ActionCreator = Function | Generator;
// eslint-disable-next-line
export type Resolver = Function | Generator;
// eslint-disable-next-line
export type Selector = Function;

export interface ReduxStoreConfig<
	State,
	ActionCreators extends MapOf< ActionCreator >,
	Selectors
> {
	initialState?: State;
	// eslint-disable-next-line
	reducer: ( state: any, action: any ) => any;
	actions?: ActionCreators;
	resolvers?: MapOf< Resolver >;
	selectors?: Selectors;
	// eslint-disable-next-line
	controls?: MapOf< Function >;
}

// eslint-disable-next-line
export type AnyConfig = ReduxStoreConfig< any, any, any >;

type CrudDataStore = {
	storeName: string;
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
	// eslint-disable-next-line
	storeConfig?: Partial< ReduxStoreConfig< ResourceState, any, any > >;
};

export const createCrudDataStore = ( {
	storeName,
	resourceName,
	namespace,
	pluralResourceName,
	storeConfig = {},
}: CrudDataStore ) => {
	const crudReducer = createReducer();

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

	registerStore( storeName, {
		reducer: reducer
			? ( combineReducers( {
					crudReducer,
					reducer,
			  } ) as Reducer )
			: ( crudReducer as Reducer< ResourceState > ),
		actions: { ...crudActions, ...actions },
		selectors: { ...crudSelectors, ...selectors },
		resolvers: { ...crudResolvers, ...resolvers },
		controls: { ...defaultControls, ...controls },
	} );
};

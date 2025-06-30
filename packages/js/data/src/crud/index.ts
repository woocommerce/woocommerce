/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { AnyAction } from 'redux';

/**
 * Internal dependencies
 */
import { createSelectors } from './selectors';
import { createDispatchActions } from './actions';
import defaultControls from '../controls';
import { createResolvers } from './resolvers';
import { createReducer, ResourceState } from './reducer';

// Arguments can be anything
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyArguments = any[];

interface CrudStoreParams<
	Actions extends Record< string, ( ...args: AnyArguments ) => unknown >,
	Selectors,
	Resolvers = Record< string, ( ...args: AnyArguments ) => unknown >,
	Controls = Record< string, ( ...args: AnyArguments ) => unknown >,
	Reducer extends (
		state: ResourceState | undefined,
		action: AnyAction
	) => ResourceState = (
		state: ResourceState | undefined,
		action: AnyAction
	) => ResourceState
> {
	storeName: string;
	resourceName: string;
	namespace: string;
	pluralResourceName: string;
	storeConfig?: {
		reducer?: Reducer;
		actions?: Actions;
		selectors?: Selectors;
		resolvers?: Resolvers;
		controls?: Controls;
	};
}

export const createCrudDataStore = <
	Actions extends Record<
		string,
		( ...args: AnyArguments ) => unknown
	> = Record< string, ( ...args: AnyArguments ) => unknown >,
	Selectors = unknown
>( {
	storeName,
	resourceName,
	namespace,
	pluralResourceName,
	storeConfig,
}: CrudStoreParams< Actions, Selectors > ) => {
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
	} = storeConfig || {};

	const crudReducer = reducer ? createReducer( reducer ) : createReducer();

	const store = createReduxStore< unknown, Actions, Selectors >( storeName, {
		reducer: crudReducer,
		actions: { ...crudActions, ...actions } as Actions,
		selectors: {
			...crudSelectors,
			...selectors,
		} as Selectors,
		resolvers: { ...crudResolvers, ...resolvers },
		controls: {
			...defaultControls,
			...controls,
		},
	} );

	register( store );

	return store;
};

/**
 * External dependencies
 */
import { createReduxStore, register, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	ACTION_SET_PRODUCT_TYPES,
	ACTION_SWITCH_PRODUCT_TYPE,
	ACTION_REGISTER_LISTENER,
	ACTION_UNREGISTER_LISTENER,
	STORE_NAME,
} from './constants';
import { getProductTypeOptions } from '../../../utils/get-product-type-options';
import type { ProductTypeProps } from './types';

type StoreState = {
	productTypes: {
		list: ProductTypeProps[];
		current: string | undefined;
	};
	listeners: string[];
};

type Actions = {
	type:
		| typeof ACTION_SET_PRODUCT_TYPES
		| typeof ACTION_SWITCH_PRODUCT_TYPE
		| typeof ACTION_REGISTER_LISTENER
		| typeof ACTION_UNREGISTER_LISTENER;
	productTypes?: ProductTypeProps[];
	current?: string;
	listener?: string;
};

const productTypeOptions = getProductTypeOptions();

const DEFAULT_STATE = {
	productTypes: {
		list: productTypeOptions,
		current: productTypeOptions[ 0 ]?.slug,
	},
	listeners: [],
};

const actions = {
	switchProductType( slug: string ) {
		return {
			type: ACTION_SWITCH_PRODUCT_TYPE,
			current: slug,
		};
	},

	setProductTypes( productTypes: ProductTypeProps[] ) {
		return {
			type: ACTION_SET_PRODUCT_TYPES,
			productTypes,
		};
	},

	registerListener( listener: string ) {
		return {
			type: ACTION_REGISTER_LISTENER,
			listener,
		};
	},

	unregisterListener( listener: string ) {
		return {
			type: ACTION_UNREGISTER_LISTENER,
			listener,
		};
	},
};

const selectors = {
	getProductTypes( state: StoreState ) {
		return state.productTypes.list;
	},
	getCurrentProductType( state: StoreState ) {
		return state.productTypes.list.find(
			( productType ) => productType.slug === state.productTypes.current
		);
	},
	getRegisteredListeners( state: StoreState ) {
		return state.listeners;
	},
};

const reducer = ( state: StoreState = DEFAULT_STATE, action: Actions ) => {
	switch ( action.type ) {
		case ACTION_SET_PRODUCT_TYPES:
			return {
				...state,
				productTypes: {
					...state.productTypes,
					list: action.productTypes || [],
				},
			};

		case ACTION_SWITCH_PRODUCT_TYPE:
			return {
				...state,
				productTypes: {
					...state.productTypes,
					current: action.current,
				},
			};

		case ACTION_REGISTER_LISTENER:
			return {
				...state,
				listeners: [ ...state.listeners, action.listener || '' ],
			};

		case ACTION_UNREGISTER_LISTENER:
			return {
				...state,
				listeners: state.listeners.filter(
					( listener ) => listener !== action.listener
				),
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

export { default as useProductTypeSelector } from './use-product-type-selector';
export type { ProductTypeProps } from './types';

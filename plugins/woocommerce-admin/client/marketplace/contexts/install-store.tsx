/**
 * External dependencies
 */
import { createReduxStore, dispatch, register } from '@wordpress/data';

const INSTALLING_STORE_NAME = 'woocommerce-admin/installing';

export interface InstallingStateError {
	productKey: string;
	error: string;
}

interface InstallingState {
	installingProducts: string[];
	errors: {
		[ key: string ]: InstallingStateError;
	};
}

const DEFAULT_STATE: InstallingState = {
	installingProducts: [],
	errors: {},
};

const store = createReduxStore( INSTALLING_STORE_NAME, {
	reducer( state: InstallingState | undefined = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'START_INSTALLING':
				return {
					...state,
					installingProducts: [
						...state.installingProducts,
						action.productKey,
					],
				};
			case 'STOP_INSTALLING':
				return {
					...state,
					installingProducts: [
						...state.installingProducts.filter(
							( productKey ) => productKey !== action.productKey
						),
					],
				};
			case 'ADD_ERROR':
				return {
					...state,
					errors: {
						...state.errors,
						[ action.productKey ]: {
							productKey: action.productKey,
							error: action.error,
						},
					},
				};
			case 'REMOVE_ERROR':
				const errors = { ...state.errors };
				delete errors[ action.productKey ];
				return {
					...state,
					errors,
				};
		}

		return state;
	},
	actions: {
		startInstalling( productKey: string ) {
			return {
				type: 'START_INSTALLING',
				productKey,
			};
		},
		stopInstalling( productKey: string ) {
			return {
				type: 'STOP_INSTALLING',
				productKey,
			};
		},
		addError( productKey: string, error: string ) {
			setTimeout( () => {
				dispatch( store ).removeError( productKey );
			}, 5000 );
			return {
				type: 'ADD_ERROR',
				productKey,
				error,
			};
		},
		removeError( productKey: string ) {
			return {
				type: 'REMOVE_ERROR',
				productKey,
			};
		},
	},
	selectors: {
		isInstalling(
			state: InstallingState | undefined,
			productKey: string
		): boolean {
			if ( ! state ) {
				return false;
			}
			return state.installingProducts.includes( productKey );
		},
		errors( state: InstallingState | undefined ): InstallingStateError[] {
			if ( ! state ) {
				return [];
			}
			return Object.values( state.errors );
		},
	},
} );

register( store );

export { store as installingStore, INSTALLING_STORE_NAME };

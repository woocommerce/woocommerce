/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { InstallingState } from './types';

const INSTALLING_STORE_NAME = 'woocommerce-admin/installing';

export interface InstallingStateErrorAction {
	label: string;
	onClick: () => void;
}

const DEFAULT_STATE: InstallingState = {
	installingProducts: [],
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
	},
} );

register( store );

export { store as installingStore, INSTALLING_STORE_NAME };

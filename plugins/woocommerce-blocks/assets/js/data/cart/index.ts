/**
 * External dependencies
 */
import {
	dispatch as wpDataDispatch,
	registerStore,
	select as wpDataSelect,
} from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer, { State } from './reducers';
import { controls as sharedControls } from '../shared-controls';
import { controls } from './controls';
import type { SelectFromMap, DispatchFromMap } from '../mapped-types';
import { pushChanges } from './push-changes';
import { checkPaymentMethodsCanPay } from '../payment/check-payment-methods';

const registeredStore = registerStore< State >( STORE_KEY, {
	reducer,
	actions,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	controls: { ...dataControls, ...sharedControls, ...controls } as any,
	selectors,
	resolvers,
} );

registeredStore.subscribe( pushChanges );
registeredStore.subscribe( async () => {
	const isInitialized =
		wpDataSelect( STORE_KEY ).hasFinishedResolution( 'getCartData' );

	if ( ! isInitialized ) {
		return;
	}
	await checkPaymentMethodsCanPay();
	await checkPaymentMethodsCanPay( true );
} );

const unsubscribeInitializePaymentStore = registeredStore.subscribe(
	async () => {
		const cartLoaded =
			wpDataSelect( STORE_KEY ).hasFinishedResolution( 'getCartTotals' );
		if ( cartLoaded ) {
			wpDataDispatch(
				'wc/store/payment'
			).__internalUpdateAvailablePaymentMethods();
			unsubscribeInitializePaymentStore();
		}
	}
);

export const CART_STORE_KEY = STORE_KEY;

declare module '@wordpress/data' {
	function dispatch(
		key: typeof CART_STORE_KEY
	): DispatchFromMap< typeof actions >;
	function select( key: typeof CART_STORE_KEY ): SelectFromMap<
		typeof selectors
	> & {
		hasFinishedResolution: ( selector: string ) => boolean;
	};
}

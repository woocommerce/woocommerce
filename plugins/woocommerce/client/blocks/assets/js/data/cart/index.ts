/**
 * External dependencies
 */
import {
	register,
	subscribe,
	createReduxStore,
	dispatch as wpDispatch,
	select,
} from '@wordpress/data';
import { controls as dataControls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import { pushChanges, flushChanges } from './push-changes';
import {
	updatePaymentMethods,
	debouncedUpdatePaymentMethods,
} from './update-payment-methods';
import {
	hasCartSession,
	persistenceLayer,
	isAddingToCart,
} from './persistence-layer';
import { defaultCartState } from './default-state';
import { getTriggerStoreSyncEvent } from './utils';
import type { QuantityChanges } from './notify-quantity-changes';
import { isEditor } from '../utils';

export const config = {
	reducer,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	actions: actions as any,
	controls: dataControls,
	selectors,
	resolvers,
	initialState: {
		...defaultCartState,
		cartData: {
			...defaultCartState.cartData,
			...( persistenceLayer.get() || {} ),
		},
	},
};

export const store = createReduxStore( STORE_KEY, config );

export type CartStoreDescriptor = typeof store;

register( store );

// The resolver for getCartData fires off an API request. But if we know the cart is empty, we can skip the request.
// Likewise, if we have a valid persistent cart, we can skip the request.
// The only reliable way to check if the cart is empty is to check the cookies.
window.addEventListener( 'load', () => {
	const cachedCart = persistenceLayer.get();
	// On login, if a customer had a cart session, the cached cart is equal to the default cart data, with no items.
	// We need to check if the cached cart has items, otherwise we will wrongly skip the API request.
	const hasItemsInCachedCart = cachedCart?.itemsCount > 0;

	if (
		( ! hasCartSession() || hasItemsInCachedCart ) &&
		! isAddingToCart() &&
		! isEditor() // Don't finish resolution in editor,but only for real carts
	) {
		// Prevent the API request from being made.
		wpDispatch( store ).finishResolution( 'getCartData' );
	}
} );

// Pushes changes whenever the store is updated.
subscribe( pushChanges, store );

// Emmits event to sync iAPI store.
let previousCart: object | null = null;
subscribe( () => {
	const cartData = select( STORE_KEY ).getCartData();
	if (
		getTriggerStoreSyncEvent() === true &&
		previousCart !== null &&
		previousCart !== cartData
	) {
		window.dispatchEvent(
			// Question: What are the usual names for WooCommerce events?
			new CustomEvent( 'wc-blocks_store_sync_required', {
				detail: { type: 'from_@wordpress/data' },
			} )
		);
	}
	previousCart = cartData;
}, store );

// Listens to cart sync events from the iAPI store.
window.addEventListener( 'wc-blocks_store_sync_required', ( event: Event ) => {
	const customEvent = event as CustomEvent< {
		type: string;
		quantityChanges: QuantityChanges;
	} >;
	const { type, quantityChanges } = customEvent.detail;
	if ( type === 'from_iAPI' ) {
		wpDispatch( store ).syncCartWithIAPIStore( quantityChanges );
	}
} );

// This will skip the debounce and immediately push changes to the server when a field is blurred.
document.body.addEventListener( 'focusout', ( event: FocusEvent ) => {
	if (
		event.target &&
		event.target instanceof Element &&
		event.target.tagName.toLowerCase() === 'input'
	) {
		flushChanges();
	}
} );

// First we will run the updatePaymentMethods function without any debounce to ensure payment methods are ready as soon
// as the cart is loaded. After that, we will unsubscribe this function and instead run the
// debouncedUpdatePaymentMethods function on subsequent cart updates.
const unsubscribeUpdatePaymentMethods = subscribe( async () => {
	const didActionDispatch = await updatePaymentMethods();
	if ( didActionDispatch ) {
		// The function we're currently in will unsubscribe itself. When we reach this line, this will be the last time
		// this function is called.
		unsubscribeUpdatePaymentMethods();
		// Resubscribe, but with the debounced version of updatePaymentMethods.
		subscribe( debouncedUpdatePaymentMethods, store );
	}
}, store );

export const CART_STORE_KEY = STORE_KEY;

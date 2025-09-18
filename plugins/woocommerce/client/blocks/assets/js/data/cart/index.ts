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
import { getSettingWithCoercion } from '@woocommerce/settings';
import type { ServerAddressAutocompleteProvider } from '@woocommerce/types';
import type {
	ActionCreatorsOf,
	ConfigOf,
} from '@wordpress/data/build-types/types';

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
import type { CheckoutStoreDescriptor } from '../checkout';

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

// Update address providers whenever the country changes.
let previousShippingCountry: string | null = null;
let previousBillingCountry: string | null = null;

/**
 * Updates the active address autocomplete provider based on the country.
 * This function checks all registered providers and selects the first one that
 * supports the given country, respecting the server-defined provider order.
 */
function updateAutocompleteProvider(
	addressType: 'shipping' | 'billing',
	country: string,
	serverProviders: ServerAddressAutocompleteProvider[]
) {
	const checkoutActions = wpDispatch(
		'wc/store/checkout'
	) as ActionCreatorsOf< ConfigOf< CheckoutStoreDescriptor > >;
	// Check if window.wc.addressAutocomplete.providers exists
	if ( ! window?.wc?.addressAutocomplete?.providers ) {
		checkoutActions.setActiveAddressAutocompleteProvider( '', addressType );
		if ( window?.wc?.addressAutocomplete?.activeProvider ) {
			window.wc.addressAutocomplete.activeProvider[ addressType ] = null;
		}
		return;
	}

	// Check providers in preference order (server handles preferred provider ordering)
	for ( const serverProvider of serverProviders ) {
		const provider =
			window?.wc?.addressAutocomplete?.providers?.[ serverProvider.id ];

		if ( provider && provider.canSearch( country ) ) {
			checkoutActions.setActiveAddressAutocompleteProvider(
				provider.id,
				addressType
			);

			// Set globally as this is going to be the source of truth where the actual provider objects are stored.
			window.wc.addressAutocomplete.activeProvider[ addressType ] =
				provider;
			return;
		}
	}

	// No provider supports this country, clear the active provider
	checkoutActions.setActiveAddressAutocompleteProvider( '', addressType );
	// Set globally as this is going to be the source of truth where the actual provider objects are stored.
	if ( window?.wc?.addressAutocomplete?.activeProvider ) {
		window.wc.addressAutocomplete.activeProvider[ addressType ] = null;
	}
}

// Get server providers configuration
const serverProviders = getSettingWithCoercion<
	ServerAddressAutocompleteProvider[]
>(
	'addressAutocompleteProviders',
	[],
	( type: unknown ): type is ServerAddressAutocompleteProvider[] => {
		if ( ! Array.isArray( type ) ) {
			return false;
		}

		return type.every( ( item ) => {
			return (
				typeof item.name === 'string' &&
				typeof item.id === 'string' &&
				typeof item.branding_html === 'string'
			);
		} );
	}
);

if ( serverProviders.length > 0 ) {
	subscribe( () => {
		const cartData = select( STORE_KEY ).getCartData();
		const shippingCountry = cartData?.shippingAddress?.country || '';
		const billingCountry = cartData?.billingAddress?.country || '';

		// Check if shipping country has changed
		if ( shippingCountry !== previousShippingCountry ) {
			previousShippingCountry = shippingCountry;
			updateAutocompleteProvider(
				'shipping',
				shippingCountry,
				serverProviders
			);
		}

		// Check if billing country has changed
		if ( billingCountry !== previousBillingCountry ) {
			previousBillingCountry = billingCountry;
			updateAutocompleteProvider(
				'billing',
				billingCountry,
				serverProviders
			);
		}
	}, store );
}

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

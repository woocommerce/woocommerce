/**
 * External dependencies
 */
import { dispatch as wpDispatch, select, subscribe } from '@wordpress/data';
import { ServerAddressAutocompleteProvider } from '@woocommerce/type-defs/address-autocomplete';
import {
	ActionCreatorsOf,
	ConfigOf,
	CurriedSelectorsOf,
} from '@wordpress/data/build-types/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { CheckoutStoreDescriptor } from '../index';
import type { CartStoreDescriptor } from '../../cart';

let previousShippingCountry: string | null = null;
let previousBillingCountry: string | null = null;

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

/**
 * Updates the active address autocomplete provider based on the country.
 * This function checks all registered providers and selects the first one that
 * supports the given country, respecting the server-defined provider order.
 *
 * @param addressType The type of address ('shipping' or 'billing')
 * @param country     The country code
 */
export function updateAutocompleteProvider(
	addressType: 'shipping' | 'billing',
	country: string
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

export const autocompleteSubscription = () => {
	if ( ! window?.wc?.addressAutocomplete?.providers ) {
		return;
	}

	const cartData = (
		select( 'wc/store/cart' ) as CurriedSelectorsOf< CartStoreDescriptor >
	 ).getCartData();
	const shippingCountry = cartData?.shippingAddress?.country || '';
	const billingCountry = cartData?.billingAddress?.country || '';

	// Check if shipping country has changed
	if ( shippingCountry !== previousShippingCountry ) {
		previousShippingCountry = shippingCountry;
		updateAutocompleteProvider( 'shipping', shippingCountry );
	}

	// Check if billing country has changed
	if ( billingCountry !== previousBillingCountry ) {
		previousBillingCountry = billingCountry;
		updateAutocompleteProvider( 'billing', billingCountry );
	}
};

export const subscribeToCartChanges = () => {
	if ( serverProviders && serverProviders.length > 0 ) {
		subscribe( autocompleteSubscription, 'wc/store/cart' );
	}
};

/**
 * External dependencies
 */
import { ServerAddressAutocompleteProvider } from '@woocommerce/type-defs/address-autocomplete';
import { ActionCreatorsOf, ConfigOf } from '@wordpress/data/build-types/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { CheckoutStoreDescriptor } from './index';

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
 * @param checkoutActions The checkout action creators.
 * @param addressType     The type of address ('shipping' or 'billing')
 * @param country         The country code
 */
export function updateAutocompleteProvider(
	checkoutActions: ActionCreatorsOf< ConfigOf< CheckoutStoreDescriptor > >,
	addressType: 'shipping' | 'billing',
	country: string
) {
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

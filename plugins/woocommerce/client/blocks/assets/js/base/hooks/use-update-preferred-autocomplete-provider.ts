/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { ServerAddressAutocompleteProvider } from '@woocommerce/type-defs/address-autocomplete';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element';
import type {
	ActionCreatorsOf,
	ConfigOf,
	CurriedSelectorsOf,
} from '@wordpress/data/build-types/types';

/**
 * Internal dependencies
 */
import { type CheckoutStoreDescriptor } from '../../data/checkout';
import { type CartStoreDescriptor } from '../../data/cart';

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
 */
export function useUpdatePreferredAutocompleteProvider(
	addressType: 'shipping' | 'billing'
) {
	const { country, registeredProviders } = useSelect(
		( select ) => {
			const addressTypeKey =
				addressType === 'shipping'
					? 'shippingAddress'
					: 'billingAddress';

			return {
				country: (
					select(
						'wc/store/cart'
					) as CurriedSelectorsOf< CartStoreDescriptor >
				 ).getCartData()?.[ addressTypeKey ]?.country,
				registeredProviders: (
					select(
						'wc/store/checkout'
					) as CurriedSelectorsOf< CheckoutStoreDescriptor >
				 ).getRegisteredAutocompleteProviders(),
			};
		},
		[ addressType ]
	);

	const { setActiveAddressAutocompleteProvider } = useDispatch(
		'wc/store/checkout'
	) as ActionCreatorsOf< ConfigOf< CheckoutStoreDescriptor > >;

	useEffect( () => {
		// Check if window.wc.addressAutocomplete.providers exists
		if ( ! window?.wc?.addressAutocomplete?.providers ) {
			setActiveAddressAutocompleteProvider( '', addressType );
			if ( window?.wc?.addressAutocomplete?.activeProvider ) {
				window.wc.addressAutocomplete.activeProvider[ addressType ] =
					null;
			}
			return;
		}

		// Check providers in preference order (server handles preferred provider ordering)
		for ( const serverProvider of serverProviders ) {
			const provider =
				window?.wc?.addressAutocomplete?.providers?.[
					serverProvider.id
				];

			if ( provider && provider.canSearch( country ) ) {
				setActiveAddressAutocompleteProvider(
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
		setActiveAddressAutocompleteProvider( '', addressType );
		// Set globally as this is going to be the source of truth where the actual provider objects are stored.
		if ( window?.wc?.addressAutocomplete?.activeProvider ) {
			window.wc.addressAutocomplete.activeProvider[ addressType ] = null;
		}
	}, [
		addressType,
		country,
		setActiveAddressAutocompleteProvider,
		registeredProviders,
	] );
}

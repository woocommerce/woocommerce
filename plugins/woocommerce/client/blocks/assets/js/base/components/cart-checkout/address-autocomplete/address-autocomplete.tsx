/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import type { ServerAddressAutocompleteProvider } from '@woocommerce/types';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	type AddressFormType,
	getSettingWithCoercion,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ValidatedTextInputProps } from '../../../../../../packages/components/text-input/types';
import './style.scss';

/**
 * Address Autocomplete component.
 *
 * @param props             - Props for the component.
 * @param props.addressType - Type of address ('billing' or 'shipping').
 * @param props.id          - ID for the input field.
 * @return Address Autocomplete component.
 */
export const AddressAutocomplete = ( {
	addressType,
	id,
	...props
}: { addressType: AddressFormType; id: string } & ValidatedTextInputProps ) => {
	const serverProviders = getSettingWithCoercion<
		ServerAddressAutocompleteProvider[]
	>(
		'addressAutocompleteProviders',
		[],
		( type: unknown ): type is ServerAddressAutocompleteProvider[] => {
			if ( ! Array.isArray( type ) ) {
				return true;
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

	const { country, registeredProviders } = useSelect(
		( select ) => {
			const cartSelectors = select( cartStore );
			const checkoutSelectors = select( checkoutStore );
			const key =
				addressType === 'shipping'
					? 'shippingAddress'
					: 'billingAddress';
			const cartData = cartSelectors.getCartData();
			return {
				country: cartData?.[ key ]?.country || '',
				registeredProviders:
					checkoutSelectors.getRegisteredAutocompleteProviders() ||
					[],
			};
		},
		[ addressType ]
	);

	const { setActiveAddressAutocompleteProvider } =
		useDispatch( checkoutStore );

	// Used to set active provider on mount and when country changes.
	useEffect( () => {
		if ( ! window?.wc?.addressAutocomplete?.providers ) {
			return;
		}
		// Check providers in preference order (server handles preferred provider ordering).
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

		setActiveAddressAutocompleteProvider( '', addressType );
		// Set globally as this is going to be the source of truth where the actual provider objects are stored.
		if ( window?.wc?.addressAutocomplete?.activeProvider ) {
			window.wc.addressAutocomplete.activeProvider[ addressType ] = null;
		}
	}, [
		country,
		registeredProviders,
		setActiveAddressAutocompleteProvider,
		addressType,
		serverProviders,
	] );

	return (
		<div className="wc-block-components-address-autocomplete-container">
			<ValidatedTextInput
				{ ...props }
				id={ id }
				onChange={ props.onChange }
			/>
		</div>
	);
};

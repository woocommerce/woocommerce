/**
 * External dependencies
 */
import {
	ValidatedTextInput,
	type ValidatedTextInputHandle,
} from '@woocommerce/blocks-components';
import type {
	AddressAutocompleteResult,
	ClientAddressAutocompleteProvider,
	ServerAddressAutocompleteProvider,
} from '@woocommerce/types';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState, useRef } from '@wordpress/element';
import { AddressFormType, getSettingWithCoercion } from '@woocommerce/settings';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { ValidatedTextInputProps } from '../../../../../../packages/components/text-input/types';
import './style.scss';
import { useUpdatePreferredAutocompleteProvider } from '../../../hooks/use-update-preferred-autocomplete-provider';

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
	// This hook will monitor for changes in country and update the provider accordingly.
	useUpdatePreferredAutocompleteProvider( addressType );
    
	const inputRef = useRef< ValidatedTextInputHandle >( null );
	const [ suggestions, setSuggestions ] = useState<
		AddressAutocompleteResult[]
	>( [] );
	const [ isSearching, setIsSearching ] = useState( false );
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ isSettingAddress, setIsSettingAddress ] = useState( false );
	const suppressSearchTimeoutRef = useRef< NodeJS.Timeout | null >( null );

	// Debounced search function
	const debouncedSearch = useDebounce( () => {
		if (
			isSettingAddress ||
			searchValue.length < 3 ||
			suppressSearchTimeoutRef.current
		) {
			setIsSearching( false );
			setSuggestions( [] );
			return;
		}

		// Do autocomplete search.
		const provider =
			window.wc.addressAutocomplete.activeProvider[
				addressType as 'shipping' | 'billing'
			];

		if ( provider ) {
			setIsSearching( true );
			provider
				.search( searchValue, country )
				.then( ( results ) => {
					if ( results && results.length ) {
						setSuggestions( results );
					} else {
						setSuggestions( [] );
					}
					// Clear searching state after results are received
					setIsSearching( false );
				} )
				.catch( ( error ) => {
					console.error( 'Address search error:', error );
					setSuggestions( [] );
					setIsSearching( false );
				} );
		}
	}, 150 );

	// Trigger debounced search when searchValue changes
	useEffect( () => {
		debouncedSearch();
		return debouncedSearch.cancel;
	}, [ searchValue ] );

	// Cleanup timeouts on unmount
	useEffect( () => {
		return () => {
			if ( suppressSearchTimeoutRef.current ) {
				clearTimeout( suppressSearchTimeoutRef.current );
			}
		};
	}, [] );

	const addressChangeHandler = ( value: string ) => {
		props.onChange( value );

		// Don't trigger search when we're programmatically setting the address
		// or when search is temporarily suppressed after address selection
		if ( ! isSettingAddress && ! suppressSearchTimeoutRef.current ) {
			setSearchValue( value );
		}
	};

	return (
		<div className="wc-block-components-address-autocomplete-container">
			<ValidatedTextInput
				{ ...props }
				id={ id }
				ref={ inputRef }
				onChange={ addressChangeHandler }
			/>
		</div>
	);
};

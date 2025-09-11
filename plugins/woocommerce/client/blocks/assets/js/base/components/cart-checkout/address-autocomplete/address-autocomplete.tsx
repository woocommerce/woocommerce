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
import { Suggestions } from './suggestions';
import { useUpdatePreferredAutocompleteProvider } from '../../../hooks/use-update-preferred-autocomplete-provider';

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

	const [ activeProviderBranding, setActiveProviderBranding ] =
		useState< string >( '' );

	const activeProvider = useSelect(
		( select ) => {
			const store = select( checkoutStore );
			return store.getActiveAutocompleteProvider( addressType );
		},
		[ addressType ]
	);

	useEffect( () => {
		const activeProviderConfig = serverProviders.find(
			( provider ) => provider.id === activeProvider
		);
		if ( typeof activeProviderConfig?.branding_html === 'string' ) {
			setActiveProviderBranding( activeProviderConfig.branding_html );
			return;
		}
		setActiveProviderBranding( '' );
	}, [ activeProvider, serverProviders ] );

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

	const [ selectedSuggestion, setSelectedSuggestion ] =
		useState< number >( -1 );

	const handleKeyDown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		if ( suggestions.length === 0 ) {
			return;
		}

		if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			setSelectedSuggestion( ( prevIndex ) =>
				prevIndex < suggestions.length - 1 ? prevIndex + 1 : 0
			);
		} else if ( event.key === 'ArrowUp' ) {
			event.preventDefault();
			setSelectedSuggestion( ( prevIndex ) =>
				prevIndex > 0 ? prevIndex - 1 : suggestions.length - 1
			);
		} else if ( event.key === 'Enter' ) {
			event.preventDefault();
			if (
				selectedSuggestion >= 0 &&
				selectedSuggestion < suggestions.length
			) {
				const selected = suggestions[ selectedSuggestion ];
				const provider =
					window.wc.addressAutocomplete.activeProvider[
						addressType as 'shipping' | 'billing'
					];
				if ( provider ) {
					setIsSettingAddress( true );
					// Immediately suppress search to prevent any change events from triggering search
					suppressSearchTimeoutRef.current = setTimeout( () => {
						suppressSearchTimeoutRef.current = null;
					}, 1000 );
					provider
						.select( selected.id, country )
						.then( ( address ) => {
							const actionToDispatch =
								addressType === 'shipping'
									? setShippingAddress
									: setBillingAddress;
							actionToDispatch( {
								...address,
							} );
						} )
						.finally( () => {
							// Clear suggestions.
							setIsSettingAddress( false );
							setSuggestions( [] );
							setSelectedSuggestion( -1 );
						} );
				}
			}
		} else if ( event.key === 'Escape' ) {
			setSuggestions( [] );
			setSelectedSuggestion( -1 );
		}
	};

	const listId = `address-suggestions-${ addressType }-list`;
	const activeDescendantId =
		selectedSuggestion >= 0
			? `suggestion-item-${ addressType }-${ selectedSuggestion }`
			: undefined;

	return (
		<div className="wc-block-components-address-autocomplete-container">
			<ValidatedTextInput
				{ ...props }
				id={ id }
				ref={ inputRef }
				onChange={ addressChangeHandler }
				onKeyDown={ handleKeyDown }
				aria-expanded={ suggestions.length > 0 }
				aria-owns={ suggestions.length > 0 ? listId : undefined }
				aria-activedescendant={ activeDescendantId }
				aria-autocomplete="list"
				role="combobox"
			/>
			{ suggestions.length > 0 ? (
				<Suggestions
					selectedSuggestion={ selectedSuggestion }
					suggestions={ suggestions }
					branding={ activeProviderBranding }
					addressType={ addressType }
				/>
			) : null }
		</div>
	);
};

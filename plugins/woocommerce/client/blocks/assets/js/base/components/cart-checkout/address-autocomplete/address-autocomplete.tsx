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
	const { setBillingAddress, setShippingAddress } = useDispatch( cartStore );
	const [ activeProviderBranding, setActiveProviderBranding ] =
		useState< string >( '' );

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

				setActiveProviderBranding(
					serverProviders.find( ( p ) => p.id === provider.id )
						?.branding_html || ''
				);

				// Set globally as this is going to be the source of truth where the actual provider objects are stored.
				window.wc.addressAutocomplete.activeProvider[ addressType ] =
					provider;
				return;
			}
		}

		setActiveProviderBranding( '' );
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

	const handleSuggestionClick = async ( suggestionId: string ) => {
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
			try {
				const address = await provider.select( suggestionId, country );
				const actionToDispatch =
					addressType === 'shipping'
						? setShippingAddress
						: setBillingAddress;
				actionToDispatch( {
					...address,
				} );
			} finally {
				// Clear suggestions.
				setIsSettingAddress( false );
				setSuggestions( [] );
				setSelectedSuggestion( -1 );
			}
		}
	};

	const handleBlur = () => {
		// Use a small delay to allow clicks on suggestions to register
		setTimeout( () => {
			setSuggestions( [] );
			setSelectedSuggestion( -1 );
		}, 200 );
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
				onBlurCapture={ handleBlur }
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
					onSuggestionClick={ handleSuggestionClick }
				/>
			) : null }
		</div>
	);
};

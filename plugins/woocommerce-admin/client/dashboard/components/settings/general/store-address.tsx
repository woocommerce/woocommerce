/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { COUNTRIES_STORE_NAME, Country } from '@woocommerce/data';
import { decodeEntities } from '@wordpress/html-entities';
import { escapeRegExp } from 'lodash';
import { useEffect, useMemo, useState, useRef } from '@wordpress/element';
import { SelectControl, TextControl } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { FormInputProps } from '~/utils/types';

const storeAddressFields = [
	'addressLine1',
	'addressLine2',
	'city',
	'countryState',
	'postCode',
] as const;

type Option = { key: string; label: string };

/**
 * Form validation.
 *
 * @return {Function} Validator function.
 */
// Note: param was removed because its not used, callers still assume its needed. TODO: Fix this.
export function getStoreAddressValidator(/* locale: Locale = {} */) {
	/**
	 * Form validator.
	 *
	 * @param {Object} values Keyed values of all fields in the form.
	 * @return {Object} Key value of fields and error messages, { myField: 'This field is required' }
	 */
	return (
		values: Record< ( typeof storeAddressFields )[ number ], string >
	) => {
		const errors: {
			[ key: string ]: string;
		} = {};
		if ( ! values.countryState.trim().length ) {
			errors.countryState = __(
				'Please select a country / region',
				'woocommerce'
			);
		}

		return errors;
	};
}

/**
 * Get all country and state combinations used for select dropdowns.
 *
 * @return {Object} Select options, { value: 'US:GA', label: 'United States - Georgia' }
 */
export function getCountryStateOptions( countries: Country[] ) {
	const countryStateOptions = countries.reduce(
		( acc: Option[], country ) => {
			if ( ! country.states.length ) {
				acc.push( {
					key: country.code,
					label: decodeEntities( country.name ),
				} );

				return acc;
			}

			const countryStates = country.states.map( ( state ) => {
				return {
					key: country.code + ':' + state.code,
					label:
						decodeEntities( country.name ) +
						' — ' +
						decodeEntities( state.name ),
				};
			} );

			acc.push( ...countryStates );

			return acc;
		},
		[]
	);

	return countryStateOptions;
}

/**
 * Normalize state string for matching.
 *
 * @param {string} state The state to normalize.
 * @return {Function} filter function.
 */
export const normalizeState = ( state: string ): string => {
	return state.replace( /\s/g, '' ).toLowerCase();
};

/**
 * Get state filter
 *
 * @param {string} isStateAbbreviation     Whether to use state abbreviation or not.
 * @param {string} normalizedAutofillState The value of the autofillState field.
 * @return {Function} filter function.
 */
export const getStateFilter =
	(
		isStateAbbreviation: boolean,
		normalizedAutofillState: string
	): ( ( option: Option ) => boolean ) =>
	( option: Option ) => {
		const countryStateArray = isStateAbbreviation
			? option.key.split( ':' )
			: option.label.split( '—' );

		// No region options in the country
		if ( countryStateArray.length <= 1 ) {
			return false;
		}

		const state = countryStateArray[ 1 ];
		// Handle special case, for example: China — Beijing / 北京
		if ( state.includes( '/' ) ) {
			const stateStrList = state.split( '/' );
			return (
				normalizeState( stateStrList[ 0 ] ) ===
					normalizedAutofillState ||
				normalizeState( stateStrList[ 1 ] ) === normalizedAutofillState
			);
		}

		// Handle special case, for example: Iran — Alborz (البرز)
		if ( state.includes( '(' ) && state.includes( ')' ) ) {
			const stateStrList = state.replace( ')', '' ).split( '(' );
			return (
				normalizeState( stateStrList[ 0 ] ) ===
					normalizedAutofillState ||
				normalizeState( stateStrList[ 1 ] ) === normalizedAutofillState
			);
		}

		return normalizeState( state ) === normalizedAutofillState;
	};

/**
 * Get the autofill countryState fields and set value from filtered options.
 *
 * @param {Array}    options      Array of filterable options.
 * @param {string}   countryState The value of the countryState field.
 * @param {Function} setValue     Set value of the countryState input.
 * @return {Object} React component.
 */
export function useGetCountryStateAutofill(
	options: Option[],
	countryState: string,
	setValue: ( key: string, value: string ) => void
): JSX.Element {
	const [ autofillCountry, setAutofillCountry ] = useState( '' );
	const [ autofillState, setAutofillState ] = useState( '' );
	const isAutofillChange = useRef< boolean >();

	// Sync the autofill fields on first render and the countryState value changes.
	useEffect( () => {
		if ( ! isAutofillChange.current ) {
			const option = options.find( ( opt ) => opt.key === countryState );
			const labels = option
				? option.label.split( /\u2013|\u2014|\-/ )
				: [];
			const newCountry = ( labels[ 0 ] || '' ).trim();
			const newState = ( labels[ 1 ] || '' ).trim();

			if (
				newCountry !== autofillCountry ||
				newState !== autofillState
			) {
				setAutofillCountry( newCountry );
				setAutofillState( newState );
			}
		}
		isAutofillChange.current = false;
		// Disable reason: If we include autofillCountry/autofillState in the dependency array, we will have an unnecessary function call because we also update them in this function.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ countryState, options ] );

	// Sync the countryState value the autofill fields changes
	useEffect( () => {
		// Skip on first render since we only want to update the value when the autofill fields changes.
		if ( isAutofillChange.current === undefined ) {
			return;
		}

		if ( ! autofillCountry && ! autofillState && countryState ) {
			// Clear form
			isAutofillChange.current = true;
			setValue( 'countryState', '' );
			return;
		}
		const countrySearch = new RegExp(
			escapeRegExp( autofillCountry ),
			'i'
		);
		const isCountryAbbreviation = autofillCountry.length < 3;
		const isStateAbbreviation =
			autofillState.length < 3 && !! autofillState.match( /^[\w]+$/ );
		let filteredOptions: Option[] = [];

		if ( autofillCountry.length && autofillState.length ) {
			filteredOptions = options.filter( ( option ) =>
				countrySearch.test(
					isCountryAbbreviation ? option.key : option.label
				)
			);
			// no country matches so use all options for state filter.
			if ( ! filteredOptions.length ) {
				filteredOptions = [ ...options ];
			}
			if ( filteredOptions.length > 1 ) {
				filteredOptions = filteredOptions.filter(
					getStateFilter(
						isStateAbbreviation,
						normalizeState( autofillState )
					)
				);
			}
		} else if ( autofillCountry.length ) {
			filteredOptions = options.filter( ( option ) =>
				countrySearch.test(
					isCountryAbbreviation ? option.key : option.label
				)
			);
		} else if ( autofillState.length ) {
			filteredOptions = options.filter(
				getStateFilter(
					isStateAbbreviation,
					normalizeState( autofillState )
				)
			);
		}
		if (
			filteredOptions.length === 1 &&
			countryState !== filteredOptions[ 0 ].key
		) {
			isAutofillChange.current = true;
			setValue( 'countryState', filteredOptions[ 0 ].key );
		}
		// Disable reason: If we include countryState in the dependency array, we will have an unnecessary function call because we also update it in this function.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ autofillCountry, autofillState, options, setValue ] );

	return (
		<>
			<input
				onChange={ ( event ) =>
					setAutofillCountry( event.target.value )
				}
				value={ autofillCountry }
				name="country"
				type="text"
				className="woocommerce-select-control__autofill-input"
				tabIndex={ -1 }
				autoComplete="country"
			/>

			<input
				onChange={ ( event ) => setAutofillState( event.target.value ) }
				value={ autofillState }
				name="state"
				type="text"
				className="woocommerce-select-control__autofill-input"
				tabIndex={ -1 }
				autoComplete="address-level1"
			/>
		</>
	);
}

type StoreAddressProps = {
	getInputProps: ( key: string ) => FormInputProps;
	setValue: ( key: string, value: string ) => void;
};

/**
 * Store address fields.
 *
 * @param {Object}   props               Props for input components.
 * @param {Function} props.getInputProps Get input props.
 * @param {Function} props.setValue      Set value of the countryState input.
 * @return {Object} -
 */
export function StoreAddress( {
	getInputProps,
	setValue,
}: StoreAddressProps ): JSX.Element {
	const countryState = getInputProps( 'countryState' ).value;
	const { locale, hasFinishedResolution, countries, loadingCountries } =
		useSelect( ( select ) => {
			const {
				getLocale,
				getCountries,
				hasFinishedResolution: hasFinishedCountryResolution,
			} = select( COUNTRIES_STORE_NAME );
			return {
				locale: getLocale( countryState ),
				countries: getCountries(),
				loadingCountries:
					! hasFinishedCountryResolution( 'getCountries' ),
				hasFinishedResolution:
					hasFinishedCountryResolution( 'getLocales' ),
			};
		} );
	const countryStateOptions = useMemo(
		() => getCountryStateOptions( countries ),
		[ countries ]
	);
	const countryStateAutofill = useGetCountryStateAutofill(
		countryStateOptions,
		countryState,
		setValue
	);

	const isLocaleKey = ( key: string ): key is keyof typeof locale => {
		return locale.hasOwnProperty( key );
	};

	useEffect( () => {
		if ( locale ) {
			storeAddressFields.forEach( ( field ) => {
				const fieldKey = field
					.replace( /(address)Line([0-9])/, '$1$2' )
					.toLowerCase();
				const props = getInputProps( field );

				if (
					isLocaleKey( fieldKey ) &&
					locale[ fieldKey ]?.hidden &&
					props.value?.length > 0
				) {
					// Clear hidden field.
					setValue( field, '' );
				}
			} );
		}
	}, [ countryState, locale ] );
	if ( ! hasFinishedResolution || loadingCountries ) {
		return <Spinner />;
	}

	return (
		<div className="woocommerce-store-address-fields">
			<SelectControl
				label={ __( 'Country / Region', 'woocommerce' ) + ' *' }
				required
				autoComplete="new-password" // disable autocomplete and autofill
				getSearchExpression={ ( query: string ) => {
					return new RegExp(
						'(^' + query + '| — (' + query + '))',
						'i'
					);
				} }
				options={ countryStateOptions }
				excludeSelectedOptions={ false }
				showAllOnFocus
				isSearchable
				{ ...getInputProps( 'countryState' ) }
				controlClassName={ getInputProps( 'countryState' ).className }
			>
				{ countryStateAutofill }
			</SelectControl>

			{ ! locale?.address_1?.hidden && (
				<TextControl
					label={
						locale?.address_1?.label ||
						__( 'Address', 'woocommerce' )
					}
					autoComplete="address-line1"
					{ ...getInputProps( 'addressLine1' ) }
				/>
			) }

			{ ! locale?.postcode?.hidden && (
				<TextControl
					label={
						locale?.postcode?.label ||
						__( 'Post code', 'woocommerce' )
					}
					autoComplete="postal-code"
					{ ...getInputProps( 'postCode' ) }
				/>
			) }

			{ ! locale?.city?.hidden && (
				<TextControl
					label={ locale?.city?.label || __( 'City', 'woocommerce' ) }
					{ ...getInputProps( 'city' ) }
					autoComplete="address-level2"
				/>
			) }
		</div>
	);
}

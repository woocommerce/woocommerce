/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { escapeRegExp } from 'lodash';
import { Fragment } from '@wordpress/element';
import { useEffect, useMemo, useState } from 'react';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { SelectControl, TextControl } from '@woocommerce/components';

const { countries } = getSetting( 'dataEndpoints', { countries: {} } );
/**
 * Form validation.
 *
 * @param {Object} values Keyed values of all fields in the form.
 * @return {Object} Key value of fields and error messages, { myField: 'This field is required' }
 */
export function validateStoreAddress( values ) {
	const errors = {};

	if ( ! values.addressLine1.length ) {
		errors.addressLine1 = __( 'Please add an address', 'woocommerce-admin' );
	}
	if ( ! values.countryState.length ) {
		errors.countryState = __( 'Please select a country and state', 'woocommerce-admin' );
	}
	if ( ! values.city.length ) {
		errors.city = __( 'Please add a city', 'woocommerce-admin' );
	}
	if ( ! values.postCode.length ) {
		errors.postCode = __( 'Please add a post code', 'woocommerce-admin' );
	}

	return errors;
}

/**
 * Get all country and state combinations used for select dropdowns.
 *
 * @return {Object} Select options, { value: 'US:GA', label: 'United States - Georgia' }
 */
export function getCountryStateOptions() {
	const countryStateOptions = countries.reduce( ( acc, country ) => {
		if ( ! country.states.length ) {
			acc.push( {
				key: country.code,
				label: decodeEntities( country.name ),
			} );

			return acc;
		}

		const countryStates = country.states.map( state => {
			return {
				key: country.code + ':' + state.code,
				label: decodeEntities( country.name ) + ' -- ' + decodeEntities( state.name ),
			};
		} );

		acc.push( ...countryStates );

		return acc;
	}, [] );

	return countryStateOptions;
}

/**
 * Get the autofill countryState fields and set value from filtered options.
 *
 * @param {Array} options Array of filterable options.
 * @param {String} countryState The value of the countryState field.
 * @param {Function} setValue Set value of the countryState input.
 * @return {Object} React component.
 */
export function getCountryStateAutofill( options, countryState, setValue ) {
	const [ autofillCountry, setAutofillCountry ] = useState( '' );
	const [ autofillState, setAutofillState ] = useState( '' );

	useEffect(
		() => {
			let filteredOptions = [];
			const countrySearch = new RegExp( escapeRegExp( autofillCountry ), 'i' );
			if ( autofillState.length || autofillCountry.length ) {
				filteredOptions = options.filter( option => countrySearch.test( option.label ) );
			}
			if ( autofillCountry.length && autofillState.length ) {
				const stateSearch = new RegExp( escapeRegExp( autofillState.replace( /\s/g, '' ) ), 'i' );
				filteredOptions = filteredOptions.filter( option =>
					stateSearch.test( option.label.replace( '-', '' ).replace( /\s/g, '' ) )
				);

				if ( filteredOptions.length > 1 ) {
					let countryKeyOptions = [];
					countryKeyOptions = filteredOptions.filter( option => countrySearch.test( option.key ) );

					if ( countryKeyOptions.length > 0 ) {
						filteredOptions = countryKeyOptions;
					}
				}

				if ( filteredOptions.length > 1 ) {
					let stateKeyOptions = [];
					stateKeyOptions = filteredOptions.filter( option => stateSearch.test( option.key ) );

					if ( 1 === stateKeyOptions.length ) {
						filteredOptions = stateKeyOptions;
					}
				}
			}

			if ( 1 === filteredOptions.length && countryState !== filteredOptions[ 0 ].key ) {
				setValue( 'countryState', filteredOptions[ 0 ].key );
			}
		},
		[ autofillCountry, autofillState ]
	);

	return (
		<Fragment>
			<input
				onChange={ event => setAutofillCountry( event.target.value ) }
				value={ autofillCountry }
				name="country"
				type="text"
				className="woocommerce-select-control__autofill-input"
				tabIndex="-1"
				autoComplete="country"
			/>

			<input
				onChange={ event => setAutofillState( event.target.value ) }
				value={ autofillState }
				name="state"
				type="text"
				className="woocommerce-select-control__autofill-input"
				tabIndex="-1"
				autoComplete="address-level1"
			/>
		</Fragment>
	);
}

/**
 * Store address fields.
 *
 * @param {Object} props Props for input components.
 * @return {Object} -
 */
export function StoreAddress( props ) {
	const { getInputProps, setValue } = props;
	const countryStateOptions = useMemo( () => getCountryStateOptions(), [] );

	return (
		<div className="woocommerce-store-address-fields">
			<TextControl
				label={ __( 'Address line 1', 'woocommerce-admin' ) }
				required
				autoComplete="address-line1"
				{ ...getInputProps( 'addressLine1' ) }
			/>

			<TextControl
				label={ __( 'Address line 2 (optional)', 'woocommerce-admin' ) }
				required
				autoComplete="address-line2"
				{ ...getInputProps( 'addressLine2' ) }
			/>

			<SelectControl
				label={ __( 'Country / State', 'woocommerce-admin' ) }
				required
				options={ countryStateOptions }
				isSearchable
				{ ...getInputProps( 'countryState' ) }
				controlClassName={ getInputProps( 'countryState' ).className }
			>
				{ getCountryStateAutofill(
					countryStateOptions,
					getInputProps( 'countryState' ).value,
					setValue
				) }
			</SelectControl>

			<TextControl
				label={ __( 'City', 'woocommerce-admin' ) }
				required
				{ ...getInputProps( 'city' ) }
				autoComplete="address-level2"
			/>

			<TextControl
				label={ __( 'Post code', 'woocommerce-admin' ) }
				required
				autoComplete="postal-code"
				{ ...getInputProps( 'postCode' ) }
			/>
		</div>
	);
}

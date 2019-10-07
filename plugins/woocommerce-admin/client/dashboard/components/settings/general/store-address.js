/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { TextControl } from 'newspack-components';
import { useMemo } from 'react';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal depdencies
 */
import { SelectControl } from '@woocommerce/components';

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
 * Store address fields.
 *
 * @param {Object} props Props for input components.
 * @return {Object} -
 */
export function StoreAddress( props ) {
	const { getInputProps } = props;
	const countryStateOptions = useMemo( () => getCountryStateOptions(), [] );

	return (
		<div className="woocommerce-store-address-fields">
			<TextControl
				label={ __( 'Address line 1', 'woocommerce-admin' ) }
				required
				{ ...getInputProps( 'addressLine1' ) }
			/>

			<TextControl
				label={ __( 'Address line 2 (optional)', 'woocommerce-admin' ) }
				required
				{ ...getInputProps( 'addressLine2' ) }
			/>

			<SelectControl
				label={ __( 'Country / State', 'woocommerce-admin' ) }
				required
				options={ countryStateOptions }
				isSearchable
				{ ...getInputProps( 'countryState' ) }
			/>

			<TextControl
				label={ __( 'City', 'woocommerce-admin' ) }
				required
				{ ...getInputProps( 'city' ) }
			/>

			<TextControl
				label={ __( 'Post code', 'woocommerce-admin' ) }
				required
				{ ...getInputProps( 'postCode' ) }
			/>
		</div>
	);
}

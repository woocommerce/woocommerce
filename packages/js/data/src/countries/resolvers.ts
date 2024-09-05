/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import {
	getLocalesSuccess,
	getLocalesError,
	getCountriesSuccess,
	getCountriesError,
} from './actions';
import { NAMESPACE } from '../constants';
import { Locales, Country, GeolocationResponse } from './types';
import { STORE_NAME } from './constants';

const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

export function* getLocale() {
	yield resolveSelect( STORE_NAME, 'getLocales' );
}

export function* getLocales() {
	try {
		const url = NAMESPACE + '/data/countries/locales';
		const results: Locales = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		return getLocalesSuccess( results );
	} catch ( error ) {
		return getLocalesError( error );
	}
}

export function* getCountry() {
	yield resolveSelect( STORE_NAME, 'getCountries' );
}

export function* getCountries() {
	try {
		const url = NAMESPACE + '/data/countries';
		const results: Country[] = yield apiFetch( {
			path: url,
			method: 'GET',
		} );

		return getCountriesSuccess( results );
	} catch ( error ) {
		return getCountriesError( error );
	}
}

export const geolocate =
	() =>
	async ( { dispatch }: { dispatch: DispatchFromMap< typeof actions > } ) => {
		try {
			const url = `https://public-api.wordpress.com/geo/?v=${ new Date().getTime() }`;
			const response = await fetch( url, {
				method: 'GET',
			} );
			const result: GeolocationResponse = await response.json();
			dispatch.geolocationSuccess( result );
		} catch ( error ) {
			dispatch.geolocationError( error );
		}
	};

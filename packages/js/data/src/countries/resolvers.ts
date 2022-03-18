/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	getLocalesSuccess,
	getLocalesError,
	getCountriesSuccess,
	getCountriesError,
} from './actions';
import { NAMESPACE } from '../constants';
import { Locales, Country } from './types';
import { STORE_NAME } from './constants';
import { RestApiError } from '../types';

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
		return getLocalesError( error as RestApiError );
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
		return getCountriesError( error as RestApiError );
	}
}

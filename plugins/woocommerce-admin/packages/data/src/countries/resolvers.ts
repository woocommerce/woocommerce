/**
 * External dependencies
 */
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getLocalesSuccess, getLocalesError } from './actions';
import { NAMESPACE } from '../constants';
import { Locales } from './types';
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

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Locales, Country } from './types';

export function getLocalesSuccess( locales: Locales ) {
	return {
		type: TYPES.GET_LOCALES_SUCCESS,
		locales,
	};
}

export function getLocalesError( error: string ) {
	return {
		type: TYPES.GET_LOCALES_ERROR,
		error,
	};
}

export function getCountriesSuccess( countries: Country[] ) {
	return {
		type: TYPES.GET_COUNTRIES_SUCCESS,
		countries,
	};
}

export function getCountriesError( error: string ) {
	return {
		type: TYPES.GET_COUNTRIES_ERROR,
		error,
	};
}

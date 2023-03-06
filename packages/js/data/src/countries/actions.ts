/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Locales, Country } from './types';

export function getLocalesSuccess( locales: Locales ) {
	return {
		type: TYPES.GET_LOCALES_SUCCESS as const,
		locales,
	};
}

export function getLocalesError( error: unknown ) {
	return {
		type: TYPES.GET_LOCALES_ERROR as const,
		error,
	};
}

export function getCountriesSuccess( countries: Country[] ) {
	return {
		type: TYPES.GET_COUNTRIES_SUCCESS as const,
		countries,
	};
}

export function getCountriesError( error: unknown ) {
	return {
		type: TYPES.GET_COUNTRIES_ERROR as const,
		error,
	};
}

export type Action = ReturnType<
	| typeof getLocalesSuccess
	| typeof getLocalesError
	| typeof getCountriesSuccess
	| typeof getCountriesError
>;

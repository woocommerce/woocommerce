/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Locales } from './types';

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

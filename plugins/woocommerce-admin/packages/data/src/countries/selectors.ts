/**
 * Internal dependencies
 */
import { CountriesState } from './types';

export const getLocales = ( state: CountriesState ) => {
	return state.locales;
};

export const getLocale = ( state: CountriesState, id: string ) => {
	const country = id.split( ':' )[ 0 ];
	return state.locales[ country ];
};

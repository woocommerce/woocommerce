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

export const getCountries = ( state: CountriesState ) => {
	return state.countries;
};

export const getCountry = ( state: CountriesState, code: string ) => {
	return state.countries.find( ( country ) => country.code === code );
};

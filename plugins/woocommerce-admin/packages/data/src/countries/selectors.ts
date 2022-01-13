/**
 * Internal dependencies
 */
import { CountriesState } from './types';

export const getLocales = ( state: CountriesState ) => {
	return state.locales;
};

export const getLocale = ( state: CountriesState, country: string ) => {
	return state.locales[ country ];
};

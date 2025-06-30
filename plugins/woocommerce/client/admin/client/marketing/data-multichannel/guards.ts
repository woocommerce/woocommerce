/**
 * Internal dependencies
 */
import { ApiFetchError } from './types';

export const isObject = ( obj: unknown ): obj is Record< string, unknown > => {
	return !! obj && typeof obj === 'object';
};

export const isApiFetchError = ( obj: unknown ): obj is ApiFetchError => {
	return (
		isObject( obj ) && 'code' in obj && 'data' in obj && 'message' in obj
	);
};

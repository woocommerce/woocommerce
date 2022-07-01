/**
 * Internal dependencies
 */
import type { State } from './reducers';

export const getValidationError = ( state: State ) => {
	return ( errorId: string ) => state[ errorId ];
};
export const getValidationErrorId = ( state: State ) => {
	return ( errorId: string ) => {
		if ( ! state.hasOwnProperty( errorId ) || state[ errorId ].hidden ) {
			return;
		}
		return `validate-error-${ errorId }`;
	};
};
export const hasValidationErrors = ( state: State ) => {
	return Object.keys( state ).length > 0;
};

/**
 * Internal dependencies
 */
import { ACTION_TYPES as types } from './action-types';
import { ReturnOrGeneratorYieldUnion } from '../mapped-types';
import { FieldValidationStatus } from '../types';

export const setValidationErrors = (
	errors: Record< string, FieldValidationStatus >
) => ( {
	type: types.SET_VALIDATION_ERRORS,
	errors,
} );

export const clearAllValidationErrors = () => ( {
	type: types.CLEAR_ALL_VALIDATION_ERRORS,
} );

export const clearValidationError = ( error: string ) => ( {
	type: types.CLEAR_VALIDATION_ERROR,
	error,
} );

export const hideValidationError = ( error: string ) => ( {
	type: types.HIDE_VALIDATION_ERROR,
	error,
} );

export const showValidationError = ( error: string ) => ( {
	type: types.SHOW_VALIDATION_ERROR,
	error,
} );

export const showAllValidationErrors = () => ( {
	type: types.SHOW_ALL_VALIDATION_ERRORS,
} );

export type ValidationAction = ReturnOrGeneratorYieldUnion<
	| typeof setValidationErrors
	| typeof clearAllValidationErrors
	| typeof clearValidationError
	| typeof hideValidationError
	| typeof showValidationError
	| typeof showAllValidationErrors
>;

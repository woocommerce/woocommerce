/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import type {
	ValidationData,
	ValidationContextError,
} from '@woocommerce/type-defs/contexts';

/**
 * Internal dependencies
 */
import { useValidationContext } from '../providers/validation/';

/**
 * Custom hook for setting for adding errors to the validation system.
 */
export const useValidation = (): ValidationData => {
	const {
		hasValidationErrors,
		getValidationError,
		clearValidationError,
		hideValidationError,
		setValidationErrors,
	} = useValidationContext();
	const prefix = 'extensions-errors';

	return {
		hasValidationErrors,
		getValidationError: useCallback(
			( validationErrorId: string ) =>
				getValidationError( `${ prefix }-${ validationErrorId }` ),
			[ getValidationError ]
		),
		clearValidationError: useCallback(
			( validationErrorId: string ) =>
				clearValidationError( `${ prefix }-${ validationErrorId }` ),
			[ clearValidationError ]
		),
		hideValidationError: useCallback(
			( validationErrorId: string ) =>
				hideValidationError( `${ prefix }-${ validationErrorId }` ),
			[ hideValidationError ]
		),
		setValidationErrors: useCallback(
			( errorsObject: Record< string, ValidationContextError > ) =>
				setValidationErrors(
					Object.fromEntries(
						Object.entries(
							errorsObject
						).map( ( [ validationErrorId, error ] ) => [
							`${ prefix }-${ validationErrorId }`,
							error,
						] )
					)
				),
			[ setValidationErrors ]
		),
	};
};

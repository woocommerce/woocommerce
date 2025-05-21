/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import type {
	ValidationData,
	ValidationContextError,
} from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

/**
 * Custom hook for setting for adding errors to the validation system.
 */
export const useValidation = (): ValidationData => {
	const { clearValidationError, hideValidationError, setValidationErrors } =
		useDispatch( validationStore );

	const prefix = 'extensions-errors';

	const { hasValidationErrors, getValidationErrorSelector } = useSelect(
		( mapSelect ) => {
			const store = mapSelect( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				getValidationErrorSelector: store.getValidationError,
			};
		},
		[]
	);

	const getValidationError = useCallback(
		( validationErrorId: string ) =>
			getValidationErrorSelector( `${ prefix }-${ validationErrorId }` ),
		[ getValidationErrorSelector, prefix ]
	);

	return {
		hasValidationErrors,
		getValidationError,
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
						Object.entries( errorsObject ).map(
							( [ validationErrorId, error ] ) => [
								`${ prefix }-${ validationErrorId }`,
								error,
							]
						)
					)
				),
			[ setValidationErrors ]
		),
	};
};

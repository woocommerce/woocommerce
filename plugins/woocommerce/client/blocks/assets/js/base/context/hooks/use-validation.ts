/**
 * External dependencies
 */
import { useCallback, useMemo } from '@wordpress/element';
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

	const { hasValidationErrors, getValidationErrorFromStore } = useSelect(
		( mapSelect ) => {
			const store = mapSelect( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				getValidationErrorFromStore: store.getValidationError,
			};
		},
		[]
	);

	const getValidationError = useCallback(
		( validationErrorId: string ) =>
			getValidationErrorFromStore( `${ prefix }-${ validationErrorId }` ),
		[ getValidationErrorFromStore ]
	);

	const memoizedCallbacks = useMemo(
		() => ( {
			clearValidationError: ( validationErrorId: string ) =>
				clearValidationError( `${ prefix }-${ validationErrorId }` ),
			hideValidationError: ( validationErrorId: string ) =>
				hideValidationError( `${ prefix }-${ validationErrorId }` ),
			setValidationErrors: (
				errorsObject: Record< string, ValidationContextError >
			) =>
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
		} ),
		[ clearValidationError, hideValidationError, setValidationErrors ]
	);

	return {
		hasValidationErrors,
		getValidationError,
		...memoizedCallbacks,
	};
};

/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Signals that product saving is locked.
 *
 * ```typescript
 * // Sync validation
 * const validateTitle = useCallback((): boolean => {
 *  if ( product.title.length < 2 ) {
 *    return false;
 *  }
 *  return true;
 * }, [product]);
 * ...
 * const isTitleValid = useValidation( 'product/title', validateTitle );
 *
 * // Async validation
 * const validateSlug = useCallback(async (): Promise<boolean> => {
 *  return fetch(`.../${product.id}/validate-slug`)
 *    .then(response => response.json())
 *    .then(({ isValid }) => isValid)
 *    .catch(() => false);
 * }, [product])
 * ...
 * const isSlugValid = useValidation( 'product/slug', validateSlug );
 * ```
 *
 * @param lockName The namespace used to lock the product saving if validation fails.
 * @param validate The validator function.
 */
export function useValidation(
	lockName: string,
	validate: () => boolean | Promise< boolean >
): boolean | undefined {
	const [ isValid, setIsValid ] = useState< boolean | undefined >();
	const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );

	useEffect( () => {
		let validationResponse = validate();

		if ( typeof validationResponse === 'boolean' ) {
			validationResponse = Promise.resolve( validationResponse );
		}

		validationResponse
			.then( ( isValidationValid ) => {
				if ( isValidationValid ) {
					unlockPostSaving( lockName );
				} else {
					lockPostSaving( lockName );
				}
				setIsValid( isValidationValid );
			} )
			.catch( () => {
				lockPostSaving( lockName );
				setIsValid( false );
			} );
	}, [ lockName, validate, lockPostSaving, unlockPostSaving ] );

	return isValid;
}

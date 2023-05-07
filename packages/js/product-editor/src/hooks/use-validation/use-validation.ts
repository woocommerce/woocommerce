/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ValidationError } from './types';

/**
 * Signals that product saving is locked.
 *
 * @param  lockName The namespace used to lock the product saving if validation fails.
 * @param  validate The validator function.
 * @return The error message.
 */
export function useValidation(
	lockName: string,
	validate: () => ValidationError | Promise< ValidationError >
): ValidationError {
	const [ validationError, setValidationError ] =
		useState< ValidationError >();
	const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );

	useEffect( () => {
		let validationResponse = validate();

		if ( ! ( validationResponse instanceof Promise ) ) {
			validationResponse = Promise.resolve( validationResponse );
		}

		validationResponse
			.then( ( message ) => {
				if ( message ) {
					lockPostSaving( lockName );
				} else {
					unlockPostSaving( lockName );
				}
				setValidationError( message );
			} )
			.catch( ( error ) => {
				lockPostSaving( lockName );
				setValidationError( error.message ?? error );
			} );
	}, [ lockName, validate, lockPostSaving, unlockPostSaving ] );

	return validationError;
}

/**
 * External dependencies
 */
import { ErrorObject } from 'ajv';

/**
 * Internal dependencies
 */
import { ErrorDictionary, Schema } from './types';
import { getErrorMessage } from './get-error-message';

/**
 * Get a dictionary of errors for faster lookup and merge custom error messages
 * with original error objects.
 *
 * @param {ErrorObject} errors Ajv error objects.
 * @param {Schema}      schema Entity schema to pull custom error messages from.
 * @return {ErrorDictionary} Key value pairs of instance IDs with error objects.
 */
export function getErrorDictionary( errors: ErrorObject[], schema: Schema ) {
	return errors.reduce( ( acc, error ) => {
		const message = getErrorMessage( error, schema );
		acc[ error.instancePath.replace( '/', '' ) ] = {
			...error,
			message,
		};
		return acc;
	}, {} as ErrorDictionary );
}

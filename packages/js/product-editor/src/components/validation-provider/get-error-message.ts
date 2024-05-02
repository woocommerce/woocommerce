/**
 * External dependencies
 */
import { ErrorObject } from 'ajv';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { Schema } from './types';

/**
 * Get a custom error message based on the error instance and keyword
 * if one exists in the schema.
 *
 * @param {ErrorObject} error  Ajv error object.
 * @param {Schema}      schema Schema to pull custom error message from.
 * @return {string} Error message.
 */
export function getErrorMessage( error: ErrorObject, schema: Schema ) {
	const path = error.schemaPath.replace( '#/', '' ).split( '/' );
	path.pop();
	const property = get( schema, path );

	if ( property.errorMessage && property.errorMessage[ error.keyword ] ) {
		return property.errorMessage[ error.keyword ] as string;
	}

	if ( typeof property.errorMessage === 'string' ) {
		return property.errorMessage as string;
	}

	return ( error.instancePath.replace( '/', '' ) +
		' ' +
		error.message ) as string;
}

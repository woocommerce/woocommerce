/**
 * External dependencies
 */
import Ajv from 'ajv';
import addFormats from 'ajv-formats';
import addErrors from 'ajv-errors';

const ajv = new Ajv( {
	allErrors: true,
	$data: true,
	validateSchema: true,
	validateFormats: false,
	strictSchema: false,
	strict: false,
	messages: true,
} );

addFormats( ajv );
addErrors( ajv );

// Add type declaration for window.schemaParser
declare global {
	interface Window {
		schemaParser: typeof ajv;
	}
}

window.schemaParser = ajv;

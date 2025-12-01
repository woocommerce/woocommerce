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
	validateFormats: true,
	strictSchema: false,
	strict: false,
	messages: true,
} );

// Override email format to match PHP's FILTER_VALIDATE_EMAIL.
ajv.addFormat(
	'email',
	/^(?!.*[.]{2})[a-zA-Z0-9](?:[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]*[a-zA-Z0-9])?@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/i
);

addFormats( ajv, {
	mode: 'fast',
	formats: [ 'date', 'time', 'uri' ],
	keywords: true,
} );
addErrors( ajv );

// Add type declaration for window.schemaParser
declare global {
	interface Window {
		schemaParser: typeof ajv;
	}
}

window.schemaParser = ajv;

export { ajv as schemaParser };

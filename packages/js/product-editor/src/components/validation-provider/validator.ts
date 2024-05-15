/**
 * External dependencies
 */
import Ajv, { _, KeywordCxt } from 'ajv';

export const validator = new Ajv( {
	strict: false,
	allErrors: true,
	coerceTypes: true,
	$data: true,
} );

validator.addKeyword( {
	keyword: 'exclusiveMinimumCoerced',
	type: 'string',
	$data: true,
	code( cxt: KeywordCxt ) {
		const { data, schemaCode } = cxt;

		cxt.fail$data(
			_`${ data } !== '' && ${ schemaCode } !== '' && Number( ${ data } ) <= Number( ${ schemaCode } )`
		);
	},
} );

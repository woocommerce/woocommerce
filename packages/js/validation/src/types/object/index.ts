/**
 * Internal dependencies
 */
import { ObjectSchema, Data, ValidationError } from '../../types';
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';
import { validateKeywords } from '../../utils/validate-keywords';
import { required } from '../../keywords/required';
import { parse } from './parse';

export function parseObject( schema: ObjectSchema, data: Data, path: string ) {
    // Throw early if we're not dealing with an object.
    if ( typeof data !== 'object' || data == null ) {
        throw [ getInvalidTypeError( 'object', path ) ];
    }

    const { parsed, errors } = parse( data, schema, path );

    const keywordErrors = validateKeywords< object, ObjectSchema >(
        [
            required,
        ],
        parsed,
        schema,
        path
    );

    return {
        parsed,
        errors: [ ...errors, ...keywordErrors ],
    };
}
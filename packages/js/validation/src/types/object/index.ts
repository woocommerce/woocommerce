/**
 * Internal dependencies
 */
import { ObjectSchema, Data, ValidationError } from '../../types';
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';
import { validateKeywords } from '../../utils/validate-keywords';
import { required } from '../../keywords/required';
import { parse } from './parse';

export function parseObject( schema: ObjectSchema, object: Data, path: string, data: Data ) {
    // Throw early if we're not dealing with an object.
    if ( typeof object !== 'object' || object == null ) {
        throw [ getInvalidTypeError( 'object', path ) ];
    }

    const { parsed, errors } = parse( object, schema, path, data );

    const keywordErrors = validateKeywords< object, ObjectSchema >(
        {
            required,
        },
        parsed,
        schema,
        path,
        data
    );

    return {
        parsed,
        errors: [ ...errors, ...keywordErrors ],
    };
}
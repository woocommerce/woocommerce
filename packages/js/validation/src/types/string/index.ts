/**
 * Internal dependencies
 */
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { StringSchema, ValidationError } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { validateType } from '../../validators/validate-type';

export function parseString( schema: StringSchema, data: unknown, path: string ) {
    validateType( data, 'string', path );

    // @todo Add coercion from numbers.
    const parsed = data as string;

    validateKeywords< string, StringSchema >(
        [
            maxLength,
            minLength,
        ],
        parsed,
        schema,
        path
    );

    return parsed;
}
/**
 * Internal dependencies
 */
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { StringSchema, ValidationError } from '../../types';
import { validateType } from '../../validators/validate-type';

export function parseString( schema: StringSchema, data: unknown, path: string ) {
    validateType( data, 'string', path );

    // @todo Add coercion from numbers.
    const parsed = data as string;
    const errors = [] as ValidationError[];
    const keywords = [
        maxLength,
        minLength,
    ];

    for ( const keyword of keywords ) {
        try {
            keyword( parsed, schema, path );
        } catch (e) {
            errors.push( e as ValidationError );
        }
    }

    if ( errors.length ) {
        throw errors;
    }

    return parsed;
}
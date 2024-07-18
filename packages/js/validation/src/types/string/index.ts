/**
 * Internal dependencies
 */
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { StringSchema } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { parse } from './parse';

export function parseString( schema: StringSchema, data: unknown, path: string ) {
    const parsed = parse( data, path );

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
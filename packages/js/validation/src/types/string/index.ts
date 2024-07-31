/**
 * Internal dependencies
 */
import { format } from '../../keywords/format';
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { pattern } from '../../keywords/pattern';
import { StringSchema } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { parse } from './parse';

export function parseString( schema: StringSchema, data: unknown, path: string ) {
    const { parsed, errors } = parse( data, path );

    const keywordErrors = validateKeywords< string, StringSchema >(
        [
            format,
            maxLength,
            minLength,
            pattern,
        ],
        parsed,
        schema,
        path
    );

    return {
        errors: [ ...errors, ...keywordErrors ],
        parsed
    };
}
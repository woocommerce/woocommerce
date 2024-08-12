/**
 * Internal dependencies
 */
import { format } from '../../keywords/format';
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { pattern } from '../../keywords/pattern';
import { Data, StringSchema } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { parse } from './parse';

export function parseString( schema: StringSchema, string: unknown, path: string, data: Data ) {
    const { parsed, errors } = parse( string, path );

    const keywordErrors = validateKeywords< string, StringSchema >(
        {
            format,
            maxLength,
            minLength,
            pattern,
        },
        parsed,
        schema,
        path,
        data
    );

    return {
        errors: [ ...errors, ...keywordErrors ],
        parsed
    };
}
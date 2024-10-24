/**
 * Internal dependencies
 */
import { format } from '../../keywords/format';
import { maxLength } from '../../keywords/max-length';
import { minLength } from '../../keywords/min-length';
import { pattern } from '../../keywords/pattern';
import { Context, ParsedContext, StringSchema } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { validateFilters } from '../../utils/validate-filters';
import { parse } from './parse';

export function parseString( context: Context< StringSchema > ) {
    const { value, path } = context;
    const { parsed, errors } = parse( value, path );
    const parsedContext: ParsedContext< StringSchema, string > = { ...context, parsed };

    const keywordErrors = validateKeywords< StringSchema, string >(
        {
            format,
            maxLength,
            minLength,
            pattern,
        },
        parsedContext
    );

    const filterErrors = validateFilters( parsedContext );

    return {
        errors: [ ...errors, ...keywordErrors, ...filterErrors ],
        parsed
    };
}
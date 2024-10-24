/**
 * Internal dependencies
 */
import { ObjectSchema, Data, Context, ParsedContext } from '../../types';
import { validateKeywords } from '../../utils/validate-keywords';
import { validateFilters } from '../../utils/validate-filters';
import { required } from '../../keywords/required';
import { parse } from './parse';



export function parseObject( context: Context< ObjectSchema > ) {
    const { parsed, errors } = parse( context );
    const parsedContext = {
        ...context,
        parsed
    } as ParsedContext< ObjectSchema, Data >;

    const keywordErrors = validateKeywords< ObjectSchema, object >(
        {
            required,
        },
        parsedContext
    );

    const filterErrors = validateFilters( parsedContext );

    return {
        parsed,
        errors: [ ...errors, ...keywordErrors, ...filterErrors ],
    };
}
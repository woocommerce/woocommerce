/**
 * Internal dependencies
 */
import { Data, ParsedContext, StringSchema } from "../types";
import { ValidationError } from "../types";

export function maxLength( context: ParsedContext< StringSchema, string >, operand: Data ): ValidationError[] {
    const { path, parsed } = context;

    if ( typeof operand !== 'number' ) {
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'maxLength',
                message: `maxLength must be a number`,
                path: path,
            }
         ] as ValidationError[];
    }

    if ( parsed.length > operand ) {
        return [
            {
                code: 'max_length',
                keyword: 'maxLength',
                message: `${path} must not be more than ${operand} characters in length`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
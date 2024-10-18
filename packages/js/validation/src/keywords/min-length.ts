/**
 * Internal dependencies
 */
import { ParsedContext, StringSchema, ValidationError } from "../types";

export function minLength( context: ParsedContext< StringSchema, string >, operand: unknown ): ValidationError[] {
    const { path, parsed } = context;

    if ( typeof operand !== 'number' ) {
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'minLength',
                message: `minLength must be a number`,
                path: path,
            }
        ] as ValidationError[];
    }

    if ( parsed.length < operand ) {
        return [
            {
                code: 'min_length',
                keyword: 'minLength',
                message: `${path} must be at least ${operand} characters in length`,
                path: path,
            }
         ] as ValidationError[];
    }

    return [];
}
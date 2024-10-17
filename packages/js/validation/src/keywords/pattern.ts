/**
 * Internal dependencies
 */
import { Data, ParsedContext, StringSchema } from "../types";
import { ValidationError } from "../types";

export function pattern( context: ParsedContext< StringSchema, string >, operand: Data ): ValidationError[] {
    const { path, parsed } = context;

    if ( typeof operand !== 'string' ) {
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'pattern',
                message: `pattern must be a valid regex string`,
                path: path,
            }
         ] as ValidationError[];
    }

    if ( ! parsed.match( operand ) ) {
        return [
            {
                code: 'pattern',
                keyword: 'pattern',
                message: `${path} must match the regex pattern ${operand}`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
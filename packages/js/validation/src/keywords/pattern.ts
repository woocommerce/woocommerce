/**
 * Internal dependencies
 */
import { Data } from "../types";
import { ValidationError } from "../types";

export function pattern( string: string, path: string, operand: Data ): ValidationError[] {
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

    if ( ! string.match( operand ) ) {
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
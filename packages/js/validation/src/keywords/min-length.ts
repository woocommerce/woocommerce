/**
 * Internal dependencies
 */
import { ValidationError } from "../types";

export function minLength( string: string, path: string, operand: unknown ): ValidationError[] {
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

    if ( string.length < operand ) {
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
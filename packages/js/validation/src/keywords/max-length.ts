/**
 * Internal dependencies
 */
import { Data } from "../types";
import { ValidationError } from "../types";

export function maxLength( string: string, path: string, operand: Data ): ValidationError[] {
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

    if ( string.length > operand ) {
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
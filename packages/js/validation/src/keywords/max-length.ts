/**
 * Internal dependencies
 */
import { StringSchema } from "../types";
import { ValidationError } from "../types";

export function maxLength( data: string, schema: StringSchema, path: string ): ValidationError[] {
    if ( ! schema.hasOwnProperty( 'maxLength' ) ) {
        return [];
    }

    if ( typeof schema.maxLength !== 'number' ) {
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'maxLength',
                message: `maxLength must be a number`,
                path: path,
            }
         ] as ValidationError[];
    }

    if ( data.length > schema.maxLength ) {
        return [
            {
                code: 'max_length',
                keyword: 'maxLength',
                message: `${path} must not be more than ${schema.maxLength} characters in length`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
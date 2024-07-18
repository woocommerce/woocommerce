/**
 * Internal dependencies
 */
import { StringSchema } from "../types";
import { ValidationError } from "../types";

export function maxLength( data: string, schema: StringSchema, path: string ) {
    if ( ! schema.hasOwnProperty( 'maxLength' ) ) {
        return;
    }

    if ( typeof schema.maxLength !== 'number' ) {
        throw {
            code: 'invalid_keyword_value',
            keyword: 'maxLength',
            message: `maxLength must be a number`,
            path: path,
        } as ValidationError;
    }

    if ( data.length > schema.maxLength ) {
        throw {
            code: 'max_length',
            keyword: 'maxLength',
            message: `${path} must not be more than ${schema.maxLength} characters in length`,
            path: path,
        } as ValidationError;
    }
}
/**
 * Internal dependencies
 */
import { StringSchema } from "../types";
import { ValidationError } from "../types";

export function minLength( data: string, schema: StringSchema, path: string ) {
    if ( ! schema.hasOwnProperty( 'minLength' ) ) {
        return;
    }

    if ( typeof schema.minLength !== 'number' ) {
        throw {
            code: 'invalid_keyword_value',
            keyword: 'minLength',
            message: `minLength must be a number`,
            path: path,
        } as ValidationError;
    }

    if ( data.length < schema.minLength ) {
        throw {
            code: 'min_length',
            keyword: 'minLength',
            message: `${path} must be at least ${schema.minLength} characters in length`,
            path: path,
        } as ValidationError;
    }
}
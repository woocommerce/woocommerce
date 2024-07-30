/**
 * Internal dependencies
 */
import { StringSchema } from "../types";
import { ValidationError } from "../types";

export function pattern( data: string, schema: StringSchema, path: string ): ValidationError[] {
    if ( ! schema.hasOwnProperty( 'pattern' ) ) {
        return [];
    }

    if ( typeof schema.pattern !== 'string' ) {
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'pattern',
                message: `pattern must be a valid regex string`,
                path: path,
            }
         ] as ValidationError[];
    }

    if ( ! data.match( schema.pattern ) ) {
        return [
            {
                code: 'pattern',
                keyword: 'pattern',
                message: `${path} must match the regex pattern ${schema.pattern}`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
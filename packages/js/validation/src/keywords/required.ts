/**
 * Internal dependencies
 */
import { ERROR_CODES } from "../errors/constants";
import { Data, ObjectSchema, ParsedContext } from "../types";
import { ValidationError } from "../types";

export function required( context: ParsedContext< ObjectSchema, object >, operand: Data ): ValidationError[] {
    const { parsed, path } = context;

    if ( ! Array.isArray( operand ) ) {
        return [];
    }

    const errors = [] as ValidationError[];

    if ( operand ) {
        for ( const requiredProperty of operand ) {
            const propertyPath = `${path}/${requiredProperty}`;
            if ( ! parsed.hasOwnProperty( requiredProperty ) ) {
                errors.push( {
                    code: ERROR_CODES.MISSING_REQUIRED,
                    keyword: 'required',
                    message: `${propertyPath} is a required property`,
                    path: propertyPath,
                } );
            }
        }
    }

    console.log(errors);

    return errors;
}
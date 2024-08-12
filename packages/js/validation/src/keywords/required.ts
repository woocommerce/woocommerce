/**
 * Internal dependencies
 */
import { ERROR_CODES } from "../errors/constants";
import { Data } from "../types";
import { ValidationError } from "../types";

export function required( object: object, path: string, operand: Data ): ValidationError[] {
    if ( ! Array.isArray( operand ) ) {
        return [];
    }

    const errors = [] as ValidationError[];

    if ( operand ) {
        for ( const requiredProperty of operand ) {
            const propertyPath = `${path}/${requiredProperty}`;
            if ( ! object.hasOwnProperty( requiredProperty ) ) {
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
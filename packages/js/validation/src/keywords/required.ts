/**
 * Internal dependencies
 */
import { ERROR_CODES } from "../errors/constants";
import { ObjectSchema } from "../types";
import { ValidationError } from "../types";

export function required( data: object, schema: ObjectSchema, path: string ): ValidationError[] {
    if ( ! schema.hasOwnProperty( 'required' ) || ! Array.isArray( schema.required ) ) {
        return [];
    }

    const errors = [] as ValidationError[];

    if ( schema.required ) {
        for ( const requiredProperty of schema.required ) {
            const propertyPath = `${path}/${requiredProperty}`;
            if ( ! data.hasOwnProperty( requiredProperty ) ) {
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
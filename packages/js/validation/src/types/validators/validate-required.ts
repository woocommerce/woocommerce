/**
 * Internal dependencies
 */
import { ObjectSchema, ValidationError } from '../../types';

export function validateRequired( data: object, schema: ObjectSchema, path: string ): ValidationError[] {
    const errors = [] as ValidationError[];

    if ( schema.required ) {
        for ( const requiredProperty of schema.required ) {
            const propertyPath = `${path}/${requiredProperty}`;
            if ( ! data.hasOwnProperty( requiredProperty ) ) {
                errors.push( {
                    code: 'required_property',
                    keyword: 'required',
                    message: `${propertyPath} is a required property`,
                    path: propertyPath,
                } );
            }
        }
    }

    return errors;
};
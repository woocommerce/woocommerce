/**
 * Internal dependencies
 */
import { Context, Data, ObjectSchema, StringSchema, ValidationError } from "../../types";
import { parseString } from '../string';
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';

export function parse( context: Context< ObjectSchema > ) {
    const { path, schema, value } = context;
    const parsed = {} as Data;
    let errors = [] as ValidationError[];

    // Throw early if we're not dealing with an object.
    if ( typeof value !== 'object' || value == null ) {
        throw [ getInvalidTypeError( 'object', path ) ];
    }

    const properties = Object.keys(  schema.properties ) as string[];
    for ( const property of properties ) {
        const propertySchema = schema.properties[ property ];
        const propertyPath = `${path}/${property}`;
        const propertyContext = {
            ...context,
            schema: propertySchema,
            value: value[ property as keyof typeof value ],
            path: propertyPath,
            data: context.data,
        };

        switch ( propertySchema.type ) {
            case 'string':
                const { parsed: parsedString, errors: stringErrors } = parsed[ property ] = parseString( propertyContext as Context< StringSchema > );
                parsed[ property ] = parsedString;
                errors = [ ...errors,  ...stringErrors as ValidationError[] ]; 
        }
    }

    return {
        parsed,
        errors
    };
}
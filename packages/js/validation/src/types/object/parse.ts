/**
 * Internal dependencies
 */
import { Data, ObjectSchema, ValidationError } from "../../types";
import { parseString } from '../string';

export function parse( object: Data, schema: ObjectSchema, path: string, data: Data ) {
    const parsed = {} as Data;
    let errors = [] as ValidationError[];

    const properties = Object.keys(  schema.properties ) as string[];
    for ( const property of properties ) {
        const propertySchema = schema.properties[ property ];
        const propertyPath = `${path}/${property}`;

        switch ( propertySchema.type ) {
            case 'string':
                const { parsed: parsedString, errors: stringErrors } = parsed[ property ] = parseString( propertySchema, object[ property ], propertyPath, data );
                parsed[ property ] = parsedString;
                errors = [ ...errors,  ...stringErrors as ValidationError[] ]; 
        }
    }

    return {
        parsed,
        errors
    };
}
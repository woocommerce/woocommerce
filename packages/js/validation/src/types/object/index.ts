/**
 * Internal dependencies
 */
import { ObjectSchema, Data, ValidationError } from '../../types';
import { parseString } from '../string';
import { validateType } from '../../validators/validate-type'
import { validateRequired } from '../../validators/validate-required'

export function parseObject( schema: ObjectSchema, data: Data, path: string ) {
    validateType( data, 'object', path );

    const parsed = {} as Data;
    let errors = [] as ValidationError[];

    console.log(data);
    const properties = Object.keys(  schema.properties ) as string[];
    for ( const property of properties ) {
        const propertySchema = schema.properties[ property ];
        const propertyPath = `${path}/${property}`;

        switch ( propertySchema.type ) {
            case 'string':
                try {
                    parsed[ property ] = parseString( propertySchema, data[ property ], propertyPath );
                } catch( e ) {
                    errors.push( e as ValidationError ); 
                }
        }
    }

    try {
        validateRequired( data, schema, path )
    } catch ( e ) {
        errors = [ ...errors, ...e as ValidationError[] ];
    }

    if ( errors.length ) {
        throw errors;
    }

    return parsed;
}
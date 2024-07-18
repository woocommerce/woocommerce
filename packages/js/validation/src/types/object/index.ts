/**
 * Internal dependencies
 */
import { ObjectSchema, Data, ValidationError } from '../../types';
import { parseString } from '../string';
import { validateType } from '../validators/validate-type'

export function parseObject( schema: ObjectSchema, data: Data, path: string ) {
    validateType( data, 'object', path );

    const parsed = {} as Data;
    const errors = [] as ValidationError[];

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

        // @todo check if in required array
    }

    if ( errors.length ) {
        throw errors;
    }

    return parsed;
}
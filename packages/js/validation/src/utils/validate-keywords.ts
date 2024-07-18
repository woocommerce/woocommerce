/**
 * Internal dependencies
 */
import { KeywordInterface } from "../types";
import { ValidationError } from "../types";

export function validateKeywords< DataType, SchemaType >( keywords: KeywordInterface< DataType, SchemaType >[], data: DataType, schema: SchemaType, path: string ) {
    const errors = [] as ValidationError[];

    for ( const keyword of keywords ) {
        try {
            keyword( data, schema, path );
        } catch (e) {
            errors.push( e as ValidationError );
        }
    }

    if ( errors.length ) {
        throw errors;
    }
}
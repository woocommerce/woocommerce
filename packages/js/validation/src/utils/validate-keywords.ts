/**
 * Internal dependencies
 */
import { Data, KeywordInterface } from "../types";
import { ValidationError, Property } from "../types";
import { getFullPath } from './get-full-path';
import { getPropertyByPath } from './get-property-by-path';

export function validateKeywords< DataType, SchemaType extends Property >( keywords: { [ keyword: string ] : KeywordInterface< DataType > }, datum: DataType, schema: SchemaType, path: string, data: Data ) {
    let errors = [] as ValidationError[];

    for ( const keyword in keywords ) {
        if ( ! schema.hasOwnProperty( keyword ) ) {
            continue;
        }
    
        let operand = schema[ keyword ];
        if ( schema[ keyword ] && typeof schema[ keyword ] === 'object' && schema[ keyword ].$data ) {
            const fullPath = getFullPath( path, schema.minLength.$data );
            operand = getPropertyByPath( data, fullPath );
        }

        const keywordCallback = keywords[ keyword ];
        const keywordErrors = keywordCallback( datum, path, operand );
        errors = [ ...errors, ...keywordErrors as ValidationError[] ];
    }

    return errors;
}
/**
 * Internal dependencies
 */
import { KeywordInterface, ParsedContext } from "../types";
import { ValidationError, Property } from "../types";
import { getFullPath } from './get-full-path';
import { getPropertyByPath } from './get-property-by-path';

export function validateKeywords< SchemaType extends Property, DataType >( keywords: { [ keyword: string ] : KeywordInterface< SchemaType, DataType > }, context: ParsedContext< SchemaType, DataType > ) {
    const { schema, path, data } = context;
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
        const keywordErrors = keywordCallback( context, operand );
        errors = [ ...errors, ...keywordErrors as ValidationError[] ];
    }

    return errors;
}
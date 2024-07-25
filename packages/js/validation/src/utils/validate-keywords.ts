/**
 * Internal dependencies
 */
import { KeywordInterface } from "../types";
import { ValidationError } from "../types";

export function validateKeywords< DataType, SchemaType >( keywords: KeywordInterface< DataType, SchemaType >[], data: DataType, schema: SchemaType, path: string ) {
    let errors = [] as ValidationError[];

    for ( const keyword of keywords ) {
        const keywordErrors = keyword( data, schema, path );
        errors = [ ...errors, ...keywordErrors as ValidationError[] ];
    }

    return errors;
}
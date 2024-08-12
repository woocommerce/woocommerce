/**
 * Internal dependencies
 */
import { Data, KeywordInterface } from "../types";
import { ValidationError, Property } from "../types";

function getFullPath( currentPath: string, relativePointer: string ) {
    // Remove the initial `/` before the path and split.
    const pathParts = currentPath.slice( 1 ).split('/');
    const refParts = relativePointer.split('/');
    // Pop off the first number from the pointer.
    const integerPrefix = refParts.shift();

    for ( let i = 0; i < Number( integerPrefix ); i++ ) {
        pathParts.pop();
    }

    return [ ...pathParts, ...refParts ];
}

const getPropertyByPath = ( obj: any, keys: string[] ) => {
    return keys.reduce(
        ( acc, key ) => {
            if ( typeof acc === 'object' ) {
                return acc?.[ key ] ?? undefined
            }
            return undefined;
        }, 
        obj
    );
};

export function validateKeywords< DataType, SchemaType extends Property >( keywords: { [ keyword: string ] : KeywordInterface< DataType, SchemaType > }, datum: DataType, schema: SchemaType, path: string, data: Data ) {
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
/**
 * Internal dependencies
 */
import { ValidationError, ParsedContext } from '../types';

export function validateFilters< SchemaType, ParsedType >( context: ParsedContext< SchemaType, ParsedType > ) {
    const { filters, path, parsed, data } = context;

    let filterErrors = [] as ValidationError[];
    
    filters.forEach( ( filter ) => {
        filterErrors = [ ...filterErrors, ...filter( context ) ];
    } );

    return filterErrors;
}
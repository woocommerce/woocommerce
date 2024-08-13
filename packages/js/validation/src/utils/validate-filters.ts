/**
 * Internal dependencies
 */
import { Data, Validator, ValidationError } from '../types';

export function validateFilters( parsed: any, path: string, data: Data, filters: Validator< any >[] ) {
    let filterErrors = [] as ValidationError[];
    
    filters.forEach( ( filter ) => {
        filterErrors = [ ...filterErrors, ...filter( parsed, path, data ) ];
    } );

    return filterErrors;
}
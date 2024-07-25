/**
 * Internal dependencies
 */
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';
import { ValidationError } from '../../types';

export function parse( data: unknown, path: string ) {
    let errors = [] as ValidationError[];

    // @todo Handle strict cases vs coercion.
    if ( typeof data !== 'string' && typeof data !== 'number' ) {
        errors.push( getInvalidTypeError( 'string', path ) );
    }

    return {
        errors,
        parsed: errors.length ? '' : String( data ),
    };
}
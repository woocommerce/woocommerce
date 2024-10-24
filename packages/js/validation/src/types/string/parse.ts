/**
 * Internal dependencies
 */
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';
import { Data, ValidationError } from '../../types';

export function parse( string: unknown, path: string ) {
    let errors = [] as ValidationError[];

    // @todo Handle strict cases vs coercion.
    if ( typeof string !== 'string' && typeof string !== 'number' ) {
        errors.push( getInvalidTypeError( 'string', path ) );
    }

    return {
        errors,
        parsed: errors.length ? '' : String( string ),
    };
}
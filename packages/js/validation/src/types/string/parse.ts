/**
 * Internal dependencies
 */
import { getInvalidTypeError } from '../../errors/get-invalid-type-error';

export function parse( data: unknown, path: string ) {
    if ( typeof data !== 'string' && typeof data !== 'number' ) {
        throw [ getInvalidTypeError( 'string', path ) ];
    }

    return String( data );
}
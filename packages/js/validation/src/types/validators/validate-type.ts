/**
 * Internal dependencies
 */
import { ValidationError } from '../../types';

export function validateType( data: unknown, type: string, path: string ) {
    if ( typeof data !== type ) {
        throw {
            code: 'invalid_type',
            keyword: 'type',
            message: `${path} is not a valid ${type}`,
            path: path,
        } as ValidationError;
    }
};
/**
 * Internal dependencies
 */
import { StringSchema, Data, ValidationError } from '../../types';
import { validateType } from '../validators/validate-type';

export function parseString( schema: StringSchema, data: unknown, path: string ) {
    validateType( data, 'string', path );

    return data;
}
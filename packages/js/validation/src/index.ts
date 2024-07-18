/**
 * Internal dependencies
 */
import { ObjectSchema, Data } from "./types";
import { parseObject } from './types/object';

export function parse( schema: ObjectSchema, data: Data ) {
    try {
        return parseObject( schema, data, '' );
    } catch ( e ) {
        return {
            errors: e
        };
    }
}

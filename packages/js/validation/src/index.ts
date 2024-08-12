/**
 * Internal dependencies
 */
import { ObjectSchema, Data } from "./types";
import { parseObject } from './types/object';

export function parse( schema: ObjectSchema, data: Data ) {
    return parseObject( schema, data, '', data );
}

/**
 * Internal dependencies
 */
import { ObjectSchema, Data, Validator } from "./types";
import { parseObject } from './types/object';


export function validator() {
    const filters: Validator<any>[] = [];

    return {
        addFilter< DataType >( val: Validator< DataType > ) {
            filters.push( val );
        },
        filters: [],
        parse: ( schema: ObjectSchema, data: Data ) => {
            return parseObject( schema, data, '', data, filters );
        },
    }
};

export * from './types';

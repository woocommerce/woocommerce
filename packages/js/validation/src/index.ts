/**
 * Internal dependencies
 */
import { ObjectSchema, Data, Validator } from "./types";
import { parseObject } from './types/object';


export function validator() {
    const filters: Validator[] = [];

    return {
        addFilter< SchemaType, DataType >( val: Validator ) {
            filters.push( val );
        },
        filters: [],
        parse: ( schema: ObjectSchema, data: Data ) => {
            const context = {
                schema,
                value: data,
                path:'',
                data,
                filters
            }
            return parseObject( context );
        },
    }
};

export * from './types';

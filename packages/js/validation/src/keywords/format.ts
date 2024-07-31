/**
 * Internal dependencies
 */
import { StringSchema } from "../types";
import { ValidationError } from "../types";

type FormatValidators = {
    [ key: string ]: ( data: string ) => boolean;
}

const formatValidators: FormatValidators = {
    'email': ( data: string ) => {
        return !! data.match( "[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?" );
    },
    'uri': ( data: string ) => {
        return !! data.match( "^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?" );
    }
};

export function format( data: string, schema: StringSchema, path: string ): ValidationError[] {
    if ( ! schema.hasOwnProperty( 'format' ) ) {
        return [];
    }

    if ( typeof schema.format !== 'string' || ! formatValidators.hasOwnProperty( schema.format ) ) {
        const formatOptions = Object.keys( formatValidators ).join(', ');
        return [
            {
                code: 'invalid_keyword_value',
                keyword: 'format',
                message: `format must be one of: ${formatOptions}`,
                path: path,
            }
         ] as ValidationError[];
    }

    const isValid = formatValidators[ schema.format ];

    if ( ! isValid( data ) ) {
        return [
            {
                code: 'format',
                keyword: 'format',
                message: `${path} is not a valid "${schema.format}" format`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
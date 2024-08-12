/**
 * Internal dependencies
 */
import { Data } from "../types";
import { ValidationError } from "../types";

type FormatValidators = {
    [ key: string ]: ( data: string ) => boolean;
}

const formatValidators: FormatValidators = {
    'email': ( data: string ) => {
        return !! data.match( "[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?" );
    },
    'uri': ( data: string ) => {
        try {
            new URL( data );
            return true;
        } catch {
            return false;
        }
    }
};

export function format( string: string, path: string, operand: Data ): ValidationError[] {
    if ( typeof operand !== 'string' || ! formatValidators.hasOwnProperty( operand ) ) {
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

    const isValid = formatValidators[ operand ];

    if ( ! isValid( string ) ) {
        return [
            {
                code: 'format',
                keyword: 'format',
                message: `${path} is not a valid "${operand}" format`,
                path: path,
            }
        ] as ValidationError[];
    }

    return [];
}
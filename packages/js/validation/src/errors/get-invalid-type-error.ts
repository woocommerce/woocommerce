/**
 * Internal dependencies
 */
import { ValidationError } from "../types";
import { ERROR_CODES } from "./constants";

export function getInvalidTypeError( type: string, path: string ) {
    return {
        code: ERROR_CODES.INVALID_TYPE,
        keyword: 'type',
        message: `${path} is not a valid ${type}`,
        path: path,
    } as ValidationError;
}

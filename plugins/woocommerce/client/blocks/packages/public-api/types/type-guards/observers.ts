/**
 * Internal dependencies
 */
import { FieldValidationStatus, isObject, objectHasProp } from '../';

export enum responseTypes {
	SUCCESS = 'success',
	FAIL = 'failure',
	ERROR = 'error',
}

export interface ObserverResponse extends Record< string, unknown > {
	type: responseTypes;
	errorMessage?: string;
	context?: string;
	meta?: Record< string, unknown >;
	validationErrors?: Record< string, FieldValidationStatus >;
}

/**
 * Whether the passed object is an ObserverResponse.
 */
export const isObserverResponse = (
	response: unknown
): response is ObserverResponse => {
	return isObject( response ) && objectHasProp( response, 'type' );
};

export interface ResponseType extends Record< string, unknown > {
	type: responseTypes;
	retry?: boolean;
}

const isResponseOf = (
	response: unknown,
	type: string extends responseTypes ? never : responseTypes
): response is ResponseType => {
	return isObject( response ) && 'type' in response && response.type === type;
};

export const isSuccessResponse = (
	response: unknown
): response is ObserverSuccessResponse => {
	return isResponseOf( response, responseTypes.SUCCESS );
};
export interface ObserverSuccessResponse extends ObserverResponse {
	type: responseTypes.SUCCESS;
}
export const isErrorResponse = (
	response: unknown
): response is ObserverErrorResponse => {
	return isResponseOf( response, responseTypes.ERROR );
};
export interface ObserverErrorResponse extends ObserverResponse {
	type: responseTypes.ERROR;
}

export interface ObserverFailResponse extends ObserverResponse {
	type: responseTypes.FAIL;
}
export const isFailResponse = (
	response: unknown
): response is ObserverFailResponse => {
	return isResponseOf( response, responseTypes.FAIL );
};

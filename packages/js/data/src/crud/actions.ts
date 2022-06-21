/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Resource } from './types';

export function getResourcesSuccess( query: unknown, resources: Resource[] ) {
	return {
		type: TYPES.GET_RESOURCES_SUCCESS as const,
		resources,
		query,
	};
}

export function getResourcesError( query: unknown, error: unknown ) {
	return {
		type: TYPES.GET_RESOURCES_ERROR as const,
		query,
		error,
	};
}

export function getResourceSuccess( id: number, resource: Resource ) {
	return {
		type: TYPES.GET_RESOURCE_SUCCESS as const,
		id,
		resource,
	};
}

export function getResourceError( id: unknown, error: unknown ) {
	return {
		type: TYPES.GET_RESOURCE_ERROR as const,
		id,
		error,
	};
}

export type Actions = ReturnType<
	| typeof getResourcesSuccess
	| typeof getResourcesError
	| typeof getResourceSuccess
	| typeof getResourceError
>;

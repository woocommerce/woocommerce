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

export type Actions = ReturnType<
	typeof getResourcesSuccess | typeof getResourcesError
>;

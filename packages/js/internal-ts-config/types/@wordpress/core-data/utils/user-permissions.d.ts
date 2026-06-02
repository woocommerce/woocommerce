declare module '@wordpress/core-data/build-types/utils/user-permissions' {
	export function getUserPermissionsFromAllowHeader(allowedMethods: any): {};
	export function getUserPermissionCacheKey(action: any, resource: any, id: any): string;
	export const ALLOWED_RESOURCE_ACTIONS: string[];
}

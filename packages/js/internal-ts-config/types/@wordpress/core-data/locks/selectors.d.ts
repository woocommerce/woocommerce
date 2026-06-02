declare module '@wordpress/core-data/build-types/locks/selectors' {
	export function getPendingLockRequests(state: any): any;
	export function isLockAvailable(state: any, store: any, path: any, { exclusive }: {
	    exclusive: any;
	}): boolean;
}

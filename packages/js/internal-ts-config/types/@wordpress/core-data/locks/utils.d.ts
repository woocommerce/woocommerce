declare module '@wordpress/core-data/build-types/locks/utils' {
	export function deepCopyLocksTreePath(tree: any, path: any): any;
	export function getNode(tree: any, path: any): any;
	export function iteratePath(tree: any, path: any): Generator<any, void, unknown>;
	export function iterateDescendants(node: any): Generator<any, void, unknown>;
	export function hasConflictingLock({ exclusive }: {
	    exclusive: any;
	}, locks: any): boolean;
}

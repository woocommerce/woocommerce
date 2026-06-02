/// <reference path="./types.d.ts" />

declare module '@wordpress/data/build-types/controls' {
	export namespace controls {
	    export { select };
	    export { resolveSelect };
	    export { dispatch };
	}
	export const builtinControls: {
	    "@@data/SELECT": ((registry: any) => ({ storeKey, selectorName, args }: {
	        storeKey: any;
	        selectorName: any;
	        args: any;
	    }) => any) & {
	        isRegistryControl?: boolean;
	    };
	    "@@data/RESOLVE_SELECT": ((registry: any) => ({ storeKey, selectorName, args }: {
	        storeKey: any;
	        selectorName: any;
	        args: any;
	    }) => any) & {
	        isRegistryControl?: boolean;
	    };
	    "@@data/DISPATCH": ((registry: any) => ({ storeKey, actionName, args }: {
	        storeKey: any;
	        actionName: any;
	        args: any;
	    }) => any) & {
	        isRegistryControl?: boolean;
	    };
	};
	export type StoreDescriptor = import("@wordpress/data/build-types/types").StoreDescriptor<any>;
	/**
	 * Dispatches a control action for triggering a synchronous registry select.
	 *
	 * Note: This control synchronously returns the current selector value, triggering the
	 * resolution, but not waiting for it.
	 *
	 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
	 * @param {string}                 selectorName          The name of the selector.
	 * @param {Array}                  args                  Arguments for the selector.
	 *
	 * @example
	 * ```js
	 * import { controls } from '@wordpress/data';
	 *
	 * // Action generator using `select`.
	 * export function* myAction() {
	 *   const isEditorSideBarOpened = yield controls.select( 'core/edit-post', 'isEditorSideBarOpened' );
	 *   // Do stuff with the result from the `select`.
	 * }
	 * ```
	 *
	 * @return {Object} The control descriptor.
	 */
	declare function select(storeNameOrDescriptor: string | StoreDescriptor, selectorName: string, ...args: any[]): Object;
	/**
	 * Dispatches a control action for triggering and resolving a registry select.
	 *
	 * Note: when this control action is handled, it automatically considers
	 * selectors that may have a resolver. In such case, it will return a `Promise` that resolves
	 * after the selector finishes resolving, with the final result value.
	 *
	 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
	 * @param {string}                 selectorName          The name of the selector
	 * @param {Array}                  args                  Arguments for the selector.
	 *
	 * @example
	 * ```js
	 * import { controls } from '@wordpress/data';
	 *
	 * // Action generator using resolveSelect
	 * export function* myAction() {
	 * 	const isSidebarOpened = yield controls.resolveSelect( 'core/edit-post', 'isEditorSideBarOpened' );
	 * 	// do stuff with the result from the select.
	 * }
	 * ```
	 *
	 * @return {Object} The control descriptor.
	 */
	declare function resolveSelect(storeNameOrDescriptor: string | StoreDescriptor, selectorName: string, ...args: any[]): Object;
	/**
	 * Dispatches a control action for triggering a registry dispatch.
	 *
	 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
	 * @param {string}                 actionName            The name of the action to dispatch
	 * @param {Array}                  args                  Arguments for the dispatch action.
	 *
	 * @example
	 * ```js
	 * import { controls } from '@wordpress/data-controls';
	 *
	 * // Action generator using dispatch
	 * export function* myAction() {
	 *   yield controls.dispatch( 'core/editor', 'togglePublishSidebar' );
	 *   // do some other things.
	 * }
	 * ```
	 *
	 * @return {Object}  The control descriptor.
	 */
	declare function dispatch(storeNameOrDescriptor: string | StoreDescriptor, actionName: string, ...args: any[]): Object;
	export {};
}

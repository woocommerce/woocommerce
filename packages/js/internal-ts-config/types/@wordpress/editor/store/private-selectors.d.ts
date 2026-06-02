declare module '@wordpress/editor/build-types/store/private-selectors' {
	export function getListViewToggleRef(state: any): any;
	export function getInserterSidebarToggleRef(state: any): any;
	export function getEntityActions(state: any, ...args: any[]): import("@wordpress/dataviews").Action<any>[];
	export function isEntityReady(state: any, ...args: any[]): boolean;
	export function getEntityFields(state: any, ...args: any[]): import("@wordpress/dataviews").Field<any>[];
	/**
	 * Get the canvas minimum height.
	 *
	 * @param {Object} state Global application state.
	 * @return {number} The canvas minimum height.
	 */
	export function getCanvasMinHeight(state: Object): number;
	/**
	 * Get the inserter.
	 *
	 * @param {Object} state Global application state.
	 *
	 * @return {Object} The root client ID, index to insert at and starting filter value.
	 */
	export const getInserter: {
	    (state?: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	export const getPostIcon: {
	    (state?: any, postType?: any, options?: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns true if there are unsaved changes to the
	 * post's meta fields, and false otherwise.
	 *
	 * @param {Object} state    Global application state.
	 * @param {string} postType The post type of the post.
	 * @param {number} postId   The ID of the post.
	 *
	 * @return {boolean} Whether there are edits or not in the meta fields of the relevant post.
	 */
	export const hasPostMetaChanges: {
	    (state?: any, postType?: any, postId?: any): boolean;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Similar to getBlocksByName in @wordpress/block-editor, but only returns the top-most
	 * blocks that aren't descendants of the query block.
	 *
	 * @param {Object}       state      Global application state.
	 * @param {Array|string} blockNames Block names of the blocks to retrieve.
	 *
	 * @return {Array} Block client IDs.
	 */
	export const getPostBlocksByName: {
	    (state?: any, blockNames?: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns the default rendering mode for a post type by user preference or post type configuration.
	 *
	 * @param {Object} state    Global application state.
	 * @param {string} postType The post type.
	 *
	 * @return {string} The default rendering mode. Returns `undefined` while resolving value.
	 */
	export const getDefaultRenderingMode: {
	    (state?: any, postType?: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
}

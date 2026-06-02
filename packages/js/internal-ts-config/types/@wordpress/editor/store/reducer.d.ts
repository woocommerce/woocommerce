/// <reference path="../dataviews/store/reducer.d.ts" />

declare module '@wordpress/editor/build-types/store/reducer' {
	/**
	 * Returns a post attribute value, flattening nested rendered content using its
	 * raw value in place of its original object form.
	 *
	 * @param {*} value Original value.
	 *
	 * @return {*} Raw value.
	 */
	export function getPostRawValue(value: any): any;
	/**
	 * Returns true if the two object arguments have the same keys, or false
	 * otherwise.
	 *
	 * @param {Object} a First object.
	 * @param {Object} b Second object.
	 *
	 * @return {boolean} Whether the two objects have the same keys.
	 */
	export function hasSameKeys(a: Object, b: Object): boolean;
	/**
	 * Returns true if, given the currently dispatching action and the previously
	 * dispatched action, the two actions are editing the same post property, or
	 * false otherwise.
	 *
	 * @param {Object} action         Currently dispatching action.
	 * @param {Object} previousAction Previously dispatched action.
	 *
	 * @return {boolean} Whether actions are updating the same post property.
	 */
	export function isUpdatingSamePostProperty(action: Object, previousAction: Object): boolean;
	/**
	 * Returns true if, given the currently dispatching action and the previously
	 * dispatched action, the two actions are modifying the same property such that
	 * undo history should be batched.
	 *
	 * @param {Object} action         Currently dispatching action.
	 * @param {Object} previousAction Previously dispatched action.
	 *
	 * @return {boolean} Whether to overwrite present state.
	 */
	export function shouldOverwriteState(action: Object, previousAction: Object): boolean;
	export function postId(state: null | undefined, action: any): any;
	export function templateId(state: null | undefined, action: any): any;
	export function postType(state: null | undefined, action: any): any;
	/**
	 * Reducer returning whether the post blocks match the defined template or not.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {boolean} Updated state.
	 */
	export function template(state: Object | undefined, action: Object): boolean;
	/**
	 * Reducer returning current network request state (whether a request to
	 * the WP REST API is in progress, successful, or failed).
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function saving(state: Object | undefined, action: Object): Object;
	/**
	 * Reducer returning deleting post request state.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function deleting(state: Object | undefined, action: Object): Object;
	/**
	 * Post Lock State.
	 *
	 * @typedef {Object} PostLockState
	 *
	 * @property {boolean}  isLocked       Whether the post is locked.
	 * @property {?boolean} isTakeover     Whether the post editing has been taken over.
	 * @property {?boolean} activePostLock Active post lock value.
	 * @property {?Object}  user           User that took over the post.
	 */
	/**
	 * Reducer returning the post lock status.
	 *
	 * @param {PostLockState} state  Current state.
	 * @param {Object}        action Dispatched action.
	 *
	 * @return {PostLockState} Updated state.
	 */
	export function postLock(state: PostLockState | undefined, action: Object): PostLockState;
	/**
	 * Post saving lock.
	 *
	 * When post saving is locked, the post cannot be published or updated.
	 *
	 * @param {PostLockState} state  Current state.
	 * @param {Object}        action Dispatched action.
	 *
	 * @return {PostLockState} Updated state.
	 */
	export function postSavingLock(state: PostLockState | undefined, action: Object): PostLockState;
	/**
	 * Post autosaving lock.
	 *
	 * When post autosaving is locked, the post will not autosave.
	 *
	 * @param {PostLockState} state  Current state.
	 * @param {Object}        action Dispatched action.
	 *
	 * @return {PostLockState} Updated state.
	 */
	export function postAutosavingLock(state: PostLockState | undefined, action: Object): PostLockState;
	/**
	 * Reducer returning the post editor setting.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function editorSettings(state: Object | undefined, action: Object): Object;
	export function renderingMode(state: string | undefined, action: any): any;
	/**
	 * Reducer returning the editing canvas device type.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function deviceType(state: Object | undefined, action: Object): Object;
	/**
	 * Reducer storing the list of all programmatically removed panels.
	 *
	 * @param {Array}  state  Current state.
	 * @param {Object} action Action object.
	 *
	 * @return {Array} Updated state.
	 */
	export function removedPanels(state: any[] | undefined, action: Object): any[];
	/**
	 * Reducer to set the block inserter panel open or closed.
	 *
	 * Note: this reducer interacts with the list view panel reducer
	 * to make sure that only one of the two panels is open at the same time.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 */
	export function blockInserterPanel(state: Object | undefined, action: Object): any;
	/**
	 * Reducer to set the list view panel open or closed.
	 *
	 * Note: this reducer interacts with the inserter panel reducer
	 * to make sure that only one of the two panels is open at the same time.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 */
	export function listViewPanel(state: Object | undefined, action: Object): any;
	/**
	 * This reducer does nothing aside initializing a ref to the list view toggle.
	 * We will have a unique ref per "editor" instance.
	 *
	 * @param {Object} state
	 * @return {Object} Reference to the list view toggle button.
	 */
	export function listViewToggleRef(state?: Object): Object;
	/**
	 * This reducer does nothing aside initializing a ref to the inserter sidebar toggle.
	 * We will have a unique ref per "editor" instance.
	 *
	 * @param {Object} state
	 * @return {Object} Reference to the inserter sidebar toggle button.
	 */
	export function inserterSidebarToggleRef(state?: Object): Object;
	export function publishSidebarActive(state: boolean | undefined, action: any): boolean;
	/**
	 * Reducer for the canvas minimum height.
	 *
	 * @param {number} state  Current state.
	 * @param {Object} action Dispatched action.
	 * @return {number} Updated state.
	 */
	export function canvasMinHeight(state: number | undefined, action: Object): number;
	declare const _default: import("redux").Reducer<{
	    postId: any;
	    postType: any;
	    templateId: any;
	    saving: Object;
	    deleting: Object;
	    postLock: PostLockState;
	    template: boolean;
	    postSavingLock: PostLockState;
	    editorSettings: Object;
	    postAutosavingLock: PostLockState;
	    renderingMode: any;
	    deviceType: Object;
	    removedPanels: any[];
	    blockInserterPanel: any;
	    inserterSidebarToggleRef: Object;
	    listViewPanel: any;
	    listViewToggleRef: Object;
	    publishSidebarActive: boolean;
	    canvasMinHeight: number;
	    dataviews: {
	        actions: {
	            [x: string]: Record<string, import("@wordpress/dataviews").Action<any>[]> | {
	                [x: string]: import("@wordpress/dataviews").Action<any>[] | (import("@wordpress/dataviews").Action<unknown> | import("@wordpress/dataviews").Action<any>)[];
	            };
	        };
	        fields: {
	            [x: string]: Record<string, import("@wordpress/dataviews").Field<any>[]> | {
	                [x: string]: import("@wordpress/dataviews").Field<any>[] | (import("@wordpress/dataviews").Field<any> | import("@wordpress/dataviews").Field<unknown>)[];
	            };
	        };
	        isReady: import("@wordpress/editor/build-types/dataviews/store/reducer").ReadyState;
	    };
	}, any, Partial<{
	    postId: null | undefined;
	    postType: null | undefined;
	    templateId: null | undefined;
	    saving: Object | undefined;
	    deleting: Object | undefined;
	    postLock: PostLockState | undefined;
	    template: Object | undefined;
	    postSavingLock: PostLockState | undefined;
	    editorSettings: Object | undefined;
	    postAutosavingLock: PostLockState | undefined;
	    renderingMode: string | undefined;
	    deviceType: Object | undefined;
	    removedPanels: any[] | undefined;
	    blockInserterPanel: Object | undefined;
	    inserterSidebarToggleRef: Object | undefined;
	    listViewPanel: Object | undefined;
	    listViewToggleRef: Object | undefined;
	    publishSidebarActive: boolean | undefined;
	    canvasMinHeight: number | undefined;
	    dataviews: never;
	}>>;
	export default _default;
	/**
	 * Post Lock State.
	 */
	export type PostLockState = {
	    /**
	     * Whether the post is locked.
	     */
	    isLocked: boolean;
	    /**
	     * Whether the post editing has been taken over.
	     */
	    isTakeover: boolean | null;
	    /**
	     * Active post lock value.
	     */
	    activePostLock: boolean | null;
	    /**
	     * User that took over the post.
	     */
	    user: Object | null;
	};
}

/// <reference path="./types.d.ts" />

declare module '@wordpress/core-data/build-types/reducer' {
	/** @typedef {import('./types').AnyFunction} AnyFunction */
	/**
	 * Reducer managing authors state. Keyed by id.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function users(state: any, action: any): any;
	/**
	 * Reducer managing current user state.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function currentUser(state: any, action: any): any;
	/**
	 * Reducer managing the current theme.
	 *
	 * @param {string|undefined} state  Current state.
	 * @param {Object}           action Dispatched action.
	 *
	 * @return {string|undefined} Updated state.
	 */
	export function currentTheme(state: string | undefined, action: any): string | undefined;
	/**
	 * Reducer managing the current global styles id.
	 *
	 * @param {string|undefined} state  Current state.
	 * @param {Object}           action Dispatched action.
	 *
	 * @return {string|undefined} Updated state.
	 */
	export function currentGlobalStylesId(state: string | undefined, action: any): string | undefined;
	/**
	 * Reducer managing the theme base global styles.
	 *
	 * @param {Record<string, object>} state  Current state.
	 * @param {Object}                 action Dispatched action.
	 *
	 * @return {Record<string, object>} Updated state.
	 */
	export function themeBaseGlobalStyles(state: Record<string, object> | undefined, action: any): Record<string, object>;
	/**
	 * Reducer managing the theme global styles variations.
	 *
	 * @param {Record<string, object>} state  Current state.
	 * @param {Object}                 action Dispatched action.
	 *
	 * @return {Record<string, object>} Updated state.
	 */
	export function themeGlobalStyleVariations(state: Record<string, object> | undefined, action: any): Record<string, object>;
	/**
	 * Reducer keeping track of the registered entities.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function entitiesConfig(state: any, action: any): any;
	/**
	 * @type {UndoManager}
	 */
	export function undoManager(state?: import("@wordpress/undo-manager").UndoManager<unknown>): import("@wordpress/undo-manager").UndoManager<unknown>;
	export function editsReference(state: {} | undefined, action: any): {};
	/**
	 * Reducer managing embed preview data.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function embedPreviews(state: any, action: any): any;
	/**
	 * State which tracks whether the user can perform an action on a REST
	 * resource.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function userPermissions(state: any, action: any): any;
	/**
	 * Reducer returning autosaves keyed by their parent's post id.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function autosaves(state: any, action: any): any;
	export function blockPatterns(state: any[] | undefined, action: any): any;
	export function blockPatternCategories(state: any[] | undefined, action: any): any;
	export function userPatternCategories(state: any[] | undefined, action: any): any;
	export function navigationFallbackId(state: null | undefined, action: any): any;
	/**
	 * Reducer managing the theme global styles revisions.
	 *
	 * @param {Record<string, object>} state  Current state.
	 * @param {Object}                 action Dispatched action.
	 *
	 * @return {Record<string, object>} Updated state.
	 */
	export function themeGlobalStyleRevisions(state: Record<string, object> | undefined, action: any): Record<string, object>;
	/**
	 * Reducer managing the template lookup per query.
	 *
	 * @param {Record<string, string>} state  Current state.
	 * @param {Object}                 action Dispatched action.
	 *
	 * @return {Record<string, string>} Updated state.
	 */
	export function defaultTemplates(state: Record<string, string> | undefined, action: any): Record<string, string>;
	/**
	 * Reducer returning an object of registered post meta.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	export function registeredPostMeta(state: any, action: any): any;
	export function entities(state: any, action: any): any;
	declare const _default: import("redux").Reducer<{
	    users: any;
	    currentTheme: string | undefined;
	    currentGlobalStylesId: string | undefined;
	    currentUser: any;
	    themeGlobalStyleVariations: Record<string, any>;
	    themeBaseGlobalStyles: Record<string, any>;
	    themeGlobalStyleRevisions: Record<string, any>;
	    entities: any;
	    editsReference: {};
	    undoManager: import("@wordpress/undo-manager").UndoManager<unknown>;
	    embedPreviews: any;
	    userPermissions: any;
	    autosaves: any;
	    blockPatterns: any;
	    blockPatternCategories: any;
	    userPatternCategories: any;
	    navigationFallbackId: any;
	    defaultTemplates: Record<string, string>;
	    registeredPostMeta: any;
	}, any, Partial<{
	    users: any;
	    currentTheme: string | undefined;
	    currentGlobalStylesId: string | undefined;
	    currentUser: any;
	    themeGlobalStyleVariations: Record<string, any> | undefined;
	    themeBaseGlobalStyles: Record<string, any> | undefined;
	    themeGlobalStyleRevisions: Record<string, any> | undefined;
	    entities: any;
	    editsReference: {} | undefined;
	    undoManager: import("@wordpress/undo-manager").UndoManager<unknown> | undefined;
	    embedPreviews: any;
	    userPermissions: any;
	    autosaves: any;
	    blockPatterns: any[] | undefined;
	    blockPatternCategories: any[] | undefined;
	    userPatternCategories: any[] | undefined;
	    navigationFallbackId: null | undefined;
	    defaultTemplates: Record<string, string> | undefined;
	    registeredPostMeta: any;
	}>>;
	export default _default;
	export type AnyFunction = import("@wordpress/core-data/build-types/types").AnyFunction;
}

declare module '@wordpress/core-data/build-types/actions' {
	/**
	 * Returns an action object used in signalling that authors have been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string}       queryID Query ID.
	 * @param {Array|Object} users   Users received.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveUserQuery(queryID: string, users: any[] | any): any;
	/**
	 * Returns an action used in signalling that the current user has been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {Object} currentUser Current user object.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveCurrentUser(currentUser: any): any;
	/**
	 * Returns an action object used in adding new entities.
	 *
	 * @param {Array} entities Entities received.
	 *
	 * @return {Object} Action object.
	 */
	export function addEntities(entities: any[]): any;
	/**
	 * Returns an action object used in signalling that entity records have been received.
	 *
	 * @param {string}       kind            Kind of the received entity record.
	 * @param {string}       name            Name of the received entity record.
	 * @param {Array|Object} records         Records received.
	 * @param {?Object}      query           Query Object.
	 * @param {?boolean}     invalidateCache Should invalidate query caches.
	 * @param {?Object}      edits           Edits to reset.
	 * @param {?Object}      meta            Meta information about pagination.
	 * @return {Object} Action object.
	 */
	export function receiveEntityRecords(kind: string, name: string, records: any[] | any, query: any | null, invalidateCache: (boolean | null) | undefined, edits: any | null, meta: any | null): any;
	/**
	 * Returns an action object used in signalling that the current theme has been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {Object} currentTheme The current theme.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveCurrentTheme(currentTheme: any): any;
	/**
	 * Returns an action object used in signalling that the current global styles id has been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string} currentGlobalStylesId The current global styles id.
	 *
	 * @return {Object} Action object.
	 */
	export function __experimentalReceiveCurrentGlobalStylesId(currentGlobalStylesId: string): any;
	/**
	 * Returns an action object used in signalling that the theme base global styles have been received
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string} stylesheet   The theme's identifier
	 * @param {Object} globalStyles The global styles object.
	 *
	 * @return {Object} Action object.
	 */
	export function __experimentalReceiveThemeBaseGlobalStyles(stylesheet: string, globalStyles: any): any;
	/**
	 * Returns an action object used in signalling that the theme global styles variations have been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string} stylesheet The theme's identifier
	 * @param {Array}  variations The global styles variations.
	 *
	 * @return {Object} Action object.
	 */
	export function __experimentalReceiveThemeGlobalStyleVariations(stylesheet: string, variations: any[]): any;
	/**
	 * Returns an action object used in signalling that the index has been received.
	 *
	 * @deprecated since WP 5.9, this is not useful anymore, use the selector directly.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveThemeSupports(): any;
	/**
	 * Returns an action object used in signalling that the theme global styles CPT post revisions have been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @deprecated since WordPress 6.5.0. Callers should use `dispatch( 'core' ).receiveRevision` instead.
	 *
	 * @ignore
	 *
	 * @param {number} currentId The post id.
	 * @param {Array}  revisions The global styles revisions.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveThemeGlobalStyleRevisions(currentId: number, revisions: any[]): any;
	/**
	 * Returns an action object used in signalling that the preview data for
	 * a given URl has been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string} url     URL to preview the embed for.
	 * @param {*}      preview Preview data.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveEmbedPreview(url: string, preview: any): any;
	/**
	 * Returns an action object used in signalling that Upload permissions have been received.
	 *
	 * @deprecated since WP 5.9, use receiveUserPermission instead.
	 *
	 * @param {boolean} hasUploadPermissions Does the user have permission to upload files?
	 *
	 * @return {Object} Action object.
	 */
	export function receiveUploadPermissions(hasUploadPermissions: boolean): any;
	/**
	 * Returns an action object used in signalling that the current user has
	 * permission to perform an action on a REST resource.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {string}  key       A key that represents the action and REST resource.
	 * @param {boolean} isAllowed Whether or not the user can perform the action.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveUserPermission(key: string, isAllowed: boolean): any;
	/**
	 * Returns an action object used in signalling that the current user has
	 * permission to perform an action on a REST resource. Ignored from
	 * documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {Object<string, boolean>} permissions An object where keys represent
	 *                                              actions and REST resources, and
	 *                                              values indicate whether the user
	 *                                              is allowed to perform the
	 *                                              action.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveUserPermissions(permissions: {
	    [x: string]: boolean;
	}): any;
	/**
	 * Returns an action object used in signalling that the autosaves for a
	 * post have been received.
	 * Ignored from documentation as it's internal to the data store.
	 *
	 * @ignore
	 *
	 * @param {number}       postId    The id of the post that is parent to the autosave.
	 * @param {Array|Object} autosaves An array of autosaves or singular autosave object.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveAutosaves(postId: number, autosaves: any[] | any): any;
	/**
	 * Returns an action object signalling that the fallback Navigation
	 * Menu id has been received.
	 *
	 * @param {integer} fallbackId the id of the fallback Navigation Menu
	 * @return {Object} Action object.
	 */
	export function receiveNavigationFallbackId(fallbackId: integer): any;
	/**
	 * Returns an action object used to set the template for a given query.
	 *
	 * @param {Object} query      The lookup query.
	 * @param {string} templateId The resolved template id.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveDefaultTemplateId(query: any, templateId: string): any;
	export function deleteEntityRecord(kind: string, name: string, recordId: number | string, query: any | null, { __unstableFetch, throwOnError }?: {
	    __unstableFetch?: Function | undefined;
	    throwOnError?: boolean | undefined;
	}): ({ dispatch, resolveSelect }: {
	    dispatch: any;
	    resolveSelect: any;
	}) => Promise<boolean | undefined>;
	export function editEntityRecord(kind: string, name: string, recordId: number | string, edits: any, options?: {
	    undoIgnore?: boolean | undefined;
	}): any;
	export function undo(): ({ select, dispatch }: {
	    select: any;
	    dispatch: any;
	}) => void;
	export function redo(): ({ select, dispatch }: {
	    select: any;
	    dispatch: any;
	}) => void;
	export function __unstableCreateUndoLevel(): any;
	export function saveEntityRecord(kind: string, name: string, record: any, { isAutosave, __unstableFetch, throwOnError, }?: {
	    isAutosave?: boolean | undefined;
	    __unstableFetch?: Function | undefined;
	    throwOnError?: boolean | undefined;
	}): ({ select, resolveSelect, dispatch }: {
	    select: any;
	    resolveSelect: any;
	    dispatch: any;
	}) => Promise<any>;
	export function __experimentalBatch(requests: any[]): (thunkArgs: any) => Promise<any>;
	export function saveEditedEntityRecord(kind: string, name: string, recordId: any, options?: any | undefined): ({ select, dispatch, resolveSelect }: {
	    select: any;
	    dispatch: any;
	    resolveSelect: any;
	}) => Promise<any>;
	export function __experimentalSaveSpecifiedEntityEdits(kind: string, name: string, recordId: number | string, itemsToSave: any[], options: any): ({ select, dispatch, resolveSelect }: {
	    select: any;
	    dispatch: any;
	    resolveSelect: any;
	}) => Promise<any>;
	export function receiveRevisions(kind: string, name: string, recordKey: number | string, records: any[] | any, query: any | null, invalidateCache: (boolean | null) | undefined, meta: any | null): ({ dispatch, resolveSelect }: {
	    dispatch: any;
	    resolveSelect: any;
	}) => Promise<void>;
}

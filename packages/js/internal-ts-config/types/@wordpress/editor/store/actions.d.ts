declare module '@wordpress/editor/build-types/store/actions' {
	/**
	 * Returns an action object signalling that the editor is being destroyed and
	 * that any necessary state or side-effect cleanup should occur.
	 *
	 * @deprecated
	 *
	 * @return {Object} Action object.
	 */
	export function __experimentalTearDownEditor(): Object;
	/**
	 * Returns an action object used in signalling that the latest version of the
	 * post has been received, either by initialization or save.
	 *
	 * @deprecated Since WordPress 6.0.
	 */
	export function resetPost(): {
	    type: string;
	};
	/**
	 * Returns an action object used in signalling that a patch of updates for the
	 * latest version of the post have been received.
	 *
	 * @return {Object} Action object.
	 * @deprecated since Gutenberg 9.7.0.
	 */
	export function updatePost(): Object;
	/**
	 * Setup the editor state.
	 *
	 * @deprecated
	 *
	 * @param {Object} post Post object.
	 */
	export function setupEditorState(post: Object): Object;
	/**
	 * Returns an action that sets the current post Type and post ID.
	 *
	 * @param {string} postType Post Type.
	 * @param {string} postId   Post ID.
	 *
	 * @return {Object} Action object.
	 */
	export function setEditedPost(postType: string, postId: string): Object;
	/**
	 * Action for refreshing the current post.
	 *
	 * @deprecated Since WordPress 6.0.
	 */
	export function refreshPost(): {
	    type: string;
	};
	/**
	 * Action that creates an undo history record.
	 *
	 * @deprecated Since WordPress 6.0
	 */
	export function createUndoLevel(): {
	    type: string;
	};
	/**
	 * Action that locks the editor.
	 *
	 * @param {Object} lock Details about the post lock status, user, and nonce.
	 * @return {Object} Action object.
	 */
	export function updatePostLock(lock: Object): Object;
	/**
	 * Action that locks post saving.
	 *
	 * @param {string} lockName The lock name.
	 *
	 * @example
	 * ```
	 * const { subscribe } = wp.data;
	 *
	 * const initialPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
	 *
	 * // Only allow publishing posts that are set to a future date.
	 * if ( 'publish' !== initialPostStatus ) {
	 *
	 * 	// Track locking.
	 * 	let locked = false;
	 *
	 * 	// Watch for the publish event.
	 * 	let unssubscribe = subscribe( () => {
	 * 		const currentPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
	 * 		if ( 'publish' !== currentPostStatus ) {
	 *
	 * 			// Compare the post date to the current date, lock the post if the date isn't in the future.
	 * 			const postDate = new Date( wp.data.select( 'core/editor' ).getEditedPostAttribute( 'date' ) );
	 * 			const currentDate = new Date();
	 * 			if ( postDate.getTime() <= currentDate.getTime() ) {
	 * 				if ( ! locked ) {
	 * 					locked = true;
	 * 					wp.data.dispatch( 'core/editor' ).lockPostSaving( 'futurelock' );
	 * 				}
	 * 			} else {
	 * 				if ( locked ) {
	 * 					locked = false;
	 * 					wp.data.dispatch( 'core/editor' ).unlockPostSaving( 'futurelock' );
	 * 				}
	 * 			}
	 * 		}
	 * 	} );
	 * }
	 * ```
	 *
	 * @return {Object} Action object
	 */
	export function lockPostSaving(lockName: string): Object;
	/**
	 * Action that unlocks post saving.
	 *
	 * @param {string} lockName The lock name.
	 *
	 * @example
	 * ```
	 * // Unlock post saving with the lock key `mylock`:
	 * wp.data.dispatch( 'core/editor' ).unlockPostSaving( 'mylock' );
	 * ```
	 *
	 * @return {Object} Action object
	 */
	export function unlockPostSaving(lockName: string): Object;
	/**
	 * Action that locks post autosaving.
	 *
	 * @param {string} lockName The lock name.
	 *
	 * @example
	 * ```
	 * // Lock post autosaving with the lock key `mylock`:
	 * wp.data.dispatch( 'core/editor' ).lockPostAutosaving( 'mylock' );
	 * ```
	 *
	 * @return {Object} Action object
	 */
	export function lockPostAutosaving(lockName: string): Object;
	/**
	 * Action that unlocks post autosaving.
	 *
	 * @param {string} lockName The lock name.
	 *
	 * @example
	 * ```
	 * // Unlock post saving with the lock key `mylock`:
	 * wp.data.dispatch( 'core/editor' ).unlockPostAutosaving( 'mylock' );
	 * ```
	 *
	 * @return {Object} Action object
	 */
	export function unlockPostAutosaving(lockName: string): Object;
	export function updateEditorSettings(settings: any): {
	    type: string;
	    settings: any;
	};
	/**
	 * Action that changes the width of the editing canvas.
	 *
	 * @param {string} deviceType
	 *
	 * @return {Object} Action object.
	 */
	export function setDeviceType(deviceType: string): Object;
	/**
	 * Returns an action object used to remove a panel from the editor.
	 *
	 * @param {string} panelName A string that identifies the panel to remove.
	 *
	 * @return {Object} Action object.
	 */
	export function removeEditorPanel(panelName: string): Object;
	/**
	 * Returns an action object used to open/close the list view.
	 *
	 * @param {boolean} isOpen A boolean representing whether the list view should be opened or closed.
	 * @return {Object} Action object.
	 */
	export function setIsListViewOpened(isOpen: boolean): Object;
	/**
	 * Returns an action object used in signalling that the user opened the publish
	 * sidebar.
	 *
	 * @return {Object} Action object
	 */
	export function openPublishSidebar(): Object;
	/**
	 * Returns an action object used in signalling that the user closed the
	 * publish sidebar.
	 *
	 * @return {Object} Action object.
	 */
	export function closePublishSidebar(): Object;
	/**
	 * Returns an action object used in signalling that the user toggles the publish sidebar.
	 *
	 * @return {Object} Action object
	 */
	export function togglePublishSidebar(): Object;
	export function setupEditor(post: Object, edits: Object, template?: any[]): ({ dispatch }: {
	    dispatch: any;
	}) => void;
	export function editPost(edits: Object, options?: Object): Object;
	export function savePost(options?: Object): ({ select, dispatch, registry }: {
	    select: any;
	    dispatch: any;
	    registry: any;
	}) => Promise<void>;
	export function trashPost(): ({ select, dispatch, registry }: {
	    select: any;
	    dispatch: any;
	    registry: any;
	}) => Promise<void>;
	export function autosave({ local, ...options }?: {
	    local?: boolean | undefined;
	}): ({ select, dispatch }: {
	    select: any;
	    dispatch: any;
	}) => Promise<void>;
	export function __unstableSaveForPreview({ forceIsAutosaveable }?: {}): ({ select, dispatch }: {
	    select: any;
	    dispatch: any;
	}) => Promise<any>;
	export function redo(): ({ registry }: {
	    registry: any;
	}) => void;
	export function undo(): ({ registry }: {
	    registry: any;
	}) => void;
	export function enablePublishSidebar(): ({ registry }: {
	    registry: any;
	}) => void;
	export function disablePublishSidebar(): ({ registry }: {
	    registry: any;
	}) => void;
	export function resetEditorBlocks(blocks: any[], options?: Object): ({ select, dispatch, registry }: {
	    select: any;
	    dispatch: any;
	    registry: any;
	}) => void;
	export function setRenderingMode(mode: string): ({ dispatch, registry, select }: {
	    dispatch: any;
	    registry: any;
	    select: any;
	}) => void;
	export function toggleEditorPanelEnabled(panelName: string): Object;
	export function toggleEditorPanelOpened(panelName: string): ({ registry }: {
	    registry: any;
	}) => void;
	export function setIsInserterOpened(value: boolean | Object): Object;
	export function toggleDistractionFree({ createNotice }?: {
	    createNotice?: boolean | undefined;
	}): ({ dispatch, registry }: {
	    dispatch: any;
	    registry: any;
	}) => void;
	export function toggleSpotlightMode(): ({ registry }: {
	    registry: any;
	}) => void;
	export function toggleTopToolbar(): ({ registry }: {
	    registry: any;
	}) => void;
	export function switchEditorMode(mode: string): ({ dispatch, registry }: {
	    dispatch: any;
	    registry: any;
	}) => void;
	export function resetBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function receiveBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function updateBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function updateBlockAttributes(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function selectBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function startMultiSelect(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function stopMultiSelect(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function multiSelect(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function clearSelectedBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function toggleSelection(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function replaceBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function replaceBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function moveBlocksDown(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function moveBlocksUp(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function moveBlockToPosition(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function insertBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function insertBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function showInsertionPoint(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function hideInsertionPoint(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function setTemplateValidity(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function synchronizeTemplate(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function mergeBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function removeBlocks(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function removeBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function toggleBlockMode(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function startTyping(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function stopTyping(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function enterFormattedText(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function exitFormattedText(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function insertDefaultBlock(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function updateBlockListSettings(...args: any[]): ({ registry }: {
	    registry: any;
	}) => void;
}

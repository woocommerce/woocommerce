declare module '@wordpress/editor/build-types/store/utils/notice-builder' {
	/**
	 * Builds the arguments for a success notification dispatch.
	 *
	 * @param {Object} data Incoming data to build the arguments from.
	 *
	 * @return {Array} Arguments for dispatch. An empty array signals no
	 *                 notification should be sent.
	 */
	export function getNotificationArgumentsForSaveSuccess(data: Object): any[];
	/**
	 * Builds the fail notification arguments for dispatch.
	 *
	 * @param {Object} data Incoming data to build the arguments with.
	 *
	 * @return {Array} Arguments for dispatch. An empty array signals no
	 *                 notification should be sent.
	 */
	export function getNotificationArgumentsForSaveFail(data: Object): any[];
	/**
	 * Builds the trash fail notification arguments for dispatch.
	 *
	 * @param {Object} data
	 *
	 * @return {Array} Arguments for dispatch.
	 */
	export function getNotificationArgumentsForTrashFail(data: Object): any[];
}

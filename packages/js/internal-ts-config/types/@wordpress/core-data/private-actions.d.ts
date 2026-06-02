declare module '@wordpress/core-data/build-types/private-actions' {
	/**
	 * Returns an action object used in signalling that the registered post meta
	 * fields for a post type have been received.
	 *
	 * @param {string} postType           Post type slug.
	 * @param {Object} registeredPostMeta Registered post meta.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveRegisteredPostMeta(postType: string, registeredPostMeta: any): any;
	export function editMediaEntity(recordId: string, edits?: Edits, { __unstableFetch, throwOnError }?: {
	    __unstableFetch: Function;
	    throwOnError: boolean;
	}): Promise<any>;
	export type Modifier = {
	    /**
	     * - The type of modifier.
	     */
	    type?: string | undefined;
	    /**
	     * - The arguments of the modifier.
	     */
	    args?: any;
	};
	export type Edits = {
	    /**
	     * - The URL of the media item.
	     */
	    src?: string | undefined;
	    /**
	     * - The modifiers to apply to the media item.
	     */
	    modifiers?: Modifier[] | undefined;
	};
}

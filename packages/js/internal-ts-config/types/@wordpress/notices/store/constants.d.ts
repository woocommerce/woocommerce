declare module '@wordpress/notices/build-types/store/constants' {
	/**
	 * Default context to use for notice grouping when not otherwise specified. Its
	 * specific value doesn't hold much meaning, but it must be reasonably unique
	 * and, more importantly, referenced consistently in the store implementation.
	 *
	 * @type {string}
	 */
	export const DEFAULT_CONTEXT: string;
	/**
	 * Default notice status.
	 *
	 * @type {string}
	 */
	export const DEFAULT_STATUS: string;
}

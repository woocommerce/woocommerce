declare module '@wordpress/core-data/build-types/fetch/__experimental-fetch-url-data' {
	export default fetchUrlData;
	export type WPRemoteUrlData = {
	    /**
	     * contents of the remote URL's `<title>` tag.
	     */
	    title: string;
	};
	/**
	 * @typedef WPRemoteUrlData
	 *
	 * @property {string} title contents of the remote URL's `<title>` tag.
	 */
	/**
	 * Fetches data about a remote URL.
	 * eg: <title> tag, favicon...etc.
	 *
	 * @async
	 * @param {string}  url     the URL to request details from.
	 * @param {?Object} options any options to pass to the underlying fetch.
	 * @example
	 * ```js
	 * import { __experimentalFetchUrlData as fetchUrlData } from '@wordpress/core-data';
	 *
	 * //...
	 *
	 * export function initialize( id, settings ) {
	 *
	 * settings.__experimentalFetchUrlData = (
	 * url
	 * ) => fetchUrlData( url );
	 * ```
	 * @return {Promise< WPRemoteUrlData[] >} Remote URL data.
	 */
	declare function fetchUrlData(url: string, options?: any | null): Promise<WPRemoteUrlData[]>;
}

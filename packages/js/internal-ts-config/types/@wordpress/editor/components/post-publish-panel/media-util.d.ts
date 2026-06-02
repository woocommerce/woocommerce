declare module '@wordpress/editor/build-types/components/post-publish-panel/media-util' {
	/**
	 * Generate a list of unique basenames given a list of URLs.
	 *
	 * We want all basenames to be unique, since sometimes the extension
	 * doesn't reflect the mime type, and may end up getting changed by
	 * the server, on upload.
	 *
	 * @param {string[]} urls The list of URLs
	 * @return {Record< string, string >} A URL => basename record.
	 */
	export function generateUniqueBasenames(urls: string[]): Record<string, string>;
	/**
	 * Fetch a list of URLs, turning those into promises for files with
	 * unique filenames.
	 *
	 * @param {string[]} urls The list of URLs
	 * @return {Record< string, Promise< File > >} A URL => File promise record.
	 */
	export function fetchMedia(urls: string[]): Record<string, Promise<File>>;
}

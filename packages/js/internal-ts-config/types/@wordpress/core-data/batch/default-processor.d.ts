declare module '@wordpress/core-data/build-types/batch/default-processor' {
	/**
	 * Default batch processor. Sends its input requests to /batch/v1.
	 *
	 * @param {Array} requests List of API requests to perform at once.
	 *
	 * @return {Promise} Promise that resolves to a list of objects containing
	 *                   either `output` (if that request was successful) or `error`
	 *                   (if not ).
	 */
	export default function defaultProcessor(requests: any[]): Promise<any>;
}

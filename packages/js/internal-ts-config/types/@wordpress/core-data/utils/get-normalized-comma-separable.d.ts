declare module '@wordpress/core-data/build-types/utils/get-normalized-comma-separable' {
	export default getNormalizedCommaSeparable;
	/**
	 * Given a value which can be specified as one or the other of a comma-separated
	 * string or an array, returns a value normalized to an array of strings, or
	 * null if the value cannot be interpreted as either.
	 *
	 * @param {string|string[]|*} value
	 *
	 * @return {?(string[])} Normalized field value.
	 */
	declare function getNormalizedCommaSeparable(value: string | string[] | any): (string[]) | null;
}

declare module '@wordpress/core-data/build-types/queried-data/get-query-parts' {
	/**
	 * An object of properties describing a specific query.
	 *
	 * @typedef {Object} WPQueriedDataQueryParts
	 *
	 * @property {number}      page      The query page (1-based index, default 1).
	 * @property {number}      perPage   Items per page for query (default 10).
	 * @property {string}      stableKey An encoded stable string of all non-
	 *                                   pagination, non-fields query parameters.
	 * @property {?(string[])} fields    Target subset of fields to derive from
	 *                                   item objects.
	 * @property {?(number[])} include   Specific item IDs to include.
	 * @property {string}      context   Scope under which the request is made;
	 *                                   determines returned fields in response.
	 */
	/**
	 * Given a query object, returns an object of parts, including pagination
	 * details (`page` and `perPage`, or default values). All other properties are
	 * encoded into a stable (idempotent) `stableKey` value.
	 *
	 * @param {Object} query Optional query object.
	 *
	 * @return {WPQueriedDataQueryParts} Query parts.
	 */
	export function getQueryParts(query: any): WPQueriedDataQueryParts;
	declare const _default: Function;
	export default _default;
	/**
	 * An object of properties describing a specific query.
	 */
	export type WPQueriedDataQueryParts = {
	    /**
	     * The query page (1-based index, default 1).
	     */
	    page: number;
	    /**
	     * Items per page for query (default 10).
	     */
	    perPage: number;
	    /**
	     * An encoded stable string of all non-
	     * pagination, non-fields query parameters.
	     */
	    stableKey: string;
	    /**
	     * Target subset of fields to derive from
	     * item objects.
	     */
	    fields: (string[]) | null;
	    /**
	     * Specific item IDs to include.
	     */
	    include: (number[]) | null;
	    /**
	     * Scope under which the request is made;
	     * determines returned fields in response.
	     */
	    context: string;
	};
}

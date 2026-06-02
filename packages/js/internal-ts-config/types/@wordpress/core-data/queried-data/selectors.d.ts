declare module '@wordpress/core-data/build-types/queried-data/selectors' {
	export function getQueriedTotalItems(state: any, query?: {}): any;
	export function getQueriedTotalPages(state: any, query?: {}): any;
	/**
	 * Returns items for a given query, or null if the items are not known. Caches
	 * result both per state (by reference) and per query (by deep equality).
	 * The caching approach is intended to be durable to query objects which are
	 * deeply but not referentially equal, since otherwise:
	 *
	 * `getQueriedItems( state, {} ) !== getQueriedItems( state, {} )`
	 *
	 * @param {Object}  state State object.
	 * @param {?Object} query Optional query.
	 *
	 * @return {?Array} Query items.
	 */
	export const getQueriedItems: ((state: any, query?: any) => any) & import("rememo").EnhancedSelector;
}

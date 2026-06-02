declare module '@wordpress/core-data/build-types/queried-data/reducer' {
	/**
	 * Returns a merged array of item IDs, given details of the received paginated
	 * items. The array is sparse-like with `undefined` entries where holes exist.
	 *
	 * @param {?Array<number>} itemIds     Original item IDs (default empty array).
	 * @param {number[]}       nextItemIds Item IDs to merge.
	 * @param {number}         page        Page of items merged.
	 * @param {number}         perPage     Number of items per page.
	 *
	 * @return {number[]} Merged array of item IDs.
	 */
	export function getMergedItemIds(itemIds: Array<number> | null, nextItemIds: number[], page: number, perPage: number): number[];
	/**
	 * Reducer tracking items state, keyed by ID. Items are assumed to be normal,
	 * where identifiers are common across all queries.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Next state.
	 */
	export function items(state: any, action: any): any;
	/**
	 * Reducer tracking item completeness, keyed by ID. A complete item is one for
	 * which all fields are known. This is used in supporting `_fields` queries,
	 * where not all properties associated with an entity are necessarily returned.
	 * In such cases, completeness is used as an indication of whether it would be
	 * safe to use queried data for a non-`_fields`-limited request.
	 *
	 * @param {Object<string,Object<string,boolean>>} state  Current state.
	 * @param {Object}                                action Dispatched action.
	 *
	 * @return {Object<string,Object<string,boolean>>} Next state.
	 */
	export function itemIsComplete(state: {
	    [x: string]: {
	        [x: string]: boolean;
	    };
	}, action: any): {
	    [x: string]: {
	        [x: string]: boolean;
	    };
	};
	declare const _default: import("redux").Reducer<{
	    items: any;
	    itemIsComplete: {
	        [x: string]: {
	            [x: string]: boolean;
	        };
	    };
	    queries: any;
	}, any, Partial<{
	    items: any;
	    itemIsComplete: {
	        [x: string]: {
	            [x: string]: boolean;
	        };
	    } | undefined;
	    queries: any;
	}>>;
	export default _default;
}

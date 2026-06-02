declare module '@wordpress/core-data/build-types/queried-data/actions' {
	/**
	 * Returns an action object used in signalling that items have been received.
	 *
	 * @param {Array}   items Items received.
	 * @param {?Object} edits Optional edits to reset.
	 * @param {?Object} meta  Meta information about pagination.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveItems(items: any[], edits: any | null, meta: any | null): any;
	/**
	 * Returns an action object used in signalling that entity records have been
	 * deleted and they need to be removed from entities state.
	 *
	 * @param {string}              kind            Kind of the removed entities.
	 * @param {string}              name            Name of the removed entities.
	 * @param {Array|number|string} records         Record IDs of the removed entities.
	 * @param {boolean}             invalidateCache Controls whether we want to invalidate the cache.
	 * @return {Object} Action object.
	 */
	export function removeItems(kind: string, name: string, records: any[] | number | string, invalidateCache?: boolean): any;
	/**
	 * Returns an action object used in signalling that queried data has been
	 * received.
	 *
	 * @param {Array}   items Queried items received.
	 * @param {?Object} query Optional query object.
	 * @param {?Object} edits Optional edits to reset.
	 * @param {?Object} meta  Meta information about pagination.
	 *
	 * @return {Object} Action object.
	 */
	export function receiveQueriedItems(items: any[], query: (any | null) | undefined, edits: any | null, meta: any | null): any;
}

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { setError, setItems, setItemsTotalCount } from './actions';
import { request } from '../utils';
import { Item, ItemType, Query } from './types';

/**
 * Safety cap on the number of additional pages the resolver will fetch when
 * paginating to gather all matching items. Combined with the caller's
 * `per_page` (which defaults to the API's own page size), this keeps the
 * resolver from issuing an unbounded number of requests against very large
 * stores where a search term could match thousands of products.
 */
const MAX_PAGINATION_PAGES = 10;

export function* getItems( itemType: ItemType, query: Query ) {
	try {
		const endpoint =
			itemType === 'categories' ? 'products/categories' : itemType;
		const path = `${ NAMESPACE }/${ endpoint }`;

		const firstPageQuery = { ...query, page: query.page ?? 1 };
		const {
			items: firstPageItems,
			totalCount,
		}: { items: Item[]; totalCount: number } = yield request(
			path,
			firstPageQuery
		);

		let items: Item[] = firstPageItems;

		// When the caller didn't request a specific page and the response is
		// paginated (i.e. total count exceeds the items returned), fetch the
		// remaining pages so consumers like the Analytics search-and-filter
		// flow don't silently drop matches beyond the first page.
		const perPage = query.per_page ?? firstPageItems.length;
		const isUnbounded = query.per_page === -1;
		const requestedSpecificPage =
			typeof query.page === 'number' && query.page > 1;

		if (
			! isUnbounded &&
			! requestedSpecificPage &&
			perPage > 0 &&
			typeof totalCount === 'number' &&
			totalCount > firstPageItems.length
		) {
			const totalPages = Math.ceil( totalCount / perPage );
			const lastPage = Math.min( totalPages, 1 + MAX_PAGINATION_PAGES );

			for ( let page = 2; page <= lastPage; page++ ) {
				const pageQuery = { ...query, page };
				const { items: pageItems }: { items: Item[] } = yield request(
					path,
					pageQuery
				);
				if ( ! pageItems || ! pageItems.length ) {
					break;
				}
				items = items.concat( pageItems );
			}
		}

		yield setItemsTotalCount( itemType, query, totalCount );
		yield setItems( itemType, query, items );
	} catch ( error ) {
		yield setError( itemType, query, error );
	}
}

export function* getItemsTotalCount( itemType: ItemType, query: Query ) {
	try {
		const totalsQuery = {
			...query,
			page: 1,
			per_page: 1,
		};
		const endpoint =
			itemType === 'categories' ? 'products/categories' : itemType;
		const { totalCount } = yield request(
			`${ NAMESPACE }/${ endpoint }`,
			totalsQuery
		);
		yield setItemsTotalCount( itemType, query, totalCount );
	} catch ( error ) {
		yield setError( itemType, query, error );
	}
}

export function* getReviewsTotalCount( itemType: ItemType, query: Query ) {
	yield getItemsTotalCount( itemType, query );
}

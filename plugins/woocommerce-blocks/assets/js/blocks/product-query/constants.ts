/**
 * Internal dependencies
 */
import { QueryBlockQuery } from './types';

export const QUERY_DEFAULT_ATTRIBUTES: { query: QueryBlockQuery } = {
	query: {
		perPage: 6,
		pages: 0,
		offset: 0,
		postType: 'product',
		order: 'desc',
		orderBy: 'date',
		author: '',
		search: '',
		exclude: [],
		sticky: '',
		inherit: false,
	},
};

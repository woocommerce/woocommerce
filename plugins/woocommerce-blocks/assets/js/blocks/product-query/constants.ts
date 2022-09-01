/**
 * External dependencies
 */
import { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { QueryBlockQuery } from './types';

export const QUERY_DEFAULT_ATTRIBUTES: {
	query: QueryBlockQuery;
	displayLayout: {
		type: 'flex' | 'list';
		columns?: number;
	};
} = {
	displayLayout: {
		type: 'flex',
		columns: 3,
	},
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

export const INNER_BLOCKS_TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/post-template',
		{},
		[
			[ 'woocommerce/product-image', undefined, [] ],
			[
				'core/post-title',
				{
					level: 3,
					fontSize: 'large',
				},
				[],
			],
		],
	],
	[ 'core/query-pagination', undefined, [] ],
	[ 'core/query-no-results', undefined, [] ],
];

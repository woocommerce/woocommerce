/**
 * External dependencies
 */
import type { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { QueryBlockAttributes } from './types';

export const DEFAULT_CORE_ALLOWED_CONTROLS = [ 'order', 'taxQuery', 'search' ];

export const ALL_PRODUCT_QUERY_CONTROLS = [ 'onSale' ];

export const DEFAULT_ALLOWED_CONTROLS = [
	...DEFAULT_CORE_ALLOWED_CONTROLS,
	...ALL_PRODUCT_QUERY_CONTROLS,
];

export const QUERY_DEFAULT_ATTRIBUTES: QueryBlockAttributes = {
	allowControls: DEFAULT_ALLOWED_CONTROLS,
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
			[ 'woocommerce/product-image' ],
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
	[ 'core/query-pagination' ],
	[ 'core/query-no-results' ],
];

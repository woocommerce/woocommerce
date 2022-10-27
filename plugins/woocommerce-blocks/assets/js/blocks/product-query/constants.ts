/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import type { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { QueryBlockAttributes } from './types';

/**
 * Returns an object without a key.
 */
function objectOmit< T, K extends keyof T >( obj: T, key: K ) {
	const { [ key ]: omit, ...rest } = obj;

	return rest;
}

export const QUERY_LOOP_ID = 'core/query';

export const DEFAULT_CORE_ALLOWED_CONTROLS = [ 'order', 'taxQuery', 'search' ];

export const ALL_PRODUCT_QUERY_CONTROLS = [ 'onSale', 'stockStatus' ];

export const DEFAULT_ALLOWED_CONTROLS = [
	...DEFAULT_CORE_ALLOWED_CONTROLS,
	...ALL_PRODUCT_QUERY_CONTROLS,
];

export const STOCK_STATUS_OPTIONS = getSetting< Record< string, string > >(
	'stockStatusOptions',
	[]
);

const GLOBAL_HIDE_OUT_OF_STOCK = getSetting< boolean >(
	'hideOutOfStockItems',
	false
);

export const QUERY_DEFAULT_ATTRIBUTES: QueryBlockAttributes = {
	allowedControls: DEFAULT_ALLOWED_CONTROLS,
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
		__woocommerceStockStatus: GLOBAL_HIDE_OUT_OF_STOCK
			? Object.keys( objectOmit( STOCK_STATUS_OPTIONS, 'outofstock' ) )
			: Object.keys( STOCK_STATUS_OPTIONS ),
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

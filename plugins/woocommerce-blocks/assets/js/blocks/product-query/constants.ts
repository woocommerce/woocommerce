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

export const DEFAULT_CORE_ALLOWED_CONTROLS = [ 'taxQuery', 'search' ];

export const ALL_PRODUCT_QUERY_CONTROLS = [
	'attributes',
	'presets',
	'onSale',
	'stockStatus',
];

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
		perPage: 9,
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
		__woocommerceAttributes: [],
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
				'core/post-terms',
				{
					term: 'product_cat',
					textAlign: 'center',
					fontSize: 'small',
				},
				[],
			],
			[
				'core/post-title',
				{
					textAlign: 'center',
					level: 3,
					fontSize: 'medium',
				},
				[],
			],
			[
				'woocommerce/product-rating',
				{
					isDescendentOfQueryLoop: true,
					textAlign: 'center',
					fontSize: 'small',
				},
				[],
			],
			[
				'woocommerce/product-price',
				{
					isDescendentOfQueryLoop: true,
					textAlign: 'center',
					fontSize: 'small',
				},
				[],
			],
			[
				'woocommerce/product-button',
				{
					isDescendentOfQueryLoop: true,
					textAlign: 'center',
					fontSize: 'small',
				},
				[],
			],
		],
	],
	[ 'core/query-pagination' ],
	[ 'core/query-no-results' ],
];

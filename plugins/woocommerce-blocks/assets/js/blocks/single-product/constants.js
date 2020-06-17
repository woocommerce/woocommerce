/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, reader } from '@woocommerce/icons';
import { getBlockMap } from '@woocommerce/atomic-utils';

export const BLOCK_NAME = 'woocommerce/single-product';
export const BLOCK_TITLE = __(
	'Single Product',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON = <Icon srcElement={ reader } />;
export const BLOCK_DESCRIPTION = __(
	'Display a single product.',
	'woo-gutenberg-products-block'
);

export const DEFAULT_INNER_BLOCKS = [
	[
		'core/columns',
		{},
		[
			[
				'core/column',
				{},
				[ [ 'woocommerce/product-image', { showSaleBadge: false } ] ],
			],
			[
				'core/column',
				{},
				[
					[ 'woocommerce/product-sale-badge' ],
					[ 'woocommerce/product-title', { headingLevel: 1 } ],
					[ 'woocommerce/product-rating' ],
					[ 'woocommerce/product-price' ],
					[ 'woocommerce/product-summary' ],
					[ 'woocommerce/product-stock-indicator' ],
					[
						'woocommerce/product-add-to-cart',
						{ showFormElements: true },
					],
					[ 'woocommerce/product-sku' ],
					[ 'woocommerce/product-category-list' ],
					[ 'woocommerce/product-tag-list' ],
				],
			],
		],
	],
];

export const ALLOWED_INNER_BLOCKS = [
	'core/columns',
	'core/column',
	...Object.keys( getBlockMap( BLOCK_NAME ) ),
];

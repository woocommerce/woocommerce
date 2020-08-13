/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, reader } from '@woocommerce/icons';
import { getBlockMap } from '@woocommerce/atomic-utils';

export const BLOCK_NAME = 'woocommerce/single-product';
export const BLOCK_TITLE = __(
	'Single Product',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ reader } />;
export const BLOCK_DESCRIPTION = __(
	'Display a single product.',
	'woocommerce'
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
					[ 'woocommerce/product-button' ],
					[ 'woocommerce/product-sku' ],
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

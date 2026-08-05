/**
 * External dependencies
 */
import { Icon, mediaAndText } from '@wordpress/icons';
import { getBlockMap } from '@woocommerce/atomic-utils';
import { getSetting } from '@woocommerce/settings';
import type { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { VARIATION_NAME as PRODUCT_TITLE_VARIATION_NAME } from '../product-query/variations/elements/product-title';

export const BLOCK_ICON = (
	<Icon
		icon={ mediaAndText }
		className="wc-block-editor-components-block-icon"
	/>
);

export const DEFAULT_INNER_BLOCKS: InnerBlockTemplate[] = [
	[
		'core/columns',
		{},
		[
			[ 'core/column', {}, [ [ 'woocommerce/product-gallery' ] ] ],
			[
				'core/column',
				{},
				[
					[
						'core/post-title',
						{
							headingLevel: 2,
							isLink: true,
							__woocommerceNamespace:
								PRODUCT_TITLE_VARIATION_NAME,
						},
					],
					[
						'woocommerce/product-rating',
						{ isDescendentOfSingleProductBlock: true },
					],
					[
						'woocommerce/product-price',
						{ isDescendentOfSingleProductBlock: true },
					],
					[
						'woocommerce/product-summary',
						{ isDescendentOfSingleProductBlock: true },
					],
					[
						getSetting( 'isBlockTheme', false )
							? 'woocommerce/add-to-cart-with-options'
							: 'woocommerce/add-to-cart-form',
					],
					[ 'woocommerce/product-meta' ],
				],
			],
		],
	],
];

export const ALLOWED_INNER_BLOCKS = [
	'core/columns',
	'core/column',
	'core/post-title',
	'core/post-excerpt',
	'woocommerce/add-to-cart-form',
	'woocommerce/add-to-cart-with-options',
	'woocommerce/product-meta',
	'woocommerce/product-gallery',
	'woocommerce/product-reviews',
	'woocommerce/product-details',
	...Object.keys( getBlockMap( metadata.name ) ),
];

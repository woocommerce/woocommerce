/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { stacks } from '@woocommerce/icons';
import { __ } from '@wordpress/i18n';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';
import {
	BlockAttributes,
	InnerBlockTemplate
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { VARIATION_NAME as PRODUCT_TEMPLATE_ID } from './elements/product-template';
import { VARIATION_NAME as PRODUCT_TITLE_ID } from './elements/product-title';

export const RELATED_PRODUCTS_VARIATION_NAME = 'woocommerce/related-products';

export const BLOCK_ATTRIBUTES = {
	namespace: RELATED_PRODUCTS_VARIATION_NAME,
	allowedControls: [],
	displayLayout: {
		type: 'flex',
		columns: 5,
	},
	query: {
		perPage: 5,
		pages: 0,
		offset: 0,
		postType: 'product',
		order: 'asc',
		orderBy: 'title',
		author: '',
		search: '',
		exclude: [],
		sticky: '',
		inherit: false,
	},
	lock: {
		remove: true,
		move: true,
	},
};


const postTemplateHasSupportForGridView = getSettingWithCoercion(
	'postTemplateHasSupportForGridView',
	false,
	isBoolean
);



export const INNER_BLOCKS_TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 2,
			content: __( 'Related products', 'woocommerce' ),
			style: { spacing: { margin: { top: '1rem', bottom: '1rem' } } },
		},
	],
	[
		'core/post-template',
		{
			__woocommerceNamespace: PRODUCT_TEMPLATE_ID,
			...( postTemplateHasSupportForGridView && {
				layout: { type: 'grid', columnCount: 5 },
			} ),
		},
		[
			[
				'woocommerce/product-image',
				{
					productId: 0,
					imageSizing: 'cropped',
				},
			],
			[
				'core/post-title',
				{
					textAlign: 'center',
					level: 3,
					fontSize: 'medium',
					isLink: true,
					__woocommerceNamespace: PRODUCT_TITLE_ID,
				},
				[],
			],
			[
				'woocommerce/product-price',
				{
					textAlign: 'center',
					fontSize: 'small',
					style: {
						spacing: {
							margin: { bottom: '1rem' },
						},
					},
				},
				[],
			],
			[
				'woocommerce/product-button',
				{
					textAlign: 'center',
					fontSize: 'small',
					style: {
						spacing: {
							margin: { bottom: '1rem' },
						},
					},
				},
				[],
			],
		],
	],
];

export const RelatedProductsControlsBlockVariationSettings = {
	description: __( 'Display related products.', 'woocommerce' ),
	name: 'Related Products Controls',
	title: __( 'Related Products Controls', 'woocommerce' ),
	isActive: ( blockAttributes: BlockAttributes ) =>
		blockAttributes.namespace === RELATED_PRODUCTS_VARIATION_NAME,
	icon: (
		<Icon
			icon={ stacks }
			className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--stacks"
		/>
	),
	attributes: BLOCK_ATTRIBUTES,
	allowedControls: [],
	innerBlocks: INNER_BLOCKS_TEMPLATE,
	scope: [ 'block' ],
};

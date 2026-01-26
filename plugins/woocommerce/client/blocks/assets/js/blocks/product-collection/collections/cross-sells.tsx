/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, reusableBlock } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	INNER_BLOCKS_PRODUCT_TEMPLATE,
	DEFAULT_QUERY,
	DEFAULT_ATTRIBUTES,
} from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.CROSS_SELLS,
	title: __( 'Cross-Sells', 'woocommerce' ),
	icon: <Icon icon={ reusableBlock } />,
	description: __(
		'By suggesting complementary products in the cart using cross-sells, you can significantly increase the average order value.',
		'woocommerce'
	),
	keywords: [ 'boost', 'promotion' ],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
	usesReference: [ 'product', 'cart', 'order' ],
};

export const attributes = {
	...DEFAULT_ATTRIBUTES,
	displayLayout: {
		type: 'flex',
		columns: 4,
		shrinkColumns: true,
	},
	query: {
		...DEFAULT_QUERY,
		perPage: 8,
		pages: 1,
	},
	hideControls: [ CoreFilterNames.FILTERABLE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'left',
		level: 2,
		content: __( 'You may be interested in…', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const innerBlocks: InnerBlockTemplate[] = [
	heading,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
];

export default {
	...collection,
	attributes,
	innerBlocks,
};

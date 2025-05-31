/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, category } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.BY_CATEGORY,
	title: __( 'Products by Category', 'woocommerce' ),
	icon: <Icon icon={ category } />,
	description: __(
		'Display products from specific categories.',
		'woocommerce'
	),
	keywords: [ 'category', 'products' ],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		orderBy: 'post__in',
	},
	hideControls: [ CoreFilterNames.HAND_PICKED, CoreFilterNames.FILTERABLE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'Products by Category', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const pagination: InnerBlockTemplate = [
	'core/query-pagination',
	{
		layout: {
			type: 'flex',
			justifyContent: 'center',
		},
	},
];

const innerBlocks: InnerBlockTemplate[] = [
	heading,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
	pagination,
];

export default {
	...collection,
	attributes,
	innerBlocks,
};

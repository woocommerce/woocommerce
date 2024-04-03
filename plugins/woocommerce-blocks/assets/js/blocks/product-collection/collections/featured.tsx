/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, starFilled } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import {
	CoreCollectionNames,
	CoreFilterNames,
	LayoutOptions,
	ProductCollectionAttributes,
	ProductCollectionQuery,
} from '../types';

const collection = {
	name: CoreCollectionNames.FEATURED,
	title: __( 'Featured', 'woocommerce' ),
	icon: ( <Icon icon={ starFilled } /> ) as BlockIcon,
	description: __( 'Showcase your featured products.', 'woocommerce' ),
	keywords: [ 'product collection' ],
	scope: [],
};

const attributes: ProductCollectionAttributes = {
	...( DEFAULT_ATTRIBUTES as ProductCollectionAttributes ),
	templateLayout: {
		type: LayoutOptions.GRID,
		columnCount: 5,
	},
	query: {
		...( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery ),
		inherit: false,
		featured: true,
		perPage: 5,
		pages: 1,
	},
	collection: collection.name,
	hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.FEATURED ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'Featured products', 'woocommerce' ),
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

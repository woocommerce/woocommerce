/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, starEmpty } from '@wordpress/icons';

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
	name: CoreCollectionNames.TOP_RATED,
	title: __( 'Top Rated', 'woocommerce' ),
	icon: ( <Icon icon={ starEmpty } /> ) as BlockIcon,
	description: __(
		'Recommend products with the highest review ratings.',
		'woocommerce'
	),
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
		orderBy: 'rating',
		order: 'desc',
		perPage: 5,
		pages: 1,
	},
	collection: collection.name,
	hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.ORDER ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'Top rated products', 'woocommerce' ),
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

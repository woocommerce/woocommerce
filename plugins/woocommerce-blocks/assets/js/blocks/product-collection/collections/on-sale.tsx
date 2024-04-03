/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, percent } from '@wordpress/icons';

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
	name: CoreCollectionNames.ON_SALE,
	title: __( 'On Sale', 'woocommerce' ),
	icon: ( <Icon icon={ percent } /> ) as BlockIcon,
	description: __(
		'Highlight products that are currently on sale.',
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
		woocommerceOnSale: true,
		perPage: 5,
		pages: 1,
	},
	collection: collection.name,
	hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.ON_SALE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'On sale products', 'woocommerce' ),
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

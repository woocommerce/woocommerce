/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';
import { cart } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.CART_CONTENTS,
	title: __( 'Cart Contents', 'woocommerce' ),
	icon: <Icon icon={ cart } />,
	description: __(
		'Display products from the customer cart for abandoned cart emails.',
		'woocommerce'
	),
	keywords: [ 'cart', 'email', 'abandoned' ],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 1, // Single column for email compatibility
		shrinkColumns: true,
	},
	query: {
		// This will need to be handled by a custom query filter on the backend
		// to fetch products from the cart context
		inherit: false,
		perPage: 10, // Show up to 10 cart items
		pages: 1,
	},
	hideControls: [
		CoreFilterNames.ATTRIBUTES,
		CoreFilterNames.KEYWORD,
		CoreFilterNames.ORDER,
		CoreFilterNames.DEFAULT_ORDER,
		CoreFilterNames.FEATURED,
		CoreFilterNames.ON_SALE,
		CoreFilterNames.STOCK_STATUS,
		CoreFilterNames.HAND_PICKED,
		CoreFilterNames.TAXONOMY,
		CoreFilterNames.FILTERABLE,
		CoreFilterNames.CREATED,
		CoreFilterNames.PRICE_RANGE,
	],
	queryContextIncludes: [ 'cart' ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'Your Cart', 'woocommerce' ),
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

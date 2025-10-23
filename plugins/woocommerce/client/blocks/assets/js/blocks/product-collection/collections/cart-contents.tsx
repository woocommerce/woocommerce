/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

export const cartIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		fill="currentColor"
		viewBox="0 0 32 32"
	>
		<circle cx="12.667" cy="24.667" r="2"></circle>
		<circle cx="23.333" cy="24.667" r="2"></circle>
		<path
			fillRule="evenodd"
			d="M9.285 10.036a1 1 0 0 1 .776-.37h15.272a1 1 0 0 1 .99 1.142l-1.333 9.333A1 1 0 0 1 24 21H12a1 1 0 0 1-.98-.797L9.083 10.87a1 1 0 0 1 .203-.834m2.005 1.63L12.814 19h10.319l1.047-7.333z"
			clipRule="evenodd"
		></path>
		<path
			fillRule="evenodd"
			d="M5.667 6.667a1 1 0 0 1 1-1h2.666a1 1 0 0 1 .984.82l.727 4a1 1 0 1 1-1.967.359l-.578-3.18H6.667a1 1 0 0 1-1-1"
			clipRule="evenodd"
		></path>
	</svg>
);

const collection = {
	name: CoreCollectionNames.CART_CONTENTS,
	title: __( 'Cart Contents', 'woocommerce' ),
	icon: <Icon icon={ cartIcon } />,
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

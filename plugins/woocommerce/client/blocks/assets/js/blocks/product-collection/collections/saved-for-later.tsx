/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { productTemplateBlockName } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';
import { ImageSizing } from '../../../atomic/blocks/product-elements/image/types';

const collection = {
	name: CoreCollectionNames.SAVED_FOR_LATER,
	title: __( 'Saved for Later', 'woocommerce' ),
	icon: <Icon icon={ starEmpty } />,
	description: __(
		"Display the logged-in shopper's saved-for-later items on the cart page.",
		'woocommerce'
	),
	keywords: [ 'save', 'later', 'wishlist', 'cart' ],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 3,
		shrinkColumns: true,
	},
	query: {
		inherit: false,
		perPage: 10,
		pages: 1,
		orderBy: 'post__in',
	},
	hideControls: [
		CoreFilterNames.ATTRIBUTES,
		CoreFilterNames.CREATED,
		CoreFilterNames.FEATURED,
		CoreFilterNames.FILTERABLE,
		CoreFilterNames.HAND_PICKED,
		CoreFilterNames.KEYWORD,
		CoreFilterNames.ON_SALE,
		CoreFilterNames.ORDER,
		CoreFilterNames.DEFAULT_ORDER,
		CoreFilterNames.PRICE_RANGE,
		CoreFilterNames.STOCK_STATUS,
		CoreFilterNames.TAXONOMY,
	],
	queryContextIncludes: [ 'savedForLater' ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		level: 2,
		content: __( 'Saved for later', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const productTemplate: InnerBlockTemplate = [
	productTemplateBlockName,
	{},
	[
		[
			'woocommerce/product-image',
			{
				imageSizing: ImageSizing.THUMBNAIL,
				showSaleBadge: false,
			},
		],
		[
			'core/post-title',
			{
				level: 3,
				fontSize: 'medium',
				isLink: true,
			},
		],
		[
			'woocommerce/product-price',
			{
				fontSize: 'small',
			},
		],
		[ 'woocommerce/product-collection-saved-item', {} ],
	],
];

const innerBlocks: InnerBlockTemplate[] = [ heading, productTemplate ];

export default {
	...collection,
	attributes,
	innerBlocks,
};

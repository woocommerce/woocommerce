/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, chartBar } from '@wordpress/icons';

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
	HandlePreviewStateArgs,
} from '../types';

const collection = {
	name: CoreCollectionNames.BEST_SELLERS,
	title: __( 'Best Sellers', 'woocommerce' ),
	icon: ( <Icon icon={ chartBar } /> ) as BlockIcon,
	description: __( 'Recommend your best-selling products.', 'woocommerce' ),
	keywords: [ 'best selling', 'product collection' ],
	scope: [],
};

const attributes = {
	...DEFAULT_ATTRIBUTES,
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		...DEFAULT_ATTRIBUTES.query,
		inherit: false,
		orderBy: 'popularity',
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
		content: __( 'Best selling products', 'woocommerce' ),
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const innerBlocks: InnerBlockTemplate[] = [
	heading,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
];

/**
 * Example of how "Best Sellers" collection can handle preview state.
 * This is a example of sync operation, but it can be async.
 * See "On Sale" collection for async example.
 */
const handlePreviewState = ( { setPreviewState }: HandlePreviewStateArgs ) => {
	setPreviewState( {
		isPreview: true,
		previewMessage: __(
			'Custom tooltip for best sellers collection.',
			'woocommerce'
		),
	} );
};

export default {
	...collection,
	attributes,
	innerBlocks,
	handlePreviewState,
};

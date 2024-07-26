/**
 * External dependencies
 */
import type { InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.TOP_RATED,
	title: __( 'Top Rated', 'woocommerce' ),
	icon: <Icon icon={ starEmpty } />,
	description: __(
		'Recommend products with the highest review ratings.',
		'woocommerce'
	),
	keywords: [ 'product collection' ],
	scope: [],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		orderBy: 'rating',
		order: 'desc',
		perPage: 5,
		pages: 1,
	},
	hideControls: [ CoreFilterNames.ORDER, CoreFilterNames.FILTERABLE ],
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

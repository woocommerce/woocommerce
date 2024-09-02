/**
 * External dependencies
 */
import type { InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, chartBar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.BEST_SELLERS,
	title: __( 'Best Sellers', 'woocommerce' ),
	icon: <Icon icon={ chartBar } />,
	description: __( 'Recommend your best-selling products.', 'woocommerce' ),
	keywords: [ 'best selling', 'product collection' ],
	scope: [],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		orderBy: 'popularity',
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
		content: __( 'Best selling products', 'woocommerce' ),
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

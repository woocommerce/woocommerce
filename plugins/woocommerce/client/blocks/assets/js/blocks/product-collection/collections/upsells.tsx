/**
 * External dependencies
 */
import type {
	InnerBlockTemplate,
	BlockVariationScope,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, trendingUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.UPSELLS,
	title: __( 'Upsells', 'woocommerce' ),
	icon: <Icon icon={ trendingUp } />,
	description: __(
		'Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.',
		'woocommerce'
	),
	keywords: [ 'boost', 'promotion' ],
	scope: [ 'inserter', 'block' ] as BlockVariationScope[],
	usesReference: [ 'product', 'cart', 'order' ],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 4,
		shrinkColumns: true,
	},
	query: {
		perPage: 8,
		pages: 1,
	},
	hideControls: [ CoreFilterNames.FILTERABLE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'You may also like', 'woocommerce' ),
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

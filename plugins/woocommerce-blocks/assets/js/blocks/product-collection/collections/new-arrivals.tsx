/**
 * External dependencies
 */
import type { InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, calendar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_PRODUCT_TEMPLATE } from '../constants';
import {
	CoreCollectionNames,
	CoreFilterNames,
	ETimeFrameOperator,
} from '../types';

const collection = {
	name: CoreCollectionNames.NEW_ARRIVALS,
	title: __( 'New Arrivals', 'woocommerce' ),
	icon: <Icon icon={ calendar } />,
	description: __( 'Recommend your newest products.', 'woocommerce' ),
	keywords: [ 'newest products', 'product collection' ],
	scope: [],
};

const attributes = {
	displayLayout: {
		type: 'flex',
		columns: 5,
		shrinkColumns: true,
	},
	query: {
		orderBy: 'date',
		order: 'desc',
		perPage: 5,
		pages: 1,
		timeFrame: {
			operator: ETimeFrameOperator.IN,
			value: '-7 days',
		},
	},
	hideControls: [ CoreFilterNames.ORDER, CoreFilterNames.FILTERABLE ],
};

const heading: InnerBlockTemplate = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'New arrivals', 'woocommerce' ),
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

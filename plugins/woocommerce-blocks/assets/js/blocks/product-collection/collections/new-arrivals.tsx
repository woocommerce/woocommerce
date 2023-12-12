/**
 * External dependencies
 */
import type {
	BlockAttributes,
	InnerBlockTemplate,
	BlockIcon,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, calendar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.NEW_ARRIVALS,
	title: __( 'New Arrivals', 'woo-gutenberg-products-block' ),
	icon: ( <Icon icon={ calendar } /> ) as BlockIcon,
	description: __(
		'Recommend your newest products.',
		'woo-gutenberg-products-block'
	),
	keywords: [ 'newest products' ],
	scope: [],
	unchangeableFilters: [ CoreFilterNames.INHERIT, CoreFilterNames.ORDER ],
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
		orderBy: 'date',
		order: 'desc',
		perPage: 5,
		pages: 1,
	},
	collection: collection.name,
};

const heading: [ string, BlockAttributes?, InnerBlockTemplate[]? ] = [
	'core/heading',
	{
		textAlign: 'center',
		level: 2,
		content: __( 'New arrivals', 'woo-gutenberg-products-block' ),
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

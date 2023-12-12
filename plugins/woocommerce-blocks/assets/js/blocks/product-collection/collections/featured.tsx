/**
 * External dependencies
 */
import type {
	BlockAttributes,
	InnerBlockTemplate,
	BlockIcon,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, starFilled } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import { CoreCollectionNames, CoreFilterNames } from '../types';

const collection = {
	name: CoreCollectionNames.FEATURED,
	title: __( 'Featured', 'woo-gutenberg-products-block' ),
	icon: ( <Icon icon={ starFilled } /> ) as BlockIcon,
	description: __(
		'Showcase your featured products.',
		'woo-gutenberg-products-block'
	),
	keywords: [],
	scope: [],
	unchangeableFilters: [ CoreFilterNames.INHERIT, CoreFilterNames.FEATURED ],
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
		featured: true,
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
		content: __( 'Featured products', 'woo-gutenberg-products-block' ),
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

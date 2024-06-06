/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, loop } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES, INNER_BLOCKS_TEMPLATE } from '../constants';
import { CoreCollectionNames } from '../types';

const collection = {
	name: CoreCollectionNames.PRODUCT_CATALOG,
	title: __( 'Product Catalog', 'woocommerce' ),
	icon: ( <Icon icon={ loop } /> ) as BlockIcon,
	description:
		'Display all products in your catalog. Results can (change to) match the current template, page, or search term.',
	keywords: [ 'all products' ],
	scope: [],
};

const attributes = {
	...DEFAULT_ATTRIBUTES,
	query: {
		...DEFAULT_ATTRIBUTES.query,
	},
	collection: collection.name,
	hideControls: [],
};

const innerBlocks: InnerBlockTemplate[] = INNER_BLOCKS_TEMPLATE;

export default {
	...collection,
	attributes,
	innerBlocks,
};

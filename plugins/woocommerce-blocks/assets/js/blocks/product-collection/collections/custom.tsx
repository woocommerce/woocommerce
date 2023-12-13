/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, tool } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import { CoreCollectionNames } from '../types';

const collection = {
	name: CoreCollectionNames.CUSTOM,
	title: __( 'Custom', 'woocommerce' ),
	icon: ( <Icon icon={ tool } /> ) as BlockIcon,
	description:
		'Build your own collection of products and customize their layout. Optionally, adjust results to match the current template, page, or search term.',
	keywords: [],
	scope: [],
	unchangeableFilters: [],
};

const attributes = {
	...DEFAULT_ATTRIBUTES,
	query: {
		...DEFAULT_ATTRIBUTES.query,
		inherit: false,
	},
	collection: collection.name,
};

const innerBlocks: InnerBlockTemplate[] = [ INNER_BLOCKS_PRODUCT_TEMPLATE ];

export default {
	...collection,
	attributes,
	innerBlocks,
};

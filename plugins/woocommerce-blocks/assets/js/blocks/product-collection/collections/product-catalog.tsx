/**
 * External dependencies
 */
import type { InnerBlockTemplate, BlockIcon } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, loop } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
} from '../constants';
import { CoreCollectionNames } from '../types';

const collection = {
	name: CoreCollectionNames.PRODUCT_CATALOG,
	title: __( 'Product Catalog', 'woo-gutenberg-products-block' ),
	icon: ( <Icon icon={ loop } /> ) as BlockIcon,
	description:
		'Display all products. Results may be limited by the current template context.',
	keywords: [ 'all products' ],
	scope: [],
	unchangeableFilters: [],
};

const attributes = {
	...DEFAULT_ATTRIBUTES,
	query: {
		...DEFAULT_ATTRIBUTES.query,
		inherit: true,
	},
};

const innerBlocks: InnerBlockTemplate[] = [ INNER_BLOCKS_PRODUCT_TEMPLATE ];

export default {
	...collection,
	attributes,
	innerBlocks,
};

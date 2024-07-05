/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { supports } from './supports';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

const deprecated = [
	{
		migrate: () => {
			// We don't deprecate attributes, but adding new ones.
			// For backwards compatibility, some new attributes require
			// different defaults than new ones.
			return {
				showDescriptionIfEmpty: true,
				summaryLength: 150,
			};
		},
		isEligible: ( {
			isDescendantOfAllProducts,
		}: {
			isDescendantOfAllProducts: boolean;
		} ) => isDescendantOfAllProducts,
	},
];

const blockSettings: BlockConfiguration = {
	...sharedConfig,
	icon: { src: icon },
	supports,
	deprecated,
	edit,
};

registerBlockSingleProductTemplate( {
	blockName: 'woocommerce/product-summary',
	blockMetadata: metadata,
	blockSettings,
	isAvailableOnPostEditor: true,
} );

/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { ProductGalleryBlockSettings } from '@woocommerce/blocks/product-gallery/settings';

/**
 * Internal dependencies
 */
import productGalleryBlockMetadata from '../../../blocks/product-gallery/block.json';

export interface BlocksWithRestriction {
	[ key: string ]: {
		blockMetadata: Partial< BlockConfiguration >;
		blockSettings: Partial< BlockConfiguration >;
		allowedTemplates: {
			[ key: string ]: boolean;
		};
		allowedTemplateParts: {
			[ key: string ]: boolean;
		};
		availableInPostOrPageEditor: boolean;
		isVariationBlock: boolean;
	};
}

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
export const BLOCKS_WITH_RESTRICTION: BlocksWithRestriction = {
	[ productGalleryBlockMetadata.name ]: {
		blockMetadata: productGalleryBlockMetadata,
		blockSettings: ProductGalleryBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		allowedTemplateParts: {
			'product-gallery': true,
		},
		availableInPostOrPageEditor: false,
		isVariationBlock: false,
	},
};

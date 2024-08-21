/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { ProductGalleryBlockSettings } from '@woocommerce/blocks/product-gallery/settings';

/**
 * Internal dependencies
 */
import { AddToCartFormBlockSettings } from '../../../atomic/blocks/product-elements/add-to-cart-form/settings';
import productGalleryBlockMetadata from '../../../blocks/product-gallery/block.json';
import addToCartFormBlockMetadata from '../../../atomic/blocks/product-elements/add-to-cart-form/block.json';
import { EditorViewContentType } from './editor-view-change-detector';
import { isEmpty } from '@woocommerce/types';

export interface BlocksWithRestriction {
	[ key: string ]: {
		blockMetadata: Partial< BlockConfiguration >;
		blockSettings: Partial< BlockConfiguration >;
		allowedTemplates: {
			[ key: string ]: boolean | { onBeforeRegisterBlock: () => void };
		};
		allowedTemplateParts: {
			[ key: string ]: boolean;
		};
		availableInPostEditor: boolean;
		availableInPageEditor: boolean;
		isVariationBlock: boolean;
		onBeforeRegisterBlock?: ( context: {
			blockMetadata: Partial< BlockConfiguration >;
			blockSettings: Partial< BlockConfiguration >;
			currentContentType: EditorViewContentType;
			currentContentId: string;
		} ) => {
			blockMetadata: Partial< BlockConfiguration >;
			blockSettings: Partial< BlockConfiguration >;
		};
	};
}

export const BLOCKS_WITH_RESTRICTION: BlocksWithRestriction = {
	'woocommerce/add-to-cart-form': {
		blockMetadata: addToCartFormBlockMetadata,
		blockSettings: AddToCartFormBlockSettings,
		availableInPostEditor: true,
		availableInPageEditor: true,
		isVariationBlock: false,
		onBeforeRegisterBlock( blockConfig ) {
			const {
				blockMetadata,
				blockSettings,
				currentContentId,
				currentContentType,
			} = blockConfig;

			if (
				currentContentType === EditorViewContentType.PAGE ||
				currentContentType === EditorViewContentType.POST ||
				currentContentType === EditorViewContentType.WP_TEMPLATE_PART ||
				( currentContentType === EditorViewContentType.WP_TEMPLATE &&
					! currentContentId?.includes( 'single-product' ) )
			) {
				blockSettings.ancestor = [ 'woocommerce/single-product' ];
			} else {
				blockSettings.ancestor = undefined;
			}

			console.log(
				'changing block settings for Add to Cart Form',
				blockSettings
			);

			return { blockMetadata, blockSettings };
		},
	},
	'woocommerce/product-gallery': {
		blockMetadata: productGalleryBlockMetadata,
		blockSettings: ProductGalleryBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		allowedTemplateParts: {
			'product-gallery': true,
		},
		availableInPostEditor: false,
		availableInPageEditor: false,
		isVariationBlock: false,
	},
};

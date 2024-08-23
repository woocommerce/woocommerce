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
import productMetaBlockMetadata from '../../../atomic/blocks/product-elements/product-meta/block.json';
import productImageGalleryBlockMetadata from '../../../atomic/blocks/product-elements/product-image-gallery/block.json';
import { EditorViewContentType } from './editor-view-change-detector';
import { ProductPriceBlockSettings } from '../../blocks/product-elements/price/settings';
import { ProductImageGalleryBlockSettings } from '../../blocks/product-elements/product-image-gallery/settings';
import { ProductMetaBlockSettings } from '../../blocks/product-elements/product-meta/settings';

interface BlockWithRestriction {
	blockMetadata?: Partial< BlockConfiguration > | string;
	blockSettings: Partial< BlockConfiguration >;
	allowedTemplates:
		| {
				[ key: string ]:
					| boolean
					| { onBeforeRegisterBlock: () => void };
		  }
		| undefined;
	allowedTemplateParts:
		| {
				[ key: string ]: boolean;
		  }
		| undefined;
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
}

export interface BlocksWithRestriction {
	[ key: string ]: BlockWithRestriction;
}

const createBlockWithRestriction = (
	context: BlockWithRestriction
): BlockWithRestriction => {
	const defaultBlockWithRestriction: BlockWithRestriction = {
		blockMetadata: context.blockMetadata,
		blockSettings: context.blockSettings,
		availableInPostEditor: true,
		availableInPageEditor: true,
		allowedTemplates: undefined,
		allowedTemplateParts: undefined,
		isVariationBlock: false,
	};

	if ( context.availableInPageEditor !== undefined ) {
		defaultBlockWithRestriction.availableInPageEditor =
			context.availableInPageEditor;
	}
	if ( context.availableInPostEditor !== undefined ) {
		defaultBlockWithRestriction.availableInPostEditor =
			context.availableInPostEditor;
	}
	if ( context.allowedTemplates !== undefined ) {
		defaultBlockWithRestriction.allowedTemplates = context.allowedTemplates;
	}
	if ( context.allowedTemplateParts !== undefined ) {
		defaultBlockWithRestriction.allowedTemplateParts =
			context.allowedTemplateParts;
	}
	if ( context.isVariationBlock !== undefined ) {
		defaultBlockWithRestriction.isVariationBlock = context.isVariationBlock;
	}
	if ( context.onBeforeRegisterBlock !== undefined ) {
		defaultBlockWithRestriction.onBeforeRegisterBlock =
			context.onBeforeRegisterBlock;
	}

	return defaultBlockWithRestriction;
};

export const BLOCKS_WITH_RESTRICTION: BlocksWithRestriction = {
	'woocommerce/add-to-cart-form': createBlockWithRestriction( {
		blockMetadata: addToCartFormBlockMetadata,
		blockSettings: AddToCartFormBlockSettings,
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

			return { blockMetadata, blockSettings };
		},
	} ),
	'woocommerce/product-meta': createBlockWithRestriction( {
		blockMetadata: productMetaBlockMetadata,
		blockSettings: ProductMetaBlockSettings,
		onBeforeRegisterBlock( blockConfig ) {
			const {
				blockMetadata,
				blockSettings,
				currentContentId,
				currentContentType,
			} = blockConfig;

			if (
				currentContentType === EditorViewContentType.WP_TEMPLATE &&
				currentContentId?.includes( 'single-product' )
			) {
				blockSettings.ancestor = undefined;
			}

			return { blockMetadata, blockSettings };
		},
	} ),
	'woocommerce/product-image-gallery': createBlockWithRestriction( {
		blockMetadata: productImageGalleryBlockMetadata,
		blockSettings: ProductImageGalleryBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		availableInPostEditor: false,
		availableInPageEditor: false,
	} ),
	'woocommerce/product-gallery': createBlockWithRestriction( {
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
	} ),
	'woocommerce/product-price': createBlockWithRestriction( {
		blockSettings: ProductPriceBlockSettings,
		onBeforeRegisterBlock( blockConfig ) {
			const {
				blockMetadata,
				blockSettings,
				currentContentId,
				currentContentType,
			} = blockConfig;

			if (
				currentContentType === EditorViewContentType.WP_TEMPLATE &&
				currentContentId?.includes( 'single-product' )
			) {
				blockSettings.ancestor = undefined;
			}

			return { blockMetadata, blockSettings };
		},
	} ),
};

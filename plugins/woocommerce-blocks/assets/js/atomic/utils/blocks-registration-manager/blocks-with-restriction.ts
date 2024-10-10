/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { ProductGalleryBlockSettings } from '@woocommerce/blocks/product-gallery/settings';
import { QUERY_LOOP_ID } from '@woocommerce/blocks/product-query/constants';
import {
	RELATED_PRODUCTS_VARIATION_NAME,
	RelatedProductsControlsBlockVariationSettings,
} from '@woocommerce/blocks/product-query/variations';

/**
 * Internal dependencies
 */
import { AddToCartFormBlockSettings } from '../../../atomic/blocks/product-elements/add-to-cart-form/settings';
import productGalleryBlockMetadata from '../../../blocks/product-gallery/block.json';
import addToCartFormBlockMetadata from '../../../atomic/blocks/product-elements/add-to-cart-form/block.json';
import productMetaBlockMetadata from '../../../atomic/blocks/product-elements/product-meta/block.json';
import productImageGalleryBlockMetadata from '../../../atomic/blocks/product-elements/product-image-gallery/block.json';
import productRatingBlockMetadata from '../../../atomic/blocks/product-elements/rating/block.json';
import productDetailsBlockMetadata from '../../../atomic/blocks/product-elements/product-details/block.json';
import productReviewsBlockMetadata from '../../../atomic/blocks/product-elements/product-reviews/block.json';
import relatedProductsBlockMetadata from '../../../atomic/blocks/product-elements/related-products/block.json';
import { EditorViewContentType } from './editor-view-change-detector';
import { ProductPriceBlockSettings } from '../../blocks/product-elements/price/settings';
import { ProductImageGalleryBlockSettings } from '../../blocks/product-elements/product-image-gallery/settings';
import { ProductMetaBlockSettings } from '../../blocks/product-elements/product-meta/settings';
import { ProductRatingBlockSettings } from '../../blocks/product-elements/rating/settings';
import { RelatedProductsBlockSettings } from '../../blocks/product-elements/related-products/settings';
import { ProductDetailsBlockSettings } from '../../blocks/product-elements/product-details/settings';
import { ProductReviewsBlockSettings } from '../../blocks/product-elements/product-reviews/settings';

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
	blockVariationName?: string;
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
	[ QUERY_LOOP_ID ]: createBlockWithRestriction( {
		blockSettings: RelatedProductsControlsBlockVariationSettings,
		allowedTemplates: {
			'single-product': true,
		},
		allowedTemplateParts: undefined,
		availableInPageEditor: false,
		availableInPostEditor: false,
		isVariationBlock: true,
		blockVariationName: RELATED_PRODUCTS_VARIATION_NAME,
	} ),
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
	'woocommerce/product-details': createBlockWithRestriction( {
		blockMetadata: productDetailsBlockMetadata,
		blockSettings: ProductDetailsBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		availableInPageEditor: false,
		availableInPostEditor: false,
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
	'woocommerce/product-rating': createBlockWithRestriction( {
		blockMetadata: productRatingBlockMetadata,
		blockSettings: ProductRatingBlockSettings,
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
	'woocommerce/product-reviews': createBlockWithRestriction( {
		blockMetadata: productReviewsBlockMetadata,
		blockSettings: ProductReviewsBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		availableInPageEditor: false,
		availableInPostEditor: false,
	} ),
	'woocommerce/related-products': createBlockWithRestriction( {
		blockMetadata: relatedProductsBlockMetadata,
		blockSettings: RelatedProductsBlockSettings,
		allowedTemplates: {
			'single-product': true,
		},
		availableInPageEditor: false,
		availableInPostEditor: false,
	} ),
};

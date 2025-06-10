/**
 * External dependencies
 */
import { ProductQueryContext } from '@woocommerce/blocks/product-query/types';

export enum ImageSizing {
	SINGLE = 'single',
	THUMBNAIL = 'thumbnail',
}

export interface BlockAttributes {
	// The product ID.
	productId: number;
	// CSS Class name for the component.
	className?: string | undefined;
	// Whether or not to display a link to the product page.
	showProductLink: boolean;
	// Whether or not to display the on sale badge.
	showSaleBadge: boolean;
	// How should the sale badge be aligned if displayed.
	saleBadgeAlign: 'left' | 'center' | 'right';
	// Size of image to use.
	imageSizing: ImageSizing;
	// Whether or not the block is within the context of a Query Loop Block.
	isDescendentOfQueryLoop: boolean;
	// Whether or not the block is within the context of a Single Product Block.
	isDescendentOfSingleProductBlock: boolean;
	// Height of the image.
	height?: string;
	// Width of the image.
	width?: string;
	// Image scaling method.
	scale: 'cover' | 'contain' | 'fill';
	// Aspect ratio of the image.
	aspectRatio: string;
}

export interface ProductImageContext extends ProductQueryContext {
	/**
	 * An `imageId` to display a particular image.
	 *
	 * Note: this `imageId` should be of one of the images present in the product context.
	 * If no product context is present, or the image is not amongst them, this will be ignored.
	 */
	imageId?: number;
	// The post ID of the product.
	postId?: number;
}

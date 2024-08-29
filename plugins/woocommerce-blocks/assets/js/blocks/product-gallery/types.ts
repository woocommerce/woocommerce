/**
 * Internal dependencies
 */
import { ProductGalleryNextPreviousBlockAttributes } from './inner-blocks/product-gallery-large-image-next-previous/types';
import { PagerDisplayModes } from './inner-blocks/product-gallery-pager/constants';
import { ThumbnailsPosition } from './inner-blocks/product-gallery-thumbnails/constants';

export interface ProductGalleryBlockAttributes {
	cropImages?: boolean;
	hoverZoom?: boolean;
	fullScreenOnClick?: boolean;
	mode?: 'standard' | 'full';
}

export interface ProductGalleryThumbnailsBlockAttributes {
	thumbnailsPosition: ThumbnailsPosition;
	thumbnailsNumberOfThumbnails: number;
	productGalleryClientId: string;
}

export interface ProductGalleryPagerBlockAttributes {
	pagerDisplayMode: PagerDisplayModes;
}

export interface ProductGalleryBlockEditProps {
	clientId: string;
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		newAttributes: ProductGalleryThumbnailsBlockAttributes
	) => void;
}

export interface ProductGallerySettingsProps {
	attributes: ProductGalleryBlockAttributes;
	setAttributes: ( attributes: ProductGalleryBlockAttributes ) => void;
	context: ProductGalleryContext;
}

export interface ProductGalleryThumbnailsSettingsProps {
	context: ProductGalleryThumbnailsContext;
}

export type ProductGalleryContext = {
	thumbnailsPosition: ThumbnailsPosition;
	thumbnailsNumberOfThumbnails: number;
	productGalleryClientId: string;
	pagerDisplayMode: PagerDisplayModes;
} & ProductGalleryNextPreviousBlockAttributes;

export type ProductGalleryPagerContext = Pick<
	ProductGalleryContext,
	'productGalleryClientId' | 'pagerDisplayMode'
>;

export type ProductGalleryLargeImageNextPreviousContext = Pick<
	ProductGalleryContext,
	'productGalleryClientId' | 'nextPreviousButtonsPosition'
>;

export type ProductGalleryThumbnailsContext = Pick<
	ProductGalleryContext,
	| 'productGalleryClientId'
	| 'thumbnailsPosition'
	| 'thumbnailsNumberOfThumbnails'
>;

export type ProductGalleryAttributes = ProductGalleryThumbnailsBlockAttributes &
	ProductGalleryBlockAttributes &
	ProductGalleryPagerBlockAttributes &
	ProductGalleryNextPreviousBlockAttributes;

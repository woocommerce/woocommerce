/**
 * Internal dependencies
 */
import { PagerDisplayModes } from './inner-blocks/product-gallery-pager/constants';
import { ThumbnailsPosition } from './inner-blocks/product-gallery-thumbnails/constants';

export interface ProductGalleryBlockAttributes {
	cropImages?: boolean;
	hoverZoom?: boolean;
	fullScreenOnClick?: boolean;
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
}

export interface ProductGalleryThumbnailsSettingsProps {
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		attributes: ProductGalleryThumbnailsBlockAttributes
	) => void;
	context: ProductGalleryThumbnailsBlockAttributes;
}

export interface ProductGalleryContext {
	thumbnailsPosition: ThumbnailsPosition;
	thumbnailsNumberOfThumbnails: number;
	productGalleryClientId: string;
	pagerDisplayMode: PagerDisplayModes;
}

export type ProductGalleryPagerContext = Pick<
	ProductGalleryContext,
	'productGalleryClientId' | 'pagerDisplayMode'
>;

export type ProductGalleryAttributes = ProductGalleryThumbnailsBlockAttributes &
	ProductGalleryBlockAttributes &
	ProductGalleryPagerBlockAttributes;

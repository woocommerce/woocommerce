/**
 * Internal dependencies
 */
import { ThumbnailsPosition } from './inner-blocks/product-gallery-thumbnails/constants';

export interface BlockAttributes {
	thumbnailsPosition: ThumbnailsPosition;
	thumbnailsNumberOfThumbnails: number;
	productGalleryClientId: string;
}

export interface Context {
	context: {
		thumbnailsPosition: ThumbnailsPosition;
		thumbnailsNumberOfThumbnails: number;
		productGalleryClientId: string;
	};
}

export interface ProductGalleryBlockEditProps {
	clientId: string;
	attributes: BlockAttributes;
	setAttributes: ( newAttributes: BlockAttributes ) => void;
}

export interface ThumbnailsSettingProps {
	attributes: BlockAttributes;
	context: BlockAttributes;
	setAttributes: ( attributes: BlockAttributes ) => void;
}

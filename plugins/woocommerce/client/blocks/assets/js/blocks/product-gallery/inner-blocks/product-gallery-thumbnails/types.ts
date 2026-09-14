export enum ProductGalleryActiveThumbnailStyle {
	OVERLAY = 'overlay',
	OUTLINE = 'outline',
}

export type ProductGalleryThumbnailsBlockAttributes = {
	thumbnailSize: string;
	aspectRatio: string;
	activeThumbnailStyle: ProductGalleryActiveThumbnailStyle;
};

export type ProductGalleryThumbnailsSettingsProps = {
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryThumbnailsBlockAttributes >
	) => void;
};

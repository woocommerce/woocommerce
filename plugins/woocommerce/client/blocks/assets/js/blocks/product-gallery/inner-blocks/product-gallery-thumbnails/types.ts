export type ProductGalleryThumbnailsBlockAttributes = {
	thumbnailSize: string;
	aspectRatio: string;
	activeThumbnailStyle: 'overlay' | 'outline';
};

export type ProductGalleryThumbnailsSettingsProps = {
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryThumbnailsBlockAttributes >
	) => void;
};

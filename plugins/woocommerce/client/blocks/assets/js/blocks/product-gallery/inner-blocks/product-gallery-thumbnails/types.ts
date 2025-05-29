export type ProductGalleryThumbnailsBlockAttributes = {
	thumbnailSize: string;
	aspectRatio: string;
	cropImages: boolean;
};

export type ProductGalleryThumbnailsSettingsProps = {
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryThumbnailsBlockAttributes >
	) => void;
};

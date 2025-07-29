export type ProductGalleryThumbnailsBlockAttributes = {
	thumbnailSize: string;
	aspectRatio: string;
} & {
	context: {
		postId?: string;
	};
};

export type ProductGalleryThumbnailsSettingsProps = {
	attributes: ProductGalleryThumbnailsBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryThumbnailsBlockAttributes >
	) => void;
};

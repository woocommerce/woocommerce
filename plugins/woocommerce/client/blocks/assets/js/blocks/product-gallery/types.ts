export interface ProductGalleryBlockAttributes {
	cropImages: boolean;
	hoverZoom: boolean;
	fullScreenOnClick: boolean;
}

export interface ProductGallerySettingsProps {
	attributes: ProductGalleryBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryBlockAttributes >
	) => void;
}

export interface ImageDataItem {
	id: number;
	src: string;
	srcSet: string;
	sizes: string;
	isActive?: boolean;
}

interface ImageDataObject {
	images: Record< number, ImageDataItem >;
	image_ids: number[];
}

export interface ProductGalleryContext {
	selectedImageId: number;
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	userHasInteracted: boolean;
	imageData: ImageDataObject;
	image: ImageDataItem;
	thumbnailsOverflow: {
		top: boolean;
		bottom: boolean;
		left: boolean;
		right: boolean;
	};
}

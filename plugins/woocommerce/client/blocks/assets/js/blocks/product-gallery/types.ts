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

export interface ProductGalleryContext {
	selectedImageId: number;
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	imageData: ImageDataItem[];
	image: ImageDataItem;
	thumbnailsOverflow: {
		top: boolean;
		bottom: boolean;
		left: boolean;
		right: boolean;
	};
}

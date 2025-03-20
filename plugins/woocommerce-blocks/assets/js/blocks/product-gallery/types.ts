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

export interface ProductGalleryContext {
	// It's an actual image number, not an index, hence one-based!
	selectedImageNumber: number;
	imageId: string;
	imageIds: string[];
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
}

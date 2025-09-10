export interface ProductGalleryBlockAttributes {
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
	selectedImageId: number;
	isDialogOpen: boolean;
	productId: string;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	imageData: number[];
	thumbnailsOverflow: {
		top: boolean;
		bottom: boolean;
		left: boolean;
		right: boolean;
	};
	// Next/Previous Buttons block context
	hideNextPreviousButtons: boolean;
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
	ariaLabelPrevious: string;
	ariaLabelNext: string;
}

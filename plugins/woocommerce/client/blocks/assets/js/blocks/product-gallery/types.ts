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

interface ImageDataItem {
	id: number;
	src: string;
	srcSet: string;
	sizes: string;
}

export interface ImageDataObject {
	images: Record< number, ImageDataItem >;
	image_ids: number[];
}

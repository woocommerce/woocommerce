/**
 * External dependencies
 */
import type { WooCommerceConfig } from '@woocommerce/stores/woocommerce/cart';

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

export type VariationImageSet = {
	image_id?: number;
	image_ids?: number[];
};

export type ProductImageSet = VariationImageSet & {
	variations?: Record< number, VariationImageSet >;
};

export type ProductGalleryConfig = WooCommerceConfig & {
	products?: Record< string, ProductImageSet >;
};

export type LegacyVariationPayload = {
	variation_id?: number | string;
	image_id?: number | string;
};

export type LegacyJQueryInstance = {
	on: (
		eventName: string,
		handler: ( event?: unknown, variation?: LegacyVariationPayload ) => void
	) => LegacyJQueryInstance;
	off: ( namespace: string ) => LegacyJQueryInstance;
};

export type LegacyJQueryWindow = Window & {
	jQuery?: ( target: Element | string ) => LegacyJQueryInstance;
};

export type LegacyJQueryFormHandlers = {
	onVariationFound: (
		variationId?: number,
		featuredImageId?: number
	) => void;
	onVariationReset: () => void;
};

export interface ProductGalleryContext {
	selectedImageId: number;
	isDialogOpen: boolean;
	videoLocation?: 'dialog' | 'gallery';
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

export interface ProductFiltersBlockAttributes {
	productId?: string;
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	overlay: string;
	overlayButton: string;
	overlayButtonStyle: string;
	overlayButtonSize: number | undefined;
}

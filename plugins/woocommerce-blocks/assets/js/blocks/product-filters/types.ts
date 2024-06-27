export interface ProductFiltersBlockAttributes {
	productId?: string;
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	overlay: string;
	overlayIcon: string;
	overlayButtonStyle: string;
	overlayIconSize?: number;
}

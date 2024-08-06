export enum BlockOverlayAttribute {
	NEVER = 'never',
	MOBILE = 'mobile',
	ALWAYS = 'always',
}

export interface BlockAttributes {
	productId?: string;
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	overlay: BlockOverlayAttribute;
	overlayIcon:
		| 'filter-icon-1'
		| 'filter-icon-2'
		| 'filter-icon-3'
		| 'filter-icon-4';
	overlayButtonStyle: 'label-icon' | 'label' | 'icon';
	overlayIconSize?: number;
}

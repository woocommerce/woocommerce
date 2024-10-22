export interface BlockAttributes {
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	productId?: string;
	overlayIcon:
		| 'filter-icon-1'
		| 'filter-icon-2'
		| 'filter-icon-3'
		| 'filter-icon-4';
	overlayButtonStyle: 'label-icon' | 'label' | 'icon';
	overlayIconSize?: number;
}

export type FilterOptionItem = {
	label: string;
	value: string;
	selected?: boolean;
	rawData?: Record< string, unknown >;
};

export type FilterBlockContext = {
	filterData: {
		isLoading: boolean;
		items?: FilterOptionItem[];
		price?: {
			minPrice: number;
			minRange: number;
			maxPrice: number;
			maxRange: number;
		};
	};
};

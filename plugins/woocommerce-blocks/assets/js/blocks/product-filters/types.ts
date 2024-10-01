/**
 * Internal dependencies
 */
import { BlockOverlayAttribute } from './constants';

export type BlockOverlayAttributeOptions =
	( typeof BlockOverlayAttribute )[ keyof typeof BlockOverlayAttribute ];

export interface BlockAttributes {
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	productId?: string;
	overlay: BlockOverlayAttributeOptions;
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
		range?: {
			min: number;
			max: number;
			step: number;
		};
	};
	isParentSelected: boolean;
};

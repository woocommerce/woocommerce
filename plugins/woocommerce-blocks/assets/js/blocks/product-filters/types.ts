/**
 * Internal dependencies
 */
import { BlockOverlayAttribute } from './constants';

export type BlockOverlayAttributeOptions =
	( typeof BlockOverlayAttribute )[ keyof typeof BlockOverlayAttribute ];

export interface BlockAttributes {
	productId?: string;
	setAttributes: ( attributes: ProductFiltersBlockAttributes ) => void;
	overlay: BlockOverlayAttributeOptions;
	overlayIcon:
		| 'filter-icon-1'
		| 'filter-icon-2'
		| 'filter-icon-3'
		| 'filter-icon-4';
	overlayButtonStyle: 'label-icon' | 'label' | 'icon';
	overlayIconSize?: number;
}

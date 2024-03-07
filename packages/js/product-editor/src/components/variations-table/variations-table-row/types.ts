/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import {
	PartialProductVariation,
	ProductAttribute,
	ProductVariation,
} from '@woocommerce/data';

export type VariationsTableRowProps = {
	variation: ProductVariation;
	variableAttributes: ProductAttribute[];
	isUpdating?: boolean;
	isSelected?: boolean;
	isSelectionDisabled?: boolean;
	hideActionButtons?: boolean;
	onChange( variation: PartialProductVariation ): void;
	onDelete( variation: PartialProductVariation ): void;
	onEdit( event: MouseEvent< HTMLAnchorElement > ): void;
	onSelect( value: boolean ): void;
};

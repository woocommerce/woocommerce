/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { ProductAttribute, ProductVariation } from '@woocommerce/data';

export type VariationsTableRowProps = {
	variation: ProductVariation;
	variableAttributes: ProductAttribute[];
	isUpdating?: boolean;
	isSelected?: boolean;
	isSelectionDisabled?: boolean;
	hideActionButtons?: boolean;
	onChange( variation: ProductVariation ): void;
	onDelete( variation: ProductVariation ): void;
	onEdit( event: MouseEvent< HTMLAnchorElement > ): void;
	onSelect( value: boolean ): void;
};

/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import {
	PartialProductVariation,
	ProductProductAttribute,
	ProductVariation,
} from '@woocommerce/data';

export type VariationsTableRowProps = {
	variation: ProductVariation;
	variableAttributes: ProductProductAttribute[];
	isUpdating?: boolean;
	isSelected?: boolean;
	isSelectionDisabled?: boolean;
	hideActionButtons?: boolean;
	onChange( variation: PartialProductVariation ): void;
	onDelete( variation: PartialProductVariation ): void;
	onEdit( event: MouseEvent< HTMLAnchorElement > ): void;
	onSelect( value: boolean ): void;
};

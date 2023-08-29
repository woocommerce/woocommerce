/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';

export type VariationActionsMenuProps = {
	variation: ProductVariation;
	onChange( variation: Partial< ProductVariation > ): void;
	onDelete( variationId: number ): void;
};

export type MenuItemProps = {
	variation?: ProductVariation;
	onChange( variation: Partial< ProductVariation > ): void;
	onClose(): void;
};
